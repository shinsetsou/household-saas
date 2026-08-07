<?php

declare(strict_types=1);

namespace App\AI\Providers;

use App\AI\AgentRequest;
use App\AI\AgentResponse;
use App\AI\Contracts\AIProvider;
use App\AI\Contracts\Tool;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI provider with a tool-calling loop.
 *
 * If the assistant decides to call one or more tools, we execute them via
 * their registered Laravel services and re-issue the request with the
 * results appended (OpenAI's standard function-calling protocol). The loop
 * terminates when the assistant returns plain text or when we hit
 * `max_tool_iterations`. Tool results are scoped to the current AgentRequest
 * and are not persisted across requests.
 */
class OpenAIProvider implements AIProvider
{
    public const MAX_TOOL_ITERATIONS = 6;

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
        $messages = $request->messages;
        $tools = $request->tools;
        $toolMap = $this->indexTools($tools);

        $totalPromptTokens = 0;
        $totalCompletionTokens = 0;

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $result = $this->dispatchOneTurn($request, $messages, $tools, $totalPromptTokens, $totalCompletionTokens);

            // Hard error short-circuits.
            if ($result['fatal']) {
                return $result['response'];
            }

            $totalPromptTokens = $result['prompt_tokens'];
            $totalCompletionTokens = $result['completion_tokens'];

            $message = $result['message'];
            $toolCalls = $message['tool_calls'] ?? [];

            // Plain text — we're done.
            if (empty($toolCalls) || empty($tools)) {
                return new AgentResponse(
                    text: (string) ($message['content'] ?? ''),
                    model: $result['model'],
                    provider: $this->name(),
                    promptTokens: $totalPromptTokens,
                    completionTokens: $totalCompletionTokens,
                    latencyMs: (int) ((microtime(true) - $startedAt) * 1000),
                    finishReason: $result['finish_reason'],
                );
            }

            // Tool calls — execute and continue.
            $messages[] = $this->assistantToolCallMessage($message);
            $messages = $this->appendToolResults($messages, $toolCalls, $toolMap);
        }

        // Loop exhausted while the model kept calling tools. Issue ONE final
        // call without tools so the model is forced to synthesize an answer
        // from the tool results it's already seen.
        $result = $this->dispatchOneTurn($request, $messages, [], $totalPromptTokens, $totalCompletionTokens);

        if ($result['fatal']) {
            return $result['response'];
        }

        $totalPromptTokens = $result['prompt_tokens'];
        $totalCompletionTokens = $result['completion_tokens'];
        $message = $result['message'];

        return new AgentResponse(
            text: (string) ($message['content'] ?? ''),
            model: $result['model'],
            provider: $this->name(),
            promptTokens: $totalPromptTokens,
            completionTokens: $totalCompletionTokens,
            latencyMs: (int) ((microtime(true) - $startedAt) * 1000),
            finishReason: $result['finish_reason'] ?? 'tool_iterations_capped',
        );
    }

    /**
     * Send one OpenAI turn. Returns either a fatal AgentResponse or a tuple of
     * parsed response bits for the caller to inspect.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<int, Tool>  $tools
     * @return array{fatal: bool, response?: AgentResponse, message: array<string, mixed>, model: string, finish_reason: ?string, prompt_tokens: int, completion_tokens: int}
     */
    protected function dispatchOneTurn(AgentRequest $request, array $messages, array $tools, int $prevPrompt, int $prevCompletion): array
    {
        $payload = $this->buildPayload($request, $messages, $tools);

        try {
            $response = $this->http()->post('/chat/completions', $payload);
        } catch (\Throwable $e) {
            return [
                'fatal' => true,
                'response' => AgentResponse::withError('OpenAI transport: '.$e->getMessage(), 0),
                'message' => [], 'model' => $request->model ?? $this->model, 'finish_reason' => null,
                'prompt_tokens' => $prevPrompt, 'completion_tokens' => $prevCompletion,
            ];
        }

        if ($response->failed()) {
            return [
                'fatal' => true,
                'response' => AgentResponse::withError('OpenAI HTTP '.$response->status().': '.$response->body(), 0),
                'message' => [], 'model' => $request->model ?? $this->model, 'finish_reason' => null,
                'prompt_tokens' => $prevPrompt, 'completion_tokens' => $prevCompletion,
            ];
        }

        $body = $response->json();
        $choice = $body['choices'][0] ?? null;

        if (! $choice) {
            return [
                'fatal' => true,
                'response' => AgentResponse::withError('OpenAI response had no choices.', 0),
                'message' => [], 'model' => $body['model'] ?? $request->model ?? $this->model, 'finish_reason' => null,
                'prompt_tokens' => $prevPrompt, 'completion_tokens' => $prevCompletion,
            ];
        }

        $message = $choice['message'] ?? [];
        $usage = $body['usage'] ?? [];

        return [
            'fatal' => false,
            'message' => $message,
            'model' => $body['model'] ?? $request->model ?? $this->model,
            'finish_reason' => $choice['finish_reason'] ?? null,
            'prompt_tokens' => $prevPrompt + (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => $prevCompletion + (int) ($usage['completion_tokens'] ?? 0),
        ];
    }

    /**
     * @param  array<int, Tool>  $tools
     * @return array<string, Tool>
     */
    protected function indexTools(array $tools): array
    {
        $map = [];

        foreach ($tools as $t) {
            $map[$t->name()] = $t;
        }

        return $map;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<int, Tool>  $tools
     * @return array<string, mixed>
     */
    protected function buildPayload(AgentRequest $request, array $messages, array $tools): array
    {
        $payload = [
            'model' => $request->model ?? $this->model,
            'messages' => $messages,
        ];

        if ($request->temperature !== null) {
            $payload['temperature'] = $request->temperature;
        }

        if ($request->maxTokens !== null) {
            $payload['max_tokens'] = $request->maxTokens;
        }

        if (! empty($tools)) {
            $payload['tools'] = array_map(function ($t) {
                return [
                    'type' => 'function',
                    'function' => [
                        'name' => $t->name(),
                        'description' => $t->description(),
                        'parameters' => $this->normalizeSchema($t->schema()),
                    ],
                ];
            }, $tools);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    protected function assistantToolCallMessage(array $message): array
    {
        $out = [
            'role' => 'assistant',
            'content' => $message['content'] ?? null,
            'tool_calls' => [],
        ];

        foreach (($message['tool_calls'] ?? []) as $tc) {
            $out['tool_calls'][] = [
                'id' => $tc['id'] ?? null,
                'type' => $tc['type'] ?? 'function',
                'function' => [
                    'name' => $tc['function']['name'] ?? '',
                    'arguments' => $tc['function']['arguments'] ?? '{}',
                ],
            ];
        }

        return $out;
    }

    /**
     * Execute each tool call and append the result to the messages array.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $toolCalls
     * @param  array<string, Tool>  $toolMap
     * @return array<int, array<string, mixed>>
     */
    protected function appendToolResults(array $messages, array $toolCalls, array $toolMap): array
    {
        foreach ($toolCalls as $tc) {
            $callId = (string) ($tc['id'] ?? '');
            $functionName = (string) ($tc['function']['name'] ?? '');
            $argumentsRaw = (string) ($tc['function']['arguments'] ?? '{}');

            $arguments = $this->decodeArguments($argumentsRaw);

            if (! isset($toolMap[$functionName])) {
                $resultPayload = ['ok' => false, 'error' => "Tool '{$functionName}' is not registered for this agent.", 'data' => null];
            } else {
                $toolResult = $toolMap[$functionName]->execute($arguments);
                $resultPayload = $toolResult->toArray();

                // Tools sometimes return large payloads. Cap to a reasonable size
                // to avoid blowing the context window on a follow-up request.
                $resultPayload = $this->truncateResultPayload($resultPayload, 8_000);
            }

            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $callId,
                'name' => $functionName,
                'content' => json_encode($resultPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
        }

        return $messages;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeArguments(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Truncate oversized tool payloads so the next assistant turn's context
     * stays within the model's window. Result keys are preserved but string
     * values get cropped; nested arrays summary-rolled.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function truncateResultPayload(array $payload, int $maxChars): array
    {
        $serialized = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($serialized === false || strlen($serialized) <= $maxChars) {
            return $payload;
        }

        return [
            'ok' => (bool) ($payload['ok'] ?? true),
            'truncated' => true,
            'note' => 'Tool returned a large payload (>'.$maxChars.' chars). Summary only.',
            'data' => [
                'keys' => array_keys((array) ($payload['data'] ?? [])),
                'preview' => mb_substr((string) json_encode($payload['data'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, (int) ($maxChars * 0.6)),
            ],
        ];
    }

    /**
     * Normalize a tool schema for OpenAI's strict JSON Schema validator.
     *
     * OpenAI rejects `parameters.properties = []` (a JSON array) and
     * demands it be a JSON object `{}`. PHP's `json_encode` serializes
     * an empty array as `[]`; we cast an empty properties map to a
     * stdClass so it serializes as `{}`. Also defaults `type` and
     * `required` when missing so no-arg tools like `recommend_plan`
     * still produce a valid descriptor.
     */
    protected function normalizeSchema(array $schema): array
    {
        if (! isset($schema['type'])) {
            $schema['type'] = 'object';
        }

        if (! isset($schema['properties']) || (is_array($schema['properties']) && $schema['properties'] === [])) {
            $schema['properties'] = (object) [];
        }

        if (! isset($schema['required'])) {
            $schema['required'] = [];
        }

        return $schema;
    }

    protected function http(): PendingRequest
    {
        $request = Http::baseUrl($this->config['base_url'])
            ->timeout($this->config['request_timeout'])
            ->acceptJson()
            ->asJson()
            ->withToken((string) $this->config['api_key']);

        if (! empty($this->config['organization'])) {
            $request = $request->withHeader('OpenAI-Organization', (string) $this->config['organization']);
        }

        return $request;
    }
}
