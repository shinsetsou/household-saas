<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class OrganizationInvitationObserver
{
    public function updating(OrganizationInvitation $invitation): void
    {
        if ($invitation->isDirty('revoked_at') && $invitation->revoked_at !== null) {
            $original = $invitation->getOriginal('revoked_at');

            if ($original === null) {
                $context = [
                    'invitation_id' => $invitation->id,
                    'organization_id' => $invitation->organization_id,
                    'email' => $invitation->email,
                    'revoked_at' => $invitation->revoked_at->toIso8601String(),
                    'route' => Request::route()?->getName(),
                    'method' => Request::method(),
                    'path' => Request::path(),
                    'ip' => Request::ip(),
                    'user_id' => Request::user()?->id,
                    'user_email' => Request::user()?->email,
                ];

                Log::channel('stack')->warning('invitation.revoked', $context);

                AuditLog::record(
                    $invitation->organization,
                    'organization.member.invitation_revoked',
                    Request::user(),
                    $invitation->organization,
                    array_merge($context, [
                        'source' => 'observer',
                    ]),
                );

                $this->lockOrphanedUserForInvite($invitation);
            }
        }

        if ($invitation->isDirty('accepted_at') && $invitation->accepted_at !== null) {
            $original = $invitation->getOriginal('accepted_at');

            if ($original === null) {
                Log::channel('stack')->info('invitation.accepted_via_db', [
                    'invitation_id' => $invitation->id,
                    'organization_id' => $invitation->organization_id,
                    'email' => $invitation->email,
                    'route' => Request::route()?->getName(),
                    'method' => Request::method(),
                    'path' => Request::path(),
                    'user_id' => Request::user()?->id,
                ]);
            }
        }
    }

    /**
     * Defense in depth — if a user was auto-created from this invite and still
     * has no org membership, mark them as invite-locked so the dashboard
     * redirects them to the explainer page instead of the onboarding wizard.
     * Idempotent; no-op if the user already belongs to any org.
     */
    protected function lockOrphanedUserForInvite(OrganizationInvitation $invitation): void
    {
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])->first();

        if (! $user || $user->organizations()->exists()) {
            return;
        }

        $user->lockInviteOrigin();
    }
}
