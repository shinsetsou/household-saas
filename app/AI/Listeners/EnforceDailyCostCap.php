<?php

declare(strict_types=1);

namespace App\AI\Listeners;

use App\AI\Events\AgentDispatched;
use App\AI\Logging\CallLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class EnforceDailyCostCap implements ShouldQueue
{
    public string $queue = 'ai';

    public function __construct(protected readonly CallLogger $logger) {}

    /**
     * Refuse to dispatch an agent if today's spend exceeds the configured cap.
     * The cap is checked before the LLM call is made so we never bill for
     * refused work. CostGuard runs as a side-effect listener — the actual
     * gate happens in AIManager::execute() right before provider->complete().
     */
    public function handle(AgentDispatched $event): void
    {
        $cap = (int) config('ai.daily_cost_cap_cents', 50);
        $spent = $this->logger->todayCostCents();

        if ($spent >= $cap) {
            Log::error('AI daily cost cap reached; future dispatches will be refused', [
                'agent' => $event->agent,
                'task' => $event->task,
                'spent_cents' => $spent,
                'cap_cents' => $cap,
            ]);
        }
    }

    public static function shouldRefuse(int $cap, int $spent, ?int $agentCap = null): bool
    {
        if ($agentCap !== null && $agentCap <= 0) {
            return true;
        }

        return $spent >= $cap;
    }
}
