<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SalesFollowupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public string $stage,
    ) {}

    public function build(): self
    {
        return $this->subject($this->subjectFor($this->stage))
            ->view('emails.sales.followup')
            ->with([
                'lead' => $this->lead,
                'stage' => $this->stage,
                'headline' => $this->headlineFor($this->stage),
                'body' => $this->bodyFor($this->stage),
                'ctaUrl' => $this->ctaUrlFor($this->stage),
                'ctaLabel' => $this->ctaLabelFor($this->stage),
            ]);
    }

    protected function subjectFor(string $stage): string
    {
        return match ($stage) {
            Lead::FOLLOWUP_DAY_0 => 'Thanks for chatting with BarangayBooks',
            Lead::FOLLOWUP_DAY_3 => 'Still thinking about BarangayBooks?',
            Lead::FOLLOWUP_DAY_7 => 'A quick 15-min demo — would that help?',
            default => 'BarangayBooks follow-up',
        };
    }

    protected function headlineFor(string $stage): string
    {
        return match ($stage) {
            Lead::FOLLOWUP_DAY_0 => 'Glad you reached out',
            Lead::FOLLOWUP_DAY_3 => 'Quick follow-up',
            Lead::FOLLOWUP_DAY_7 => 'Let\'s get on a short call',
            default => 'Following up',
        };
    }

    protected function bodyFor(string $stage): string
    {
        return match ($stage) {
            Lead::FOLLOWUP_DAY_0 => 'Thanks for chatting with the BarangayBooks sales assistant. We put together a short overview based on what you told us. Browse the link below whenever you have a minute.',
            Lead::FOLLOWUP_DAY_3 => 'You spoke with us a couple days ago about HOA dues collection. Want to see how the typical setup looks end-to-end? We recorded a 5-minute walkthrough you can watch on your own time.',
            Lead::FOLLOWUP_DAY_7 => 'Some HOAs prefer to ask their questions live. If a 15-minute call would help, pick any slot — no slides, just a working demo with your HOA size in mind.',
            default => 'Following up on your BarangayBooks inquiry.',
        };
    }

    protected function ctaLabelFor(string $stage): string
    {
        return match ($stage) {
            Lead::FOLLOWUP_DAY_0 => 'See the product overview',
            Lead::FOLLOWUP_DAY_3 => 'Watch the 5-minute walkthrough',
            Lead::FOLLOWUP_DAY_7 => 'Book a 15-minute call',
            default => 'Visit BarangayBooks',
        };
    }

    protected function ctaUrlFor(string $stage): string
    {
        return match ($stage) {
            Lead::FOLLOWUP_DAY_0 => url('/#features'),
            Lead::FOLLOWUP_DAY_3 => url('/#how'),
            Lead::FOLLOWUP_DAY_7 => 'mailto:hello@barangaybooks.app?subject=Book%20demo',
            default => url('/'),
        };
    }
}
