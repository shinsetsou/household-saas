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
    }

    public function resend(OrganizationInvitation $invitation, User $actor): OrganizationInvitation
    {
        abort_unless($invitation->isPending(), 404);

        $invitation->update([
            'expires_at' => now()->addDays(OrganizationInvitation::INVITATION_TTL_DAYS),
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
        $role = $organization->roleFor($member);

        if (! $role) {
            abort(404);
        }

        if ($role === OrganizationRole::Admin) {
            $adminCount = $organization->users()
                ->wherePivot('role', OrganizationRole::Admin->value)
                ->count();

            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'member' => 'Cannot remove the last admin. Promote another member to admin first.',
                ]);
            }
        }

        $organization->users()->detach($member->id);

        AuditLog::record(
            $organization,
            'organization.member.removed',
            $actor,
            $organization,
            [
                'member_id' => $member->id,
                'member_email' => $member->email,
                'role' => $role->value,
            ],
        );
    }

    public function changeRole(Organization $organization, User $member, OrganizationRole $newRole, User $actor): void
    {
        $currentRole = $organization->roleFor($member);

        if (! $currentRole) {
            abort(404);
        }

        if ($currentRole === OrganizationRole::Admin && $newRole !== OrganizationRole::Admin) {
            $adminCount = $organization->users()
                ->wherePivot('role', OrganizationRole::Admin->value)
                ->count();

            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'Cannot demote the last admin.',
                ]);
            }
        }

        $organization->users()->updateExistingPivot($member->id, [
            'role' => $newRole->value,
        ]);

        AuditLog::record(
            $organization,
            'organization.member.role_changed',
            $actor,
            $organization,
            [
                'member_id' => $member->id,
                'from' => $currentRole->value,
                'to' => $newRole->value,
            ],
        );
    }
}