<?php

namespace App\Listeners;

use App\Events\ContributionCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        $contribution = $event->contribution;

        // Send email to the household contact or organization admin
        Mail::to('admin@example.com')->send(new ContributionAdded($contribution));
    }
}
