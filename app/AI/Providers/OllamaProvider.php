<?php

declare(strict_types=1);

namespace App\AI\Providers;

use App\AI\AgentRequest;
use App\AI\AgentResponse;
use App\AI\Contracts\AIProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class OllamaProvider implements AIProvider
{
    public function __construct(
        protected readonly array $config,
        protected readonly string $model,
        protected readonly string $embeddingModel,
    ) {}

    public function name(): string
    {
        return 'ollama';
    }

    public function embed(string $text): array
    {
        $response = $this->http()->post('/api/embeddings', [
            'model' => $this->embeddingModel,
            'prompt' => $text,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Ollama embed failed: '.$response->body());
        }

        $embedding = $response->json('embedding');

        if (! is_array($embedding)) {
            throw new \RuntimeException('Ollama embed response missing `embedding` key.');
        }

        return array_map('floatval', $embedding);
    }

    public function complete(AgentRequest $request): AgentResponse
    {
        $startedAt = microtime(true);

        // Ollama's /api/chat expects a messages array. Tool support depends on
        // the model and on Ollama 0.4+. We forward messages verbatim and let
        // Ollama decide. For now, the local model is best used for Founder
        // / Sales prompts that don't strictly require tool-calling.
        $payload = [
            'model' => $request->model ?? $this->model,
            'messages' => $request->messages,
            'stream' => false,
        ];

        if ($request->temperature !== null) {
            $payload['options'] = ['temperature' => $request->temperature];
        }

        try {
            $response = $this->http()->post('/api/chat', $payload);
        } catch (\Throwable $e) {
            return AgentResponse::withError('Ollama transport: '.$e->getMessage(), (int) ((microtime(true) - $startedAt) * 1000));
        }

        $latency = (int) ((microtime(true) - $startedAt) * 1000);

        if ($response->failed()) {
            return AgentResponse::withError('Ollama HTTP '.$response->status().': '.$response->body(), $latency);
        }

        $message = $response->json('message');
        $text = (string) ($message['content'] ?? '');

        // Ollama doesn't return token counts; estimate from text length.
        $estimatedCompletion = (int) ceil(str_word_count($text) * 1.3);
        $estimatedPrompt = 0;

        foreach ($request->messages as $m) {
            $estimatedPrompt += (int) ceil(str_word_count((string) $m['content']) * 1.3);
        }

        return new AgentResponse(
            text: $text,
            model: $request->model ?? $this->model,
            provider: $this->name(),
            promptTokens: $estimatedPrompt,
            completionTokens: $estimatedCompletion,
            latencyMs: $latency,
            finishReason: $response->json('done_reason'),
        );
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->config['base_url'])
            ->timeout($this->config['request_timeout'])
            ->acceptJson()
            ->asJson();
    }
}
