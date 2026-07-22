<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationRole
{
    public function handle(Request $request, Closure $next, string ...$allowed): Response
    {
        $organization = $request->route('organization');
        $user = $request->user();

        if (! $organization instanceof Organization || ! $user) {
            abort(403);
        }

        $role = $organization->roleFor($user);

        if (! $role) {
            abort(403);
        }

        $allowedRoles = array_map(fn ($r) => OrganizationRole::from($r), $allowed);

        if (! in_array($role, $allowedRoles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
