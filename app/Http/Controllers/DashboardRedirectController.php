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

        if ($latest !== null) {
            return redirect()->route('organizations.index', $latest);
        }

        if ($user->pendingInvitations()->isNotEmpty()) {
            return redirect()->route('invitations.index');
        }

        if ($user->isInviteLocked()) {
            return redirect()->route('invitations.locked');
        }

        return redirect()->route('onboarding.organization.create');
    }
}
