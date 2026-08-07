<?php

declare(strict_types=1);

namespace App\AI;

use App\AI\Contracts\Tool;

class AgentRequest
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<int, Tool>  $tools
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $agent,
        public readonly string $task,
        public readonly array $messages,
        public readonly array $tools = [],
        public readonly array $context = [],
        public readonly ?string $model = null,
        public readonly ?float $temperature = null,
        public readonly ?int $maxTokens = null,
        public readonly ?string $dispatchId = null,
        public readonly ?int $organizationId = null,
        public readonly ?int $userId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'agent' => $this->agent,
            'task' => $this->task,
            'messages' => $this->messages,
            'tools' => array_map(fn (Tool $t) => [
                'name' => $t->name(),
                'description' => $t->description(),
                'schema' => $t->schema(),
            ], $this->tools),
            'context' => $this->context,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'dispatch_id' => $this->dispatchId,
        ];
    }

    /**
     * Rehydrate from toArray(). Tools are NOT serialized — RunAgentJob must
     * re-resolve them from the AgentRequest's agent slug when the job runs.
     * Use AgentRequest::resolvedFromArray() in jobs that need tools.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            agent: (string) ($data['agent'] ?? 'unknown'),
            task: (string) ($data['task'] ?? ''),
            messages: (array) ($data['messages'] ?? []),
            tools: [],
            context: (array) ($data['context'] ?? []),
            model: $data['model'] ?? null,
            temperature: isset($data['temperature']) ? (float) $data['temperature'] : null,
            maxTokens: isset($data['max_tokens']) ? (int) $data['max_tokens'] : null,
            dispatchId: $data['dispatch_id'] ?? null,
            organizationId: isset($data['organization_id']) ? (int) $data['organization_id'] : null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
        );
    }
}
