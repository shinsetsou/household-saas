<?php

declare(strict_types=1);

namespace App\AI;

class ToolInvocation
{
    public function __construct(
        public readonly string $name,
        public readonly array $arguments,
        public readonly mixed $result = null,
        public readonly ?string $error = null,
        public readonly int $latencyMs = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'arguments' => $this->arguments,
            'result' => $this->result,
            'error' => $this->error,
            'latency_ms' => $this->latencyMs,
        ];
    }
}
