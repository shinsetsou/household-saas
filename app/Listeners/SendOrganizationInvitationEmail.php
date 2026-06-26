<?php

namespace App\Listeners;

use App\Events\OrganizationInvitationSent;
use App\Mail\OrganizationInvitationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

class SendOrganizationInvitationEmail implements ShouldQueue
{
    public function handle(OrganizationInvitationSent $event): void
    {
        try {
            Mail::to($event->invitation->email)
                ->send(new OrganizationInvitationMail($event->invitation));
        } catch (TransportException $e) {
            Log::error('Failed to send invitation email', [
                'invitation_id' => $event->invitation->id,
                'email' => $event->invitation->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}