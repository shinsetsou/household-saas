<?php

namespace Tests\Unit\Listeners;

use App\Events\OrganizationInvitationSent;
use App\Listeners\SendEmailVerificationNotification;
use App\Listeners\SendOrganizationInvitationEmail;
use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class MailFailureListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_listener_swallows_transport_exception(): void
    {
        Log::spy();

$user = Mockery::mock(User::class.'[sendEmailVerificationNotification,hasVerifiedEmail]');
        $user->id = 1;
        $user->shouldReceive('hasVerifiedEmail')->andReturn(false);
        $user->shouldReceive('sendEmailVerificationNotification')
            ->andThrow(new TransportException('Connection refused'));

        $listener = new SendEmailVerificationNotification;
        $listener->handle(new Registered($user));

        Log::shouldHaveReceived('error')->once();
    }

    public function test_invitation_listener_swallows_transport_exception(): void
    {
        Log::spy();
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andThrow(new TransportException('Connection refused'));

        $invitation = new OrganizationInvitation([
            'organization_id' => 1,
            'email' => 'invitee@example.com',
            'role' => 'viewer',
            'invited_by' => 1,
        ]);
        $invitation->id = 1;

        $listener = new SendOrganizationInvitationEmail;
        $listener->handle(new OrganizationInvitationSent($invitation));

        Log::shouldHaveReceived('error')->once();
    }

    public function test_invitation_listener_succeeds_normally(): void
    {
        Log::spy();
        Mail::fake();

        $invitation = new OrganizationInvitation([
            'organization_id' => 1,
            'email' => 'invitee@example.com',
            'role' => 'viewer',
            'invited_by' => 1,
        ]);
        $invitation->id = 1;

        $listener = new SendOrganizationInvitationEmail;
        $listener->handle(new OrganizationInvitationSent($invitation));

        Mail::assertQueued(OrganizationInvitationMail::class, 0);
        Mail::assertSent(OrganizationInvitationMail::class, 1);
        Log::shouldNotHaveReceived('error');
    }
}