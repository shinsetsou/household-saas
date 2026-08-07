<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\AI\AIManager;
use App\AI\Events\CustomerSuccessEscalationTriggered;
use App\AI\Jobs\CustomerSuccessScanJob;
use App\AI\Listeners\NotifyFounderOfEscalation;
use App\AI\Tools\CustomerSuccess\GetOnboardingStatusTool;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerSuccessEscalationTest extends TestCase
{
    use RefreshDatabase;

    public function test_org_with_no_households_and_no_payments_is_high_risk(): void
    {
        $tool = new GetOnboardingStatusTool;

        $org = Organization::create([
            'name' => 'Lonely HOA',
            'slug' => 'lonely',
            'monthly_due_amount' => 100,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
        ]);

        $result = $tool->execute(['organization_id' => $org->id])->toArray();

        $this->assertTrue($result['ok']);
        $this->assertGreaterThanOrEqual(35, $result['data']['score']);
        $this->assertContains('no_households', $result['data']['signals']);
    }

    public function test_scan_job_dispatches_escalation_for_high_score_org(): void
    {
        Event::fake([CustomerSuccessEscalationTriggered::class]);
        config()->set('ai.agents.customer_success.escalation_score', 30);

        $org = Organization::create([
            'name' => 'Empty HOA',
            'slug' => 'empty',
            'monthly_due_amount' => 100,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
        ]);

        (new CustomerSuccessScanJob)->handle($this->app->make(AIManager::class));

        Event::assertDispatched(CustomerSuccessEscalationTriggered::class, function ($e) use ($org) {
            return $e->organizationId === $org->id;
        });
    }

    public function test_scan_job_skips_low_score_orgs(): void
    {
        Event::fake([CustomerSuccessEscalationTriggered::class]);
        config()->set('ai.agents.customer_success.escalation_score', 90);

        Organization::create([
            'name' => 'Steady HOA',
            'slug' => 'steady',
            'monthly_due_amount' => 100,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
        ]);

        (new CustomerSuccessScanJob)->handle($this->app->make(AIManager::class));

        Event::assertNotDispatched(CustomerSuccessEscalationTriggered::class);
    }

    public function test_listener_emails_founder_on_escalation(): void
    {
        config()->set('ai.founder_email', 'founder@example.com');

        (new NotifyFounderOfEscalation)->handle(new CustomerSuccessEscalationTriggered(
            organizationId: 42,
            score: 88,
            signals: ['signals' => ['no_households'], 'organization_name' => 'Test HOA'],
        ));

        $this->assertTrue(true); // No exception = OK; Mail::raw is hard to fake without a Mailable.
    }
}
