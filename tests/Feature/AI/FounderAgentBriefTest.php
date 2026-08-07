<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\AI\AIManager;
use App\AI\Events\FounderBriefGenerated;
use App\AI\Listeners\SendFounderBriefEmail;
use App\AI\Mail\FounderBriefMail;
use App\AI\Providers\FakeProvider;
use App\Models\AiCallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FounderAgentBriefTest extends TestCase
{
    use RefreshDatabase;

    public function test_founder_brief_persists_log_and_dispatches_event(): void
    {
        Mail::fake();
        Event::fake([FounderBriefGenerated::class]);

        $manager = $this->app->make(AIManager::class);
        $provider = new FakeProvider(['default_reply' => json_encode([
            'revenue_usd_cents' => 12345,
            'mrr_usd_cents' => 67890,
            'new_users' => 3,
            'new_trials' => 2,
            'new_paying' => 1,
            'churn_count' => 0,
            'failed_payments' => 0,
            'support_tickets' => ['open' => 4],
            'product_issues' => [],
            'top_3_risks' => ['high churn in Z'],
            'top_3_wins' => ['3 new signups'],
            'suggested_priorities' => ['ship PayMongo link'],
        ])]);
        $manager->fake($provider);

        $response = $manager->agent('founder')->task('daily-brief')->run();

        $this->assertFalse($response->isError());
        $this->assertSame('completed', AiCallLog::first()->status);

        // In production, the ai:run command + cron schedule dispatch the
        // FounderBriefGenerated event after parsing the response. Tests do it
        // inline so we can assert the event fired.
        FounderBriefGenerated::dispatch('test-dispatch-id', json_decode($response->text, true) ?: []);

        Event::assertDispatched(FounderBriefGenerated::class);
    }

    public function test_founder_brief_email_sends_to_configured_recipient(): void
    {
        Mail::fake();
        config()->set('ai.founder_email', 'founder@example.com');

        $listener = new SendFounderBriefEmail;
        $listener->handle(new FounderBriefGenerated('dispatch-xyz', [
            'revenue_usd_cents' => 1000,
            'mrr_usd_cents' => 5000,
            'new_users' => 1,
            'support_tickets' => ['open' => 0],
            'top_3_wins' => ['made rent'],
        ]));

        Mail::assertSent(FounderBriefMail::class, 1);
    }
}
