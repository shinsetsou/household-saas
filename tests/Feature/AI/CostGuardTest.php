<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\AI\AIManager;
use App\Models\AiCallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_succeeds_when_under_cap(): void
    {
        config()->set('ai.daily_cost_cap_cents', 50);

        AiCallLog::create([
            'dispatch_id' => 'd1',
            'agent' => 'founder',
            'task' => 'brief',
            'model' => 'm',
            'provider' => 'fake',
            'cost_usd_cents' => 10,
            'latency_ms' => 0,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $manager = $this->app->make(AIManager::class);
        $response = $manager->agent('founder')->task('brief')->run();

        $this->assertFalse($response->isError());
    }

    public function test_dispatch_throws_when_cap_reached(): void
    {
        config()->set('ai.daily_cost_cap_cents', 5);

        AiCallLog::create([
            'dispatch_id' => 'd1',
            'agent' => 'founder',
            'task' => 'brief',
            'model' => 'm',
            'provider' => 'fake',
            'cost_usd_cents' => 5,
            'latency_ms' => 0,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $manager = $this->app->make(AIManager::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/cost cap/i');

        $manager->agent('founder')->task('brief')->run();
    }
}
