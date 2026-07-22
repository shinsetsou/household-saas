<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationActive
{
    /**
     * Routes that remain accessible even when the org is expired —
     * billing is the whole point, so checkout + cancel + logout must work.
     */
    protected array $billingPathSuffixes = [
        'settings/billing',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        if (! $organization instanceof Organization) {
            return $next($request);
        }

        if (! $organization->isExpired()) {
            return $next($request);
        }

        if ($request->method() === 'GET') {
            return $next($request);
        }

        $path = $request->path();

        foreach ($this->billingPathSuffixes as $suffix) {
            if (str_contains($path, $suffix)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your subscription is past due. Please update your billing.',
            ], 402);
        }

        return redirect()
            ->route('organizations.settings.billing.show', $organization->slug)
            ->with('error', 'Your trial has ended. Pick a plan to continue using BarangayBooks.');
    }
}
