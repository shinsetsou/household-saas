<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $sessionOrg = $request->session()->get('onboarding.organization_id');

        if ($user && $sessionOrg === null && $user->organizations()->exists()) {
            $latest = $user->organizations()->latest('organization_user.created_at')->first();

            return redirect()->route('organizations.index', $latest);
        }

        if ($user && $sessionOrg === null && ! $user->organizations()->exists()) {
            if ($user->pendingInvitations()->isNotEmpty()) {
                return redirect()->route('invitations.index');
            }

            if ($user->isInviteLocked()) {
                return redirect()->route('invitations.locked');
            }

            return redirect()->route('onboarding.organization.create');
        }

        return $next($request);
    }
}
