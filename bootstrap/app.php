<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\ForcePasswordChange;
use App\Providers\SentryServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        // Trust X-Forwarded-Proto / X-Forwarded-For from Railway's edge proxy
        // so request()->getScheme() returns 'https' and url() generates https URLs.
        $middleware->trustProxies(at: '*');

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
