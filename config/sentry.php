<?php

use App\Support\SentryPiiScrubber;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return [

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    'environment' => env('APP_ENV', 'production'),

    'release' => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Sample rates
    |--------------------------------------------------------------------------
    | errors_sample_rate: 1.0 = report every error.
    | traces_sample_rate: 0.2 = sample 20% of performance transactions.
    | profiles_sample_rate: 0.2 = sample 20% of profiles within traced txns.
    */

    'errors_sample_rate' => (float) env('SENTRY_ERRORS_SAMPLE_RATE', 1.0),

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.2),

    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.2),

    /*
    |--------------------------------------------------------------------------
    | PII handling
    |--------------------------------------------------------------------------
    | send_default_pii=false: don't auto-attach user email/IP/user-agent.
    | The SentryPiiScrubber class is registered as a Sentry event processor
    | in AppServiceProvider to strip password/token/card fields from
    | breadcrumbs, request bodies, and exception contexts.
    */

    'send_default_pii' => false,

    'scrubber' => SentryPiiScrubber::class,

    /*
    |--------------------------------------------------------------------------
    | Ignored exceptions
    |--------------------------------------------------------------------------
    | Common 4xx classes we don't want to flood Sentry with.
    */

    'ignore_exceptions' => [
        AuthenticationException::class,
        ValidationException::class,
        HttpException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored transactions
    |--------------------------------------------------------------------------
    | Endpoints we don't want to count toward the trace quota.
    */

    'ignore_transactions' => [
        '/up',
        '/billing/webhook',
    ],

];
