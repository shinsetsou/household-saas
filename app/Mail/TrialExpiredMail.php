<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrialExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Organization $organization)
    {
    }

    public function build(): self
    {
        return $this->subject('Your BarangayBooks trial has ended')
            ->view('emails.trial.expired')
            ->with(['org' => $this->organization]);
    }
}