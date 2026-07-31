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
        // Trust X-Forwarded-Proto from upstream proxies so request()->isSecure()
        // returns true and url() generates https URLs.
        //
        // We deliberately do NOT trust `X-Forwarded-For`, `X-Forwarded-Host`,
        // `X-Forwarded-Port`, or the AWS-ELB combined bit (which silently
        // bundles X-Forwarded-For in Symfony's TrustProxies):
        //
        //   * X-Forwarded-For controls `request()->ip()`. The throttle
        //     middleware uses it as its key. Trusting it would let an
        //     attacker rotate headers to bypass the public
        //     `throttle:5,1` / `throttle:10,1` limiters on /households
        //     and /contributions.
        //   * X-Forwarded-Host drives `request()->getHost()`. Trusting it
        //     would let an attacker spoof the host header and break signed
        //     URLs, cache keys, and password-reset email links.
        //   * X-Forwarded-Port would let the attacker spoof port detection.
        //
        // Render / Railway / Fly document `trustProxies(at: '*')` because
        // their proxy IPs rotate, but they require the application to scope
        // WHICH headers to honor — this configuration does exactly that.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_PROTO,
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
