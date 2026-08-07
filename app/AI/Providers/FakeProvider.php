<?php

declare(strict_types=1);

namespace App\AI\Providers;

use App\AI\AgentRequest;
use App\AI\AgentResponse;
use App\AI\Contracts\AIProvider;

/**
 * Deterministic provider for tests + dev. Returns a scripted reply or the
 * configured default. Token counts are deterministic so cost/latency math
 * is testable.
 */
class FakeProvider implements AIProvider
{
    /**
     * @var array<int, array{match: array<string, mixed>, reply: string, model?: string, tokens?: array{prompt: int, completion: int}}>
     */
    protected array $scripted = [];

    protected string $defaultReply;

    public function __construct(array $config = [])
    {
        $this->defaultReply = (string) ($config['default_reply'] ?? 'fake-acknowledged');
    }

    public function name(): string
    {
        return 'fake';
    }

    /**
     * Append a scripted reply. The next complete() call whose request matches
     * the closure predicate pops the reply. FIFO.
     *
     * @param  array<string, mixed>  $match
     */
    public function scriptReply(array $match, string $reply, ?string $model = null, ?array $tokens = null): self
    {
        $this->scripted[] = [
            'match' => $match,
            'reply' => $reply,
            'model' => $model,
            'tokens' => $tokens,
        ];

        return $this;
    }

    public function embed(string $text): array
    {
        // Deterministic 8-dim "embedding" for tests. Real embeddings are
        // produced by EloquentMemoryStore's chunked payload.
        $seed = crc32($text);
        mt_srand($seed);

        $vector = [];

        for ($i = 0; $i < 8; $i++) {
            $vector[] = mt_rand(-100, 100) / 100;
        }

        return $vector;
    }

    public function complete(AgentRequest $request): AgentResponse
    {
        $startedAt = microtime(true);

        foreach ($this->scripted as $i => $script) {
            if ($this->matches($request, $script['match'])) {
                $reply = $script['reply'];
                $model = $script['model'] ?? 'fake-model';
                $tokens = $script['tokens'] ?? ['prompt' => 100, 'completion' => 50];
                unset($this->scripted[$i]);
                $this->scripted = array_values($this->scripted);

                usleep(1000); // 1ms — deterministic latency floor

                return new AgentResponse(
                    text: $reply,
                    model: $model,
                    provider: $this->name(),
                    promptTokens: $tokens['prompt'],
                    completionTokens: $tokens['completion'],
                    latencyMs: (int) ((microtime(true) - $startedAt) * 1000),
                    finishReason: 'stop',
                );
            }
        }

        usleep(1000);

        return new AgentResponse(
            text: $this->defaultReply,
            model: 'fake-model',
            provider: $this->name(),
            promptTokens: 100,
            completionTokens: 50,
            latencyMs: (int) ((microtime(true) - $startedAt) * 1000),
            finishReason: 'stop',
        );
    }

    /**
     * @param  array<string, mixed>  $match
     */
    protected function matches(AgentRequest $request, array $match): bool
    {
        foreach ($match as $key => $value) {
            $actual = match ($key) {
                'agent' => $request->agent,
                'task' => $request->task,
                'model' => $request->model,
                'contains' => $this->messageContains($request, (string) $value),
                default => null,
            };

            if ($actual === null) {
                return false;
            }

            if (is_string($actual) && $value !== $actual) {
                return false;
            }

            if ($actual === false) {
                return false;
            }
        }

        return true;
    }

    protected function messageContains(AgentRequest $request, string $needle): bool
    {
        foreach ($request->messages as $m) {
            if (str_contains((string) $m['content'], $needle)) {
                return true;
            }
        }

        return false;
    }
}
