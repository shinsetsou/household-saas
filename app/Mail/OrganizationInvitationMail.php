<?php

namespace App\Mail;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class OrganizationInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public OrganizationInvitation $invitation)
    {
    }

    public function build(): self
    {
        return $this->subject('You\'re invited to '.$this->invitation->organization->name.' on BarangayBooks')
            ->view('emails.invitation.sent')
            ->with([
                'invitation' => $this->invitation,
                'acceptUrl' => URL::signedRoute('invitations.show', ['invitation' => $this->invitation->token]),
                'role' => $this->invitation->role(),
            ]);
    }
}