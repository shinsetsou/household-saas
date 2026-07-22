<?php

namespace Tests\Feature;

use App\Providers\SentryServiceProvider;
use App\Support\SentryPiiScrubber;
use Sentry\Sentry;
use Tests\TestCase;

class SentryServiceProviderTest extends TestCase
{
    public function test_scrubber_is_registered_as_singleton(): void
    {
        $a = $this->app->make(SentryPiiScrubber::class);
        $b = $this->app->make(SentryPiiScrubber::class);

        $this->assertSame($a, $b);
    }

    public function test_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(SentryServiceProvider::class));
    }

    public function test_provider_does_not_throw_when_sentry_sdk_is_absent(): void
    {
        if (class_exists(Sentry::class)) {
            $this->markTestSkipped('Sentry SDK is installed; skipping absence test.');
        }

        $provider = new SentryServiceProvider($this->app);
        $provider->boot();

        $this->assertTrue(true);
    }

    public function test_provider_does_not_throw_when_dsn_is_unset(): void
    {
        if (! class_exists(Sentry::class)) {
            $this->markTestSkipped('Sentry SDK is not installed; skipping DSN test.');
        }

        config()->set('sentry.dsn', null);

        $provider = new SentryServiceProvider($this->app);
        $provider->boot();

        $this->assertTrue(true);
    }
}
