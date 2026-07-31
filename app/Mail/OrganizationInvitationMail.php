<?php

namespace App\Mail;

use App\Models\OrganizationInvitation;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\URL;

class OrganizationInvitationMail extends Mailable
{
    public function __construct(public OrganizationInvitation $invitation) {}

    public function build(): self
    {
        $expiresAt = $this->invitation->expires_at;

        $acceptUrl = URL::temporarySignedRoute(
            'invitations.show',
            $expiresAt,
            ['invitation' => $this->invitation->token],
        );

        $claimUrl = URL::temporarySignedRoute(
            'invitations.claim',
            $expiresAt,
            ['invitation' => $this->invitation->token],
        );

        return $this->subject('You\'re invited to '.$this->invitation->organization->name.' on BarangayBooks')
            ->view('emails.invitation.sent')
            ->with([
                'invitation' => $this->invitation,
                'acceptUrl' => $acceptUrl,
                'claimUrl' => $claimUrl,
                'verificationCode' => $this->invitation->verification_code,
                'role' => $this->invitation->role(),
            ]);
    }
}
