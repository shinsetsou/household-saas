<?php

declare(strict_types=1);

namespace App\AI\Contracts;

use App\AI\MemoryItem;
use App\AI\MemoryQuery;

interface MemoryStore
{
    /**
     * Persist a memory item. Implementations may overwrite an existing row
     * keyed by (namespace, source, source_id) when both are set.
     */
    public function remember(MemoryItem $item): MemoryItem;

    /**
     * Vector search over a namespace. Returns the highest-scoring items
     * up to $query->limit. Score is cosine similarity in [0, 1].
     *
     * @return array<int, array{item: MemoryItem, score: float}>
     */
    public function search(MemoryQuery $query): array;

    /**
     * Forget everything in a namespace. Used by ai:embed-docs to refresh KB.
     */
    public function forget(string $namespace, ?int $organizationId = null): int;
}
