<?php

declare(strict_types=1);

namespace App\AI\Contracts;

use App\AI\AgentRequest;
use App\AI\AgentResponse;

interface AIProvider
{
    /**
     * Send a fully-built request to the underlying model and return a response.
     * Implementations must NOT throw on rate limits — return AgentResponse::withError()
     * so the caller can persist the failure via CallLogger.
     */
    public function complete(AgentRequest $request): AgentResponse;

    /**
     * Embed a chunk of text into a fixed-size vector. Returns the raw array
     * (not a MemoryItem). The MemoryStore decides how to persist it.
     */
    public function embed(string $text): array;

    /**
     * Friendly identifier used in ai_call_logs (e.g. "openai", "ollama", "fake").
     */
    public function name(): string;
}
