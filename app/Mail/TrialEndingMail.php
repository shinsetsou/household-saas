<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrialEndingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public int $daysRemaining,
    ) {
    }

    public function build(): self
    {
        $urgency = $this->daysRemaining <= 1 ? 'tomorrow' : "in {$this->daysRemaining} days";

        return $this->subject("Your BarangayBooks trial ends {$urgency}")
            ->view('emails.trial.ending')
            ->with([
                'org' => $this->organization,
                'daysRemaining' => $this->daysRemaining,
                'urgency' => $urgency,
            ]);
    }
}