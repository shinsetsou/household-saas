<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'organization' => $this->resolveOrganizationContext($request),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        $routeParameters = $request->query('organization')
            ? ['organization' => $request->query('organization')]
            : [];

        return Redirect::route('profile.edit', $routeParameters)->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function resolveOrganizationContext(Request $request): ?Organization
    {
        $user = $request->user();
        $slug = $request->query('organization');

        if ($slug) {
            $organization = $user->organizations()
                ->where('organizations.slug', $slug)
                ->first();

            if ($organization) {
                return $organization;
            }
        }

        return $user->organizations()
            ->latest('organization_user.created_at')
            ->first();
    }
}
