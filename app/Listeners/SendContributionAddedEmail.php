<?php

namespace App\Listeners;

use App\Events\ContributionCreated;
use App\Mail\ContributionAdded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendContributionAddedEmail implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ContributionCreated $event): void
    {
        //
        $contributionData = $event->contributionData;

        // Send email to the household contact or organization admin
        Mail::to('admin@example.com')->send(new ContributionAdded($contributionData));
    }
}
