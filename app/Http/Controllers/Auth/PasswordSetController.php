<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
            return redirect(URL::signedRoute('invitations.show', ['invitation' => $intendedToken]));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
