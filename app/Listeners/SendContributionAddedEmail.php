<?php

namespace App\Listeners;

use App\Mail\ContributionAdded;
use App\Events\ContributionCreated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendContributionAddedEmail
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
