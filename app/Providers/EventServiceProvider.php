<?php

namespace App\Providers;

use App\AI\Events\AgentFailed;
use App\AI\Events\CustomerSuccessEscalationTriggered;
use App\AI\Events\FounderBriefGenerated;
use App\AI\Listeners\NotifyFounderOfEscalation;
use App\AI\Listeners\SendAgentFailureAlert;
use App\AI\Listeners\SendFounderBriefEmail;
use App\Events\ContributionCreated;
use App\Events\OrganizationInvitationSent;
use App\Listeners\SendContributionAddedEmail;
use App\Listeners\SendOrganizationInvitationEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ContributionCreated::class => [
            SendContributionAddedEmail::class,
        ],
        OrganizationInvitationSent::class => [
            SendOrganizationInvitationEmail::class,
        ],
        FounderBriefGenerated::class => [
            SendFounderBriefEmail::class,
        ],
        AgentFailed::class => [
            SendAgentFailureAlert::class,
        ],
        CustomerSuccessEscalationTriggered::class => [
            NotifyFounderOfEscalation::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot() {}
}
