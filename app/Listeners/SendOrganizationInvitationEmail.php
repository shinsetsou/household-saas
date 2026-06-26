<?php

namespace App\Listeners;

use App\Events\OrganizationInvitationSent;
use App\Mail\OrganizationInvitationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrganizationInvitationEmail implements ShouldQueue
{
    public function handle(OrganizationInvitationSent $event): void
    {
        Mail::to($event->invitation->email)
            ->send(new OrganizationInvitationMail($event->invitation));
    }
}