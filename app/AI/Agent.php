<?php

declare(strict_types=1);

namespace App\AI;

use App\AI\Contracts\Tool;
use App\AI\Jobs\RunAgentJob;
use App\Models\User;
use Illuminate\Support\Str;

class Agent
{
    /** @var array<int, Tool> */
    protected array $tools = [];

    protected ?string $task = null;

    protected AgentContext $context;

    protected ?string $model = null;

    protected ?float $temperature = null;

    protected ?int $maxTokens = null;

    protected ?int $organizationId = null;

    protected ?int $userId = null;

    protected bool $async = false;

    protected ?string $dispatchId = null;

    protected ?int $costCapCents = null;

    /** @var array<int, array{role: string, content: string}> */
    protected array $messages = [];

    public function __construct(
        protected readonly string $name,
        protected readonly string $systemPrompt,
    ) {
        $this->context = new AgentContext;
    }

    public function task(string $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function context(array|AgentContext $context): self
    {
        $this->context = $context instanceof AgentContext ? $context : new AgentContext($context);

        return $this;
    }

    public function with(string $key, mixed $value): self
    {
        $this->context->set($key, $value);

        return $this;
    }

    /**
     * @param  Tool|array<int, Tool>  $tools
     */
    public function tools(Tool|array $tools): self
    {
        $this->tools = is_array($tools) ? $tools : [$tools];

        return $this;
    }

    public function addTool(Tool $tool): self
    {
        $this->tools[] = $tool;

        return $this;
    }

    public function model(?string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function temperature(?float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function maxTokens(?int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function forOrganization(int|User|null $orgOrUser): self
    {
        if ($orgOrUser instanceof User) {
            $this->userId = $orgOrUser->id;

            return $this;
        }

        $this->organizationId = $orgOrUser;

        return $this;
    }

    public function forUser(?User $user): self
    {
        $this->userId = $user?->id;

        return $this;
    }

    public function costCapCents(int|float|null $cents): self
    {
        $this->costCapCents = $cents !== null ? (int) $cents : null;

        return $this;
    }

    /**
     * Dispatch synchronously (default) — returns AgentResponse.
     */
    public function run(): AgentResponse
    {
        return app(AIManager::class)->execute($this->buildRequest());
    }

    /**
     * Dispatch async via the queue. Returns the dispatch_id; callers can poll
     * ai_call_logs for completion.
     */
    public function queue(?string $queue = null): string
    {
        $this->dispatchId ??= (string) Str::uuid();

        $request = $this->buildRequest();
        $job = new RunAgentJob($request);

        if ($queue !== null) {
            $job->onQueue($queue);
        }

        dispatch($job);

        return $request->dispatchId;
    }

    public function dispatchId(): string
    {
        $this->dispatchId ??= (string) Str::uuid();

        return $this->dispatchId;
    }

    public function buildRequest(): AgentRequest
    {
        if ($this->task === null) {
            throw new \LogicException("Agent [{$this->name}] requires ->task(...) before ->run() or ->queue().");
        }

        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt]],
            $this->messages,
            [['role' => 'user', 'content' => $this->task]],
        );

        return new AgentRequest(
            agent: $this->name,
            task: $this->task,
            messages: $messages,
            tools: $this->tools,
            context: $this->context->toArray(),
            model: $this->model,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            dispatchId: $this->dispatchId(),
            organizationId: $this->organizationId,
            userId: $this->userId,
        );
    }

    /**
     * Append an assistant/user message between system prompt and final task.
     */
    public function say(string $role, string $content): self
    {
        $this->messages[] = ['role' => $role, 'content' => $content];

        return $this;
    }
}
