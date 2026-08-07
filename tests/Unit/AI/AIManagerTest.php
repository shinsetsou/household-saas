<?php

declare(strict_types=1);

namespace Tests\Unit\AI;

use App\AI\Agent;
use App\AI\AgentRequest;
use App\AI\AgentResponse;
use App\AI\AIManager;
use App\AI\Providers\FakeProvider;
use App\Models\AiCallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_returns_generic_agent_with_configured_system_prompt(): void
    {
        $manager = $this->app->make(AIManager::class);

        $agent = $manager->agent('founder');

        $this->assertInstanceOf(Agent::class, $agent);

        $request = $agent->task('daily-brief')->buildRequest();
        $this->assertSame('founder', $request->agent);
        $this->assertSame('daily-brief', $request->task);
        $this->assertSame('system', $request->messages[0]['role']);
        $this->assertStringContainsString('Founder Agent', $request->messages[0]['content']);
    }

    public function test_execute_persists_ai_call_log(): void
    {
        $manager = $this->app->make(AIManager::class);

        $provider = new FakeProvider(['default_reply' => 'test-reply']);
        $provider->scriptReply(['agent' => 'founder'], 'brief-payload');
        $manager->fake($provider);

        $response = $manager->agent('founder')->task('daily-brief')->run();

        $this->assertSame('brief-payload', $response->text);
        $this->assertSame('fake', $response->provider);
        $this->assertSame(1, AiCallLog::count());

        $log = AiCallLog::first();
        $this->assertSame('founder', $log->agent);
        $this->assertSame('completed', $log->status);
        $this->assertSame('brief-payload', $log->response);
    }

    public function test_execute_records_failed_status_when_provider_errors(): void
    {
        $provider = new class extends FakeProvider
        {
            public function complete(AgentRequest $request): AgentResponse
            {
                return AgentResponse::withError('upstream blew up', 50);
            }
        };

        $manager = $this->app->make(AIManager::class)->fake($provider);

        $response = $manager->agent('founder')->task('daily-brief')->run();

        $this->assertTrue($response->isError());
        $this->assertSame('failed', AiCallLog::first()->status);
    }

    public function test_fake_can_be_swapped_and_flushed(): void
    {
        $manager = $this->app->make(AIManager::class);
        $default = $manager->resolveProvider('founder');
        $this->assertSame('fake', $default->name());

        $manager->flushFake();

        $manager->fake(new FakeProvider(['default_reply' => 'override']));
        $this->assertSame('override', $manager->agent('founder')->task('x')->run()->text);

        $manager->flushFake();
        $this->assertSame('fake-acknowledged', $manager->agent('founder')->task('y')->run()->text);
    }
}
