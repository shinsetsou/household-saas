<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $latest = $user->organizations()->latest('organization_user.created_at')->first();

        if ($latest === null) {
            return redirect()->route('onboarding.organization.create');
        }

        return redirect()->route('organizations.index', $latest);
    }
}
