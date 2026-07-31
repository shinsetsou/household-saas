<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Events\OrganizationInvitationSent;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function invite(Organization $organization, User $inviter, string $email, OrganizationRole $role): OrganizationInvitation
    {
        $email = strtolower(trim($email));

        $alreadyMember = $organization->users()
            ->whereRaw('LOWER(users.email) = ?', [$email])
            ->exists();

        if ($alreadyMember) {
            throw ValidationException::withMessages([
                'email' => 'That person is already a member of this HOA.',
            ]);
        }

        $existingPending = $organization->invitations()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->pending()
            ->first();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'email' => 'That email already has a pending invitation.',
            ]);
        }

        return DB::transaction(function () use ($organization, $inviter, $email, $role) {
            $invitation = OrganizationInvitation::create([
                'organization_id' => $organization->id,
                'email' => $email,
                'role' => $role->value,
                'invited_by' => $inviter->id,
                'verification_code' => OrganizationInvitation::generateVerificationCode(),
            ]);

            AuditLog::record(
                $organization,
                'organization.member.invited',
                $inviter,
                $organization,
                [
                    'invitation_id' => $invitation->id,
                    'email' => $email,
                    'role' => $role->value,
                ],
            );

            event(new OrganizationInvitationSent($invitation));

            return $invitation;
        });
    }

    public function revoke(OrganizationInvitation $invitation, User $actor): void
    {
        if ($invitation->isRevoked() || $invitation->isAccepted()) {
            return;
        }

        $invitation->update(['revoked_at' => now()]);

        AuditLog::record(
            $invitation->organization,
            'organization.member.invitation_revoked',
            $actor,
            $invitation->organization,
            [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
            ],
        );

        $this->lockUserIfOrphan($invitation);
    }

    protected function lockUserIfOrphan(OrganizationInvitation $invitation): void
    {
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])->first();

        if (! $user || $user->organizations()->exists()) {
            return;
        }

        $user->lockInviteOrigin();
    }

    public function resend(OrganizationInvitation $invitation, User $actor): OrganizationInvitation
    {
        abort_unless($invitation->isPending(), 404);

        $invitation->update([
            'expires_at' => now()->addDays(OrganizationInvitation::INVITATION_TTL_DAYS),
            'verification_code' => OrganizationInvitation::generateVerificationCode(),
        ]);

        AuditLog::record(
            $invitation->organization,
            'organization.member.invitation_resent',
            $actor,
            $invitation->organization,
            ['invitation_id' => $invitation->id],
        );

        event(new OrganizationInvitationSent($invitation->fresh()));

        return $invitation->fresh();
    }

    public function accept(OrganizationInvitation $invitation, User $user): void
    {
        abort_unless($invitation->isPending(), 404);

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            abort(403, 'This invitation was sent to a different email address.');
        }

        DB::transaction(function () use ($invitation, $user) {
            $user->organizations()->attach($invitation->organization_id, [
                'role' => $invitation->role,
            ]);

            $invitation->update(['accepted_at' => now()]);

            $user->clearInviteOriginLock();

            AuditLog::record(
                $invitation->organization,
                'organization.member.joined',
                $user,
                $invitation->organization,
                [
                    'invitation_id' => $invitation->id,
                    'role' => $invitation->role,
                ],
            );
        });
    }

    public function removeMember(Organization $organization, User $member, User $actor): void
    {
        DB::transaction(function () use ($organization, $member, $actor) {
            $locked = Organization::whereKey($organization->id)->lockForUpdate()->first();

            $role = $locked->roleFor($member);

            if (! $role) {
                abort(404);
            }

            if ($role === OrganizationRole::Admin) {
                $adminCount = $locked->users()
                    ->wherePivot('role', OrganizationRole::Admin->value)
                    ->count();

                if ($adminCount <= 1) {
                    throw ValidationException::withMessages([
                        'member' => 'Cannot remove the last admin. Promote another member to admin first.',
                    ]);
                }
            }

            $locked->users()->detach($member->id);

            AuditLog::record(
                $locked,
                'organization.member.removed',
                $actor,
                $locked,
                [
                    'member_id' => $member->id,
                    'member_email' => $member->email,
                    'role' => $role->value,
                ],
            );
        });
    }

    public function changeRole(Organization $organization, User $member, OrganizationRole $newRole, User $actor): void
    {
        DB::transaction(function () use ($organization, $member, $newRole, $actor) {
            $locked = Organization::whereKey($organization->id)->lockForUpdate()->first();

            $currentRole = $locked->roleFor($member);

            if (! $currentRole) {
                abort(404);
            }

            if ($currentRole === OrganizationRole::Admin && $newRole !== OrganizationRole::Admin) {
                $adminCount = $locked->users()
                    ->wherePivot('role', OrganizationRole::Admin->value)
                    ->count();

                if ($adminCount <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'Cannot demote the last admin.',
                    ]);
                }
            }

            $locked->users()->updateExistingPivot($member->id, [
                'role' => $newRole->value,
            ]);

            AuditLog::record(
                $locked,
                'organization.member.role_changed',
                $actor,
                $locked,
                [
                    'member_id' => $member->id,
                    'from' => $currentRole->value,
                    'to' => $newRole->value,
                ],
            );
        });
    }
}
