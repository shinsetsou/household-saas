<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');
        $user = $request->user();

        if (! $organization instanceof Organization || ! $user) {
            abort(403);
        }

        $isAdmin = $user->organizations()
            ->where('organizations.id', $organization->id)
            ->wherePivot('role', 'admin')
            ->exists();

        if (! $isAdmin) {
            abort(403);
        }

        return $next($request);
    }
}
