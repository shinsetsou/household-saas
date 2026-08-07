<?php

declare(strict_types=1);

namespace App\AI\Contracts;

interface Tool
{
    /**
     * Stable snake_case identifier used by the provider's tool registry.
     */
    public function name(): string;

    /**
     * One-sentence description the model reads to decide when to call it.
     */
    public function description(): string;

    /**
     * JSON Schema describing the arguments the tool accepts. Returned as
     * a PHP array; the provider converts to its native format.
     */
    public function schema(): array;

    /**
     * Execute the tool against Laravel services. Throw on domain errors —
     * AIManager converts the exception into a structured failure response
     * the model can reason about.
     */
    public function execute(array $arguments): ToolResult;
}
