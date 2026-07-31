<?php

namespace App\Http\Controllers;

use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
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
            return view('invitations.claim', [
                'invitation' => $invitation,
                'role' => $invitation->role(),
            ]);
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            throw ValidationException::withMessages([
                'email' => 'This invitation was sent to '.$invitation->email.'. Sign out and sign in with that email to accept.',
            ]);
        }

        if (! $invitation->matchesVerificationCode((string) $request->query('c', ''))) {
            return view('invitations.claim', [
                'invitation' => $invitation,
                'role' => $invitation->role(),
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

    public function claim(Request $request, OrganizationInvitation $invitation): RedirectResponse
    {
        if (! $invitation->isPending()) {
            return redirect()->route('invitations.invalid', ['invitation' => $invitation->token]);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $throttleKey = 'invitation-claim:'.strtolower($validated['email']).':'.$invitation->id;

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Please wait a minute and try again.',
            ]);
        }

        if (strtolower($validated['email']) !== strtolower($invitation->email)
            || ! $invitation->matchesVerificationCode($validated['code'])) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'code' => 'That email or code does not match this invitation.',
            ]);
        }

        RateLimiter::clear($throttleKey);

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

        $request->session()->put('invitation.code_verified_token', $invitation->token);
        $request->session()->put('invitation.code_verified_at', now()->timestamp);

        return redirect()->route('password.set.show')->with(
            'info',
            'Email confirmed. Set a password to finish joining '.$invitation->organization->name.'.',
        );
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

        $this->ensureCodeVerifiedRecently($request, $invitation);

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

    protected function ensureCodeVerifiedRecently(Request $request, OrganizationInvitation $invitation): void
    {
        $verifiedAt = (int) $request->session()->get('invitation.code_verified_at', 0);
        $verifiedToken = $request->session()->get('invitation.code_verified_token');

        if ($verifiedToken !== $invitation->token || $verifiedAt < now()->subMinutes(15)->timestamp) {
            throw ValidationException::withMessages([
                'email' => 'For your security, please re-confirm the invite code before accepting. Use the link in your invitation email.',
            ]);
        }
    }
}
