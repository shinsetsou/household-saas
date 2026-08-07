<?php

declare(strict_types=1);

namespace App\AI;

class MemoryItem
{
    /**
     * @param  array<int, float>  $embedding
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $namespace,
        public readonly string $content,
        public readonly array $embedding,
        public readonly string $source = 'kb',
        public readonly ?string $sourceId = null,
        public readonly ?string $embeddingModel = null,
        public readonly array $metadata = [],
        public readonly ?int $organizationId = null,
        public readonly ?int $userId = null,
        public readonly ?int $id = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'namespace' => $this->namespace,
            'content' => $this->content,
            'embedding' => $this->embedding,
            'source' => $this->source,
            'source_id' => $this->sourceId,
            'embedding_model' => $this->embeddingModel,
            'metadata' => $this->metadata,
            'organization_id' => $this->organizationId,
            'user_id' => $this->userId,
        ];
    }
}
