<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Mailtrap\Transport\MailtrapApiTransport;
use Tests\TestCase;

class MailtrapMailerTest extends TestCase
{
    public function test_mailtrap_mailer_resolves_to_api_transport(): void
    {
        if (! class_exists(MailtrapApiTransport::class)) {
            $this->markTestSkipped('symfony/mailtrap-mailer not installed.');
        }

        config([
            'mail.default' => 'mailtrap',
            'mail.mailers.mailtrap.dsn' => 'mailtrap+api://fake@default',
        ]);

        $mailer = Mail::mailer('mailtrap');
        $transport = $mailer->getSymfonyTransport();

        $this->assertInstanceOf(MailtrapApiTransport::class, $transport);
    }

    public function test_mailtrap_mailer_resolves_sandbox_to_api_transport_with_inbox(): void
    {
        if (! class_exists(MailtrapApiTransport::class)) {
            $this->markTestSkipped('symfony/mailtrap-mailer not installed.');
        }

        config([
            'mail.default' => 'mailtrap',
            'mail.mailers.mailtrap.dsn' => 'mailtrap+sandbox://fake@default?inboxId=12345',
        ]);

        $transport = Mail::mailer('mailtrap')->getSymfonyTransport();

        $this->assertInstanceOf(MailtrapApiTransport::class, $transport);
    }

    public function test_missing_dsn_throws_clear_error(): void
    {
        config([
            'mail.default' => 'mailtrap',
            'mail.mailers.mailtrap.dsn' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MAIL_MAILTRAP_DSN');

        Mail::mailer('mailtrap');
    }
}
