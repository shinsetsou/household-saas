<?php

declare(strict_types=1);

namespace App\AI;

use App\AI\Contracts\AIProvider;
use App\AI\Contracts\Embedder;
use App\AI\Contracts\MemoryStore;
use App\AI\Events\AgentCompleted;
use App\AI\Events\AgentDispatched;
use App\AI\Events\AgentFailed;
use App\AI\Events\FounderBriefGenerated;
use App\AI\Logging\CallLogger;
use App\AI\Providers\FakeProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;

class AIManager
{
    protected ?AIProvider $swappedProvider = null;

    public function __construct(
        protected readonly Container $container,
        protected readonly array $config,
        protected readonly CallLogger $logger,
    ) {}

    /**
     * Fluent entry point — agents wrap themselves in this.
     */
    public function agent(string $name): Agent
    {
        $agentClass = $this->resolveAgentClass($name);

        if ($agentClass === GenericAgent::class) {
            $instance = new GenericAgent($name);
        } else {
            $instance = $this->container->make($agentClass);
        }

        if (! $instance instanceof Agent) {
            throw new \RuntimeException("Agent [{$name}] must resolve to an instance of App\AI\Agent.");
        }

        return $instance;
    }

    /**
     * Build an Agent with a custom system prompt without registering a class.
     * Useful for ad-hoc jobs (embeddings, summarization) that aren't named agents.
     */
    public function adhoc(string $name, string $systemPrompt): Agent
    {
        return (new Agent($name, $systemPrompt))->context([]);
    }

    /**
     * Execute an AgentRequest synchronously. The Job/Command/Controller layers
     * call this. Emits AgentDispatched before, AgentCompleted/AgentFailed after.
     */
    public function execute(AgentRequest $request): AgentResponse
    {
        $this->guardDailyCostCap($request);

        $provider = $this->resolveProvider($request->agent);
        $startedAt = microtime(true);

        AgentDispatched::dispatch($request->agent, $request->task, [
            'dispatch_id' => $request->dispatchId,
            'tool_count' => count($request->tools),
            'organization_id' => $request->organizationId,
            'user_id' => $request->userId,
        ]);

        try {
            $response = $provider->complete($request);
        } catch (\Throwable $e) {
            $response = AgentResponse::withError(
                'AIManager execute threw: '.$e->getMessage(),
                (int) ((microtime(true) - $startedAt) * 1000),
            );
        }

        $this->logger->record($request, $response);

        if ($response->isError()) {
            AgentFailed::dispatch($request->agent, $request->task, $response->error ?? 'unknown', [
                'dispatch_id' => $request->dispatchId,
            ]);
        } else {
            AgentCompleted::dispatch($request->agent, $request->task, $response, [
                'dispatch_id' => $request->dispatchId,
                'organization_id' => $request->organizationId,
                'user_id' => $request->userId,
            ]);

            if ($request->agent === 'founder') {
                $payload = json_decode($response->text, true);

                if (! is_array($payload)) {
                    $payload = ['raw' => $response->text];
                }

                FounderBriefGenerated::dispatch($request->dispatchId, $payload);
            }
        }

        return $response;
    }

    /**
     * Resolve the configured provider, honoring per-agent overrides and
     * any test swap.
     */
    public function resolveProvider(?string $agent = null): AIProvider
    {
        if ($this->swappedProvider !== null) {
            return $this->swappedProvider;
        }

        $agentConfig = ($agent !== null && isset($this->config['agents'][$agent])) ? $this->config['agents'][$agent] : [];
        $providerKey = $agentConfig['provider'] ?? $this->config['provider'];

        return match ($providerKey) {
            'openai' => $this->container->make(AIProvider::class, ['name' => 'openai']),
            'ollama' => $this->container->make(AIProvider::class, ['name' => 'ollama']),
            'fake' => $this->container->make(AIProvider::class, ['name' => 'fake']),
            default => throw new \RuntimeException("Unknown AI provider [{$providerKey}]"),
        };
    }

    public function resolveEmbedder(): Embedder
    {
        return $this->container->make(Embedder::class);
    }

    public function resolveMemoryStore(): MemoryStore
    {
        return $this->container->make(MemoryStore::class);
    }

    /**
     * Swap the provider used by the next execute() call. Used by tests.
     */
    public function fake(?AIProvider $provider = null): self
    {
        $this->swappedProvider = $provider ?? new FakeProvider($this->config['fake'] ?? []);

        return $this;
    }

    public function flushFake(): self
    {
        $this->swappedProvider = null;

        return $this;
    }

    /**
     * Resolve an Agent class for a named agent. Falls back to a generic
     * Agent if no specific class is registered.
     */
    protected function resolveAgentClass(string $name): string
    {
        $map = (array) config('ai.agent_map', []);

        if (isset($map[$name])) {
            return $map[$name];
        }

        $candidate = 'App\\AI\\Agents\\'.Str::studly($name).'Agent';

        if (class_exists($candidate)) {
            return $candidate;
        }

        // Generic fallback — the caller must supply systemPrompt + tools via
        // configuration in config/ai.php.
        return GenericAgent::class;
    }

    protected function guardDailyCostCap(AgentRequest $request): void
    {
        $cap = (int) config('ai.daily_cost_cap_cents', 50);
        $spent = $this->logger->todayCostCents();

        if ($spent >= $cap) {
            $error = sprintf(
                'AI daily cost cap reached (spent %d cents, cap %d cents). Refusing to dispatch %s.',
                $spent,
                $cap,
                $request->agent,
            );

            AgentFailed::dispatch($request->agent, $request->task, $error, [
                'dispatch_id' => $request->dispatchId,
                'reason' => 'cost_cap',
            ]);

            throw new \RuntimeException($error);
        }
    }
}
