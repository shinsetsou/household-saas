<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MailConfigWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_warns_when_sandbox_smtp_used_in_production(): void
    {
        $this->setEnvironment('production');
        $this->app['config']->set('mail.default', 'smtp');
        $this->app['config']->set('mail.mailers.smtp.host', 'sandbox.smtp.mailtrap.io');

        Log::shouldReceive('error')
            ->once()
            ->with('mail.driver.sandbox_in_production', \Mockery::on(function ($context) {
                return ($context['mailer'] ?? null) === 'smtp'
                    && ($context['host'] ?? null) === 'sandbox.smtp.mailtrap.io'
                    && str_contains($context['fix'] ?? '', 'MAIL_MAILER=mailtrap');
            }));

        $this->invokeWarnOnMisconfiguredMail();
    }

    public function test_does_not_warn_when_sandbox_used_in_local(): void
    {
        $this->setEnvironment('local');
        $this->app['config']->set('mail.default', 'smtp');
        $this->app['config']->set('mail.mailers.smtp.host', 'sandbox.smtp.mailtrap.io');

        Log::shouldReceive('error')->never();
        Log::shouldReceive('warning')->never();

        $this->invokeWarnOnMisconfiguredMail();
    }

    public function test_does_not_warn_when_mailtrap_api_used_in_production(): void
    {
        $this->setEnvironment('production');
        $this->app['config']->set('mail.default', 'mailtrap');
        $this->app['config']->set('mail.mailers.smtp.host', 'sandbox.smtp.mailtrap.io');

        Log::shouldReceive('error')->never();

        $this->invokeWarnOnMisconfiguredMail();
    }

    public function test_does_not_warn_for_real_smtp_host(): void
    {
        $this->setEnvironment('production');
        $this->app['config']->set('mail.default', 'smtp');
        $this->app['config']->set('mail.mailers.smtp.host', 'smtp.postmarkapp.com');

        Log::shouldReceive('error')->never();

        $this->invokeWarnOnMisconfiguredMail();
    }

    protected function setEnvironment(string $env): void
    {
        $this->app['config']->set('app.env', $env);
        $this->app->instance('env', $env);
    }

    protected function invokeWarnOnMisconfiguredMail(): void
    {
        $provider = new AppServiceProvider($this->app);
        $ref = new \ReflectionClass($provider);
        $method = $ref->getMethod('warnOnMisconfiguredMail');
        $method->setAccessible(true);
        $method->invoke($provider);
    }
}
