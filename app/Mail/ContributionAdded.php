<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

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
        if (! empty($this->contributionData['photo']) && Storage::disk('public')->exists($this->contributionData['photo'])) {
            $email->attachFromStorageDisk('public', $this->contributionData['photo']);
        }

        return $email;
    }
}
