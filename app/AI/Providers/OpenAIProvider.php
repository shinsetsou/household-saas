<?php

declare(strict_types=1);

namespace App\AI\Providers;

use App\AI\AgentRequest;
use App\AI\AgentResponse;
use App\AI\Contracts\AIProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class OpenAIProvider implements AIProvider
{
    public function __construct(
        protected readonly array $config,
        protected readonly string $model,
        protected readonly string $embeddingModel,
    ) {}

    public function name(): string
    {
        return 'openai';
    }

    public function embed(string $text): array
    {
        $response = $this->http()->post('/embeddings', [
            'model' => $this->embeddingModel,
            'input' => $text,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI embed failed: '.$response->body());
        }

        $data = $response->json('data.0.embedding');

        if (! is_array($data)) {
            throw new \RuntimeException('OpenAI embed response missing data.0.embedding');
        }

        return array_map('floatval', $data);
    }

    public function complete(AgentRequest $request): AgentResponse
    {
        $startedAt = microtime(true);
        $payload = $this->buildPayload($request);

        try {
            $response = $this->http()->post('/chat/completions', $payload);
        } catch (\Throwable $e) {
            return AgentResponse::withError('OpenAI transport: '.$e->getMessage(), (int) ((microtime(true) - $startedAt) * 1000));
        }

        $latency = (int) ((microtime(true) - $startedAt) * 1000);

        if ($response->failed()) {
            return AgentResponse::withError(
                'OpenAI HTTP '.$response->status().': '.$response->body(),
                $latency,
            );
        }

        $body = $response->json();
        $choice = $body['choices'][0] ?? null;

        if (! $choice) {
            return AgentResponse::withError('OpenAI response had no choices.', $latency);
        }

        $message = $choice['message'] ?? [];
        $usage = $body['usage'] ?? [];

        return new AgentResponse(
            text: (string) ($message['content'] ?? ''),
            model: $body['model'] ?? $request->model ?? $this->model,
            provider: $this->name(),
            promptTokens: (int) ($usage['prompt_tokens'] ?? 0),
            completionTokens: (int) ($usage['completion_tokens'] ?? 0),
            latencyMs: $latency,
            finishReason: $choice['finish_reason'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(AgentRequest $request): array
    {
        $payload = [
            'model' => $request->model ?? $this->model,
            'messages' => $request->messages,
        ];

        if ($request->temperature !== null) {
            $payload['temperature'] = $request->temperature;
        }

        if ($request->maxTokens !== null) {
            $payload['max_tokens'] = $request->maxTokens;
        }

        if (! empty($request->tools)) {
            $payload['tools'] = array_map(fn ($t) => [
                'type' => 'function',
                'function' => [
                    'name' => $t->name(),
                    'description' => $t->description(),
                    'parameters' => $t->schema(),
                ],
            ], $request->tools);
        }

        return $payload;
    }

    protected function http(): PendingRequest
    {
        $request = Http::baseUrl($this->config['base_url'])
            ->timeout($this->config['request_timeout'])
            ->acceptJson()
            ->asJson()
            ->withToken((string) $this->config['api_key']);

        if (! empty($this->config['organization'])) {
            $request = $request->withHeader('OpenAI-Organization', $this->config['organization']);
        }

        return $request;
    }
}
