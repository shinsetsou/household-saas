<?php

declare(strict_types=1);

namespace App\AI;

class AgentResponse
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<int, ToolInvocation>  $toolInvocations
     */
    public function __construct(
        public readonly string $text,
        public readonly array $messages = [],
        public readonly array $toolInvocations = [],
        public readonly ?string $model = null,
        public readonly ?string $provider = null,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly int $latencyMs = 0,
        public readonly ?string $error = null,
        public readonly ?string $finishReason = null,
    ) {}

    public function isError(): bool
    {
        return $this->error !== null;
    }

    public function totalCostCents(float $promptRate, float $completionRate): int
    {
        $prompt = ($this->promptTokens / 1000) * $promptRate;
        $completion = ($this->completionTokens / 1000) * $completionRate;

        return (int) round(($prompt + $completion) * 100);
    }

    public static function withError(string $message, int $latencyMs = 0): self
    {
        return new self(
            text: '',
            error: $message,
            latencyMs: $latencyMs,
        );
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'model' => $this->model,
            'provider' => $this->provider,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'latency_ms' => $this->latencyMs,
            'tool_invocations' => array_map(fn ($t) => $t->toArray(), $this->toolInvocations),
            'error' => $this->error,
            'finish_reason' => $this->finishReason,
        ];
    }
}
