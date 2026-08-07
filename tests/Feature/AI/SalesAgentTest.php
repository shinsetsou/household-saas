<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\AI\AIManager;
use App\AI\Jobs\SendSalesFollowupEmail;
use App\AI\Providers\FakeProvider;
use App\AI\Tools\Sales\CaptureEmailTool;
use App\AI\Tools\Sales\HandoffToHumanTool;
use App\AI\Tools\Sales\RecommendPlanTool;
use App\Models\AiCallLog;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\SalesConversationMessage;
use App\Services\LeadCaptureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SalesAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_agent_returns_plans_via_tool(): void
    {
        Plan::create([
            'slug' => 'community',
            'name' => 'Community',
            'description' => 'Free trial.',
            'price_cents' => 0,
            'currency' => 'PHP',
            'interval' => 'month',
            'max_households' => 20,
            'csv_import' => false,
            'api_access' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Plan::create([
            'slug' => 'standard',
            'name' => 'Standard',
            'description' => 'Most popular.',
            'price_cents' => 99000,
            'currency' => 'PHP',
            'interval' => 'month',
            'max_households' => 100,
            'csv_import' => true,
            'api_access' => false,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $tool = new RecommendPlanTool;
        $result = $tool->execute([])->toArray();

        $this->assertTrue($result['ok']);
        $this->assertCount(2, $result['data']['plans']);
        $this->assertSame('community', $result['data']['plans'][0]['slug']);
    }

    public function test_capture_email_creates_lead_row(): void
    {
        Bus::fake();

        $tool = new CaptureEmailTool(app(LeadCaptureService::class));

        $result = $tool->execute([
            'email' => 'jane@example.com',
            'role' => 'treasurer',
            'organization_size' => 'medium',
        ])->toArray();

        $this->assertTrue($result['ok']);
        $this->assertDatabaseHas('leads', ['email' => 'jane@example.com']);
        $this->assertSame(1, Lead::where('email', 'jane@example.com')->count());

        Bus::assertDispatched(SendSalesFollowupEmail::class, function ($job) {
            return $job->stage === Lead::FOLLOWUP_DAY_0
                && $job->leadId === Lead::first()->id;
        });
    }

    public function test_capture_email_is_idempotent(): void
    {
        $service = app(LeadCaptureService::class);

        $tool = new CaptureEmailTool($service);

        $tool->execute(['email' => 'jane@example.com', 'role' => 'treasurer']);
        $tool->execute(['email' => 'jane@example.com', 'role' => 'president']);

        $this->assertSame(1, Lead::count());
        $lead = Lead::first();
        $this->assertSame('president', $lead->role, 'Role should be updated on second capture');
    }

    public function test_capture_email_rejects_invalid(): void
    {
        $tool = new CaptureEmailTool(app(LeadCaptureService::class));

        $result = $tool->execute(['email' => 'not-an-email'])->toArray();

        $this->assertFalse($result['ok']);
        $this->assertSame(0, Lead::count());
    }

    public function test_sales_endpoint_requires_session_id(): void
    {
        config()->set('ai.provider', 'fake');
        $manager = $this->app->make(AIManager::class);
        $manager->fake(new FakeProvider(['default_reply' => 'x']));

        $response = $this->postJson('/api/ai/sales/inquire', [
            'message' => 'How much does it cost?',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['session_id']);
    }

    public function test_sales_endpoint_persists_conversation(): void
    {
        config()->set('ai.provider', 'fake');

        $manager = $this->app->make(AIManager::class);
        $provider = new FakeProvider(['default_reply' => 'Thanks!']);
        $manager->fake($provider);

        $sessionId = 'sales-test-session-'.uniqid();

        $this->postJson('/api/ai/sales/inquire', [
            'session_id' => $sessionId,
            'message' => 'How much does it cost?',
        ])->assertOk();

        $this->postJson('/api/ai/sales/inquire', [
            'session_id' => $sessionId,
            'message' => 'And what about the Standard plan?',
        ])->assertOk();

        $this->assertDatabaseHas('sales_conversations', ['session_id' => $sessionId]);
        $this->assertSame(4, SalesConversationMessage::count());
        $this->assertSame(2, AiCallLog::where('agent', 'sales')->count());
    }

    public function test_sales_endpoint_returns_reply(): void
    {
        config()->set('ai.provider', 'fake');

        $manager = $this->app->make(AIManager::class);
        $provider = new FakeProvider(['default_reply' => 'Thanks for reaching out!']);
        $manager->fake($provider);

        $response = $this->postJson('/api/ai/sales/inquire', [
            'session_id' => 'sales-endpoint-session',
            'message' => 'How much does it cost?',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => 'Thanks for reaching out!']);
        $this->assertSame(1, AiCallLog::where('agent', 'sales')->count());
    }

    public function test_sales_agent_registers_all_five_tools(): void
    {
        $manager = $this->app->make(AIManager::class);
        $agent = $manager->agent('sales');
        $request = $agent->task('hello')->buildRequest();

        $this->assertCount(5, $request->tools);
    }

    public function test_handoff_to_human_fires_event(): void
    {
        Event::fake(['sales.handoff_requested']);

        $tool = new HandoffToHumanTool(app(LeadCaptureService::class));
        $tool->execute([
            'email' => 'jane@example.com',
            'reason' => 'complex-hoa',
            'summary' => 'Wants custom quote.',
        ]);

        Event::assertDispatched('sales.handoff_requested', function ($event, $payload) {
            return $payload['email'] === 'jane@example.com'
                && $payload['reason'] === 'complex-hoa';
        });
    }
}
