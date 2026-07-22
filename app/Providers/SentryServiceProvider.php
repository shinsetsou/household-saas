<?php

namespace App\Providers;

use App\Support\SentryPiiScrubber;
use Illuminate\Support\ServiceProvider;
use Sentry\Sentry;
use Sentry\State\Hub;

class SentryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SentryPiiScrubber::class);
    }

    public function boot(): void
    {
        if (! class_exists(Sentry::class)) {
            return;
        }

        $dsn = (string) config('sentry.dsn');
        if ($dsn === '') {
            return;
        }

        Hub::getCurrent()
            ->getClient()
            ?->getEventFactory()
            ?->registerProcessor($this->app->make(SentryPiiScrubber::class));
    }
}
