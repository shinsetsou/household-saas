<?php

declare(strict_types=1);

namespace App\AI\Logging;

use App\AI\AgentRequest;
use App\AI\AgentResponse;
use App\Models\AiCallLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CallLogger
{
    public function record(AgentRequest $request, AgentResponse $response): AiCallLog
    {
        $promptRate = (float) config('ai.cost_per_1k_prompt_tokens_cents', 0.015);
        $completionRate = (float) config('ai.cost_per_1k_completion_tokens_cents', 0.06);

        $costCents = $response->totalCostCents($promptRate, $completionRate);

        try {
            $row = AiCallLog::create([
                'dispatch_id' => $request->dispatchId ?? (string) Str::uuid(),
                'agent' => $request->agent,
                'task' => $request->task,
                'model' => $response->model ?? $request->model,
                'provider' => $response->provider,
                'prompt_tokens' => $response->promptTokens,
                'completion_tokens' => $response->completionTokens,
                'total_tokens' => $response->promptTokens + $response->completionTokens,
                'cost_usd_cents' => $costCents,
                'latency_ms' => $response->latencyMs,
                'status' => $response->isError() ? 'failed' : 'completed',
                'error' => $response->error,
                'context' => $this->scrubContext($request->context),
                'prompt' => $this->scrubPrompt($request->messages),
                'response' => $response->text,
                'organization_id' => $request->organizationId,
                'user_id' => $request->userId,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Don't let logging crash the agent pipeline.
            Log::warning('ai_call_log insert failed', ['error' => $e->getMessage()]);

            $row = new AiCallLog;
            $row->dispatch_id = $request->dispatchId ?? (string) Str::uuid();
        }

        return $row;
    }

    /**
     * Sum cost over a window. Used by CostGuard.
     */
    public function todayCostCents(): int
    {
        return (int) AiCallLog::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('cost_usd_cents');
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    protected function scrubPrompt(array $messages): string
    {
        $lines = [];

        foreach ($messages as $m) {
            $lines[] = "[{$m['role']}] ".$this->scrubText((string) $m['content']);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function scrubContext(array $context): array
    {
        $json = json_encode($context);

        if ($json === false) {
            return ['_scrub_error' => true];
        }

        $scrubbed = $this->scrubText($json);

        $decoded = json_decode($scrubbed, true);

        return is_array($decoded) ? $decoded : ['_raw' => $scrubbed];
    }

    protected function scrubText(string $text): string
    {
        $patterns = [
            '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i' => '[email]',
            '/\b(?:\+?63|0)9\d{9}\b/' => '[phone]',
            '/\b(?:\d[ -]?){13,16}\b/' => '[card]',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return $text;
    }
}
