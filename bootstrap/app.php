<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\ForcePasswordChange;
use App\Providers\SentryServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        SentryServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // Trust X-Forwarded-Proto (and AWS-ELB chain) from upstream proxies so
        // request()->isSecure() returns true and url() generates https URLs.
        //
        // We deliberately do NOT trust `X-Forwarded-For` or `X-Forwarded-Host`:
        //   * X-Forwarded-For controls `request()->ip()`, which the throttle
        //     middleware uses as its key. Trusting it would let an attacker
        //     rotate headers to bypass the public `throttle:5,1` /
        //     `throttle:10,1` limiters on /households and /contributions.
        //   * X-Forwarded-Host drives `request()->getHost()`. Trusting it
        //     would let an attacker spoof the host header and affect
        //     cache keys, signed-URL signing, and password-reset email
        //     links.
        //
        // Render / Railway / Fly document `trustProxies(at: '*')` as the
        // recommended pattern because their proxy IPs rotate, but they
        // rely on the application to scope WHICH headers to honor —
        // this configuration does exactly that.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->validateCsrfTokens(except: [
            'billing/webhook',
        ]);

        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
            'force.password.change' => ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
