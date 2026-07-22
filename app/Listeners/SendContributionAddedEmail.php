<?php

namespace App\Listeners;

use App\Events\ContributionCreated;
use App\Mail\ContributionAdded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

class SendContributionAddedEmail implements ShouldQueue
{
    public function handle(ContributionCreated $event): void
    {
        try {
            Mail::to('admin@example.com')->send(new ContributionAdded($event->contributionData));
        } catch (TransportException $e) {
            Log::error('Failed to send contribution-added email', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
