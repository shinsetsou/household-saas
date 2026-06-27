<?php

namespace App\Http\Controllers;

use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function __construct(protected InvitationService $invitations) {}

    public function show(Request $request, OrganizationInvitation $invitation): View|RedirectResponse
    {
        if (! $invitation->isPending()) {
            return view('invitations.invalid', ['invitation' => $invitation]);
        }

        $user = $request->user();

        if (! $user) {
            $existing = User::whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])->first();

            if (! $existing) {
                $existing = User::create([
                    'name' => Str::before($invitation->email, '@'),
                    'email' => $invitation->email,
                    'password' => Str::random(64),
                    'force_change_password' => true,
                    'email_verified_at' => now(),
                    'invitation_origin_org_slug' => $invitation->organization->slug,
                    'invitation_origin_role' => $invitation->role,
                    'invitation_origin_inviter_email' => $invitation->inviter?->email,
                ]);
            } else {
                $existing->force_change_password = true;
                $existing->email_verified_at = now();

                if (! $existing->wasInvited()) {
                    $existing->invitation_origin_org_slug = $invitation->organization->slug;
                    $existing->invitation_origin_role = $invitation->role;
                    $existing->invitation_origin_inviter_email = $invitation->inviter?->email;
                    $existing->invitation_origin_revoked_at = null;
                }

                $existing->save();
            }

            Auth::login($existing);
            $request->session()->regenerate();

            session(['invitation.intended_token' => $invitation->token]);

            return redirect()->route('password.set.show')->with(
                'info',
                'Welcome! Set a password to finish joining '.$invitation->organization->name.'.',
            );
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            throw ValidationException::withMessages([
                'email' => 'This invitation was sent to '.$invitation->email.'. Sign out and sign in with that email to accept.',
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        session(['invitation.intended_token' => $invitation->token]);

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

    public function locked(Request $request): View
    {
        return view('invitations.locked', [
            'user' => $request->user(),
        ]);
    }
}
