<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Mailtrap\Bridge\Laravel\MailtrapApiProvider;
use Mailtrap\Bridge\Transport\MailtrapApiTransport;
use Tests\TestCase;

class MailtrapMailerTest extends TestCase
{
    public function test_mailtrap_mailer_resolves_to_api_transport(): void
    {
        if (! class_exists(MailtrapApiTransport::class)) {
            $this->markTestSkipped('railsware/mailtrap-php not installed.');
        }

        config([
            'mail.default' => 'mailtrap',
            'services.mailtrap.host' => 'send.api.mailtrap.io',
            'services.mailtrap.apiKey' => 'fake',
        ]);

        $mailer = Mail::mailer('mailtrap');
        $transport = $mailer->getSymfonyTransport();

        $this->assertInstanceOf(MailtrapApiTransport::class, $transport);
    }

    public function test_mailtrap_mailer_resolves_sandbox_host(): void
    {
        if (! class_exists(MailtrapApiTransport::class)) {
            $this->markTestSkipped('railsware/mailtrap-php not installed.');
        }

        config([
            'mail.default' => 'mailtrap',
            'services.mailtrap.host' => 'sandbox.api.mailtrap.io',
            'services.mailtrap.apiKey' => 'fake',
            'services.mailtrap.inboxId' => '12345',
        ]);

        $transport = Mail::mailer('mailtrap')->getSymfonyTransport();

        $this->assertInstanceOf(MailtrapApiTransport::class, $transport);
    }

    public function test_mailer_is_registered_via_package_service_provider(): void
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(MailtrapApiProvider::class, $providers);
    }
}
