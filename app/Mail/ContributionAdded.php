<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContributionAdded extends Mailable implements ShouldQueue
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

        if (! empty($this->contributionData['photo'])) {
            $email->attachFromStorageDisk('s3', $this->contributionData['photo']);
        }

        return $email;
    }
}
