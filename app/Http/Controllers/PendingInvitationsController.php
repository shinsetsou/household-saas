<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Models\OrganizationInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PendingInvitationsController extends Controller
{
    public function index(Request $request): View
    {
        $invitations = $request->user()
            ->pendingInvitations()
            ->with('organization', 'inviter')
            ->latest('created_at')
            ->get()
            ->map(function (OrganizationInvitation $invitation) {
                return [
                    'invitation' => $invitation,
                    'role' => $invitation->role(),
                    'signed_url' => URL::signedRoute('invitations.show', ['invitation' => $invitation->token]),
                    'inviter' => $invitation->inviter,
                    'organization' => $invitation->organization,
                    'expires_in_days' => max(0, (int) now()->diffInDays($invitation->expires_at, false)),
                ];
            });

        return view('invitations.pending', [
            'invitations' => $invitations,
            'roles' => OrganizationRole::cases(),
        ]);
    }

    public function destroy(Request $request, OrganizationInvitation $invitation): RedirectResponse
    {
        abort_unless(strtolower($invitation->email) === strtolower($request->user()->email), 403);

        if ($invitation->isPending()) {
            $invitation->update(['revoked_at' => now()]);
        }

        return redirect()->route('invitations.index')->with('info', 'Invitation declined.');
    }
}
