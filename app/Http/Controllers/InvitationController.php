<?php

namespace App\Http\Controllers;

use App\Models\OrganizationInvitation;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function __construct(protected InvitationService $invitations)
    {
    }

    public function show(Request $request, OrganizationInvitation $invitation): View|RedirectResponse
    {
        if (! $invitation->isPending()) {
            return view('invitations.invalid', ['invitation' => $invitation]);
        }

        $user = $request->user();

        if (! $user) {
            session(['invitation.intended_token' => $invitation->token]);

            return redirect()->route('login', [
                'email' => $invitation->email,
            ]);
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            throw ValidationException::withMessages([
                'email' => 'This invitation was sent to '.$invitation->email.'. Sign out and sign in with that email to accept.',
            ]);
        }

        return view('invitations.show', [
            'invitation' => $invitation,
            'role' => $invitation->role(),
        ]);
    }

    public function accept(Request $request, OrganizationInvitation $invitation): RedirectResponse
    {
        if (! $invitation->isPending()) {
            return redirect()->route('dashboard')->with('error', 'This invitation is no longer valid.');
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $this->invitations->accept($invitation, $user);

        return redirect()
            ->route('organizations.index', $invitation->organization->slug)
            ->with('success', 'You joined '.$invitation->organization->name.'.');
    }

    public function decline(Request $request, OrganizationInvitation $invitation): RedirectResponse
    {
        if (! $invitation->isPending()) {
            return redirect()->route('dashboard');
        }

        $user = $request->user();

        if ($user) {
            $invitation->update(['revoked_at' => now()]);
        }

        return redirect()->route('dashboard')->with('info', 'Invitation declined.');
    }
}