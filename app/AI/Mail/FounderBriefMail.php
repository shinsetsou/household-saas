<?php

declare(strict_types=1);

namespace App\AI\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FounderBriefMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly array $payload,
        public readonly string $dispatchId,
    ) {}

    public function build(): self
    {
        return $this->subject('BarangayBooks — Daily Founder Brief')
            ->view('ai.mail.founder-brief-html')
            ->text('ai.mail.founder-brief-text', [
                'payload' => $this->payload,
                'dispatchId' => $this->dispatchId,
                'date' => now()->format('F j, Y'),
            ])
            ->with([
                'payload' => $this->payload,
                'dispatchId' => $this->dispatchId,
                'date' => now()->format('F j, Y'),
            ]);
    }
}
