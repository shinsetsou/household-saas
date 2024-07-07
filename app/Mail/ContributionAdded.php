<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContributionAdded extends Mailable
{
    use Queueable, SerializesModels;

    public $contributionData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $contributionData)
    {
        $this->contributionData = $contributionData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email = $this->view('emails.contribution.added')
                      ->with('contributionData', $this->contributionData);

        // Attach the image if it exists
        if (isset($this->contributionData['photo'])) {
            $email->attachFromStorage('public/' . $this->contributionData['photo']);
        }

        return $email;
    }
}
