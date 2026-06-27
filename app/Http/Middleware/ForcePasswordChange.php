<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Routes that are reachable while the user still owes us a password set.
     */
    protected array $except = [
        'password/set',
        'password/set/*',
        'logout',
        'verify-email',
        'verify-email/*',
        'email/verification-notification',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'needsPasswordChange') || ! $user->needsPasswordChange()) {
            return $next($request);
        }

        foreach ($this->except as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        return redirect()->route('password.set.show');
    }
}
