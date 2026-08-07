<?php

declare(strict_types=1);

namespace App\AI;

class MemoryQuery
{
    public function __construct(
        public readonly string $namespace,
        public readonly string $query,
        public readonly array $queryEmbedding,
        public readonly int $limit = 5,
        public readonly float $minScore = 0.0,
        public readonly ?int $organizationId = null,
        public readonly ?int $userId = null,
    ) {}
}
