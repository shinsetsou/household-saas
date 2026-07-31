<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OrganizationInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordSetController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.set-password', [
            'email' => $request->user()->email,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->password = Hash::make($validated['password']);
        $user->force_change_password = false;
        $user->save();

        $request->session()->regenerate();

        if ($intendedToken = $request->session()->pull('invitation.intended_token')) {
            $invitation = OrganizationInvitation::where('token', $intendedToken)->first();

            $request->session()->put('invitation.code_verified_token', $intendedToken);
            $request->session()->put('invitation.code_verified_at', now()->timestamp);

            $signedUrl = $invitation
                ? URL::temporarySignedRoute(
                    'invitations.show',
                    $invitation->expires_at,
                    ['invitation' => $intendedToken, 'c' => $invitation->verification_code],
                )
                : URL::temporarySignedRoute(
                    'invitations.show',
                    now()->addHour(),
                    ['invitation' => $intendedToken],
                );

            return redirect($signedUrl);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
