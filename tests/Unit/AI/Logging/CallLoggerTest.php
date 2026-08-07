<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Logging;

use App\AI\AgentRequest;
use App\AI\AgentResponse;
use App\AI\Logging\CallLogger;
use App\Models\AiCallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_persists_successful_call(): void
    {
        $logger = new CallLogger;

        $request = new AgentRequest(
            agent: 'founder',
            task: 'brief',
            messages: [['role' => 'user', 'content' => 'go']],
            context: ['foo' => 'bar'],
            dispatchId: 'dispatch-abc',
        );

        $response = new AgentResponse(
            text: 'hello',
            model: 'fake-model',
            provider: 'fake',
            promptTokens: 200,
            completionTokens: 100,
            latencyMs: 500,
        );

        $row = $logger->record($request, $response);

        $this->assertSame('founder', $row->agent);
        $this->assertNull($row->organization_id);
        $this->assertSame('completed', $row->status);

        $log = AiCallLog::first();
        $this->assertSame('dispatch-abc', $log->dispatch_id);
        $this->assertSame(['foo' => 'bar'], $log->context);
        $this->assertStringContainsString('[user] go', $log->prompt);
    }

    public function test_record_scrubs_emails_in_prompt_and_context(): void
    {
        $logger = new CallLogger;

        $request = new AgentRequest(
            agent: 'support',
            task: 'help',
            messages: [['role' => 'user', 'content' => 'email me at jane@example.com']],
            context: ['contact' => 'jane@example.com'],
        );
        $response = new AgentResponse(text: 'ok', provider: 'fake');

        $row = $logger->record($request, $response);
        $row->refresh();

        $this->assertStringNotContainsString('jane@example.com', $row->prompt);
        $this->assertStringContainsString('[email]', $row->prompt);
        $this->assertSame('[email]', $row->context['contact']);
    }

    public function test_record_handles_failed_response(): void
    {
        $logger = new CallLogger;
        $request = new AgentRequest(agent: 'founder', task: 'x', messages: []);
        $response = AgentResponse::withError('boom', 99);

        $row = $logger->record($request, $response);

        $this->assertSame('failed', $row->status);
        $this->assertSame('boom', $row->error);
    }

    public function test_today_cost_cents_sums_only_today(): void
    {
        $logger = new CallLogger;

        AiCallLog::create([
            'dispatch_id' => 'd1',
            'agent' => 'founder',
            'task' => 't',
            'model' => 'm',
            'provider' => 'fake',
            'cost_usd_cents' => 5,
            'latency_ms' => 0,
            'status' => 'completed',
            'created_at' => now()->subDays(2),
        ]);

        AiCallLog::create([
            'dispatch_id' => 'd2',
            'agent' => 'founder',
            'task' => 't',
            'model' => 'm',
            'provider' => 'fake',
            'cost_usd_cents' => 7,
            'latency_ms' => 0,
            'status' => 'completed',
            'created_at' => now()->subHours(1),
        ]);

        $this->assertSame(7, $logger->todayCostCents());
    }
}
