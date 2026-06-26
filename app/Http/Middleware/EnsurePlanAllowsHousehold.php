<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanAllowsHousehold
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        if (! $organization instanceof Organization) {
            return $next($request);
        }

        if ($organization->hasReachedHouseholdLimit()) {
            $limit = $organization->householdLimit();

            $message = $organization->isOnTrial()
                ? "Your free trial allows up to {$limit} households. Upgrade to a paid plan to add more."
                : "Your current plan allows up to {$limit} households. Upgrade to add more.";

            return redirect()
                ->route('organizations.settings.billing.show', $organization->slug)
                ->with('error', $message);
        }

        return $next($request);
    }
}