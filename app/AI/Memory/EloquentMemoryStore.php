<?php

declare(strict_types=1);

namespace App\AI\Memory;

use App\AI\Contracts\Embedder;
use App\AI\Contracts\MemoryStore;
use App\AI\MemoryItem;
use App\AI\MemoryQuery;
use App\Models\AiMemory;

class EloquentMemoryStore implements MemoryStore
{
    public function __construct(protected readonly Embedder $embedder) {}

    public function remember(MemoryItem $item): MemoryItem
    {
        if (empty($item->embedding) && $item->content !== '') {
            $item = $this->fillEmbedding($item);
        }

        $attributes = [
            'namespace' => $item->namespace,
            'source' => $item->source,
            'source_id' => $item->sourceId,
            'embedding_model' => $item->embeddingModel ?? $this->embedder->modelName(),
            'embedding' => $item->embedding,
            'content' => $item->content,
            'metadata' => $item->metadata,
            'organization_id' => $item->organizationId,
            'user_id' => $item->userId,
        ];

        $existing = null;

        if ($item->sourceId !== null) {
            $existing = AiMemory::query()
                ->where('namespace', $item->namespace)
                ->where('source', $item->source)
                ->where('source_id', $item->sourceId)
                ->when($item->organizationId, fn ($q) => $q->where('organization_id', $item->organizationId))
                ->first();
        }

        $row = $existing ?? new AiMemory;
        $row->fill($attributes);
        $row->save();

        return new MemoryItem(
            namespace: $row->namespace,
            content: $row->content,
            embedding: $row->embedding,
            source: $row->source,
            sourceId: $row->source_id,
            embeddingModel: $row->embedding_model,
            metadata: $row->metadata ?? [],
            organizationId: $row->organization_id,
            userId: $row->user_id,
            id: $row->id,
        );
    }

    public function search(MemoryQuery $query): array
    {
        $rows = AiMemory::query()
            ->where('namespace', $query->namespace)
            ->when($query->organizationId !== null, fn ($q) => $q->where(function ($q) use ($query) {
                $q->whereNull('organization_id')->orWhere('organization_id', $query->organizationId);
            }))
            ->get();

        $scored = [];

        foreach ($rows as $row) {
            if (! is_array($row->embedding) || empty($row->embedding)) {
                continue;
            }

            $score = $this->cosineSimilarity($query->queryEmbedding, $row->embedding);

            if ($score < $query->minScore) {
                continue;
            }

            $scored[] = [
                'item' => new MemoryItem(
                    namespace: $row->namespace,
                    content: $row->content,
                    embedding: $row->embedding,
                    source: $row->source,
                    sourceId: $row->source_id,
                    embeddingModel: $row->embedding_model,
                    metadata: $row->metadata ?? [],
                    organizationId: $row->organization_id,
                    userId: $row->user_id,
                    id: $row->id,
                ),
                'score' => $score,
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $query->limit);
    }

    public function forget(string $namespace, ?int $organizationId = null): int
    {
        $query = AiMemory::query()->where('namespace', $namespace);

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        return $query->delete();
    }

    protected function fillEmbedding(MemoryItem $item): MemoryItem
    {
        return new MemoryItem(
            namespace: $item->namespace,
            content: $item->content,
            embedding: $this->embedder->embed($item->content),
            source: $item->source,
            sourceId: $item->sourceId,
            embeddingModel: $item->embeddingModel ?? $this->embedder->modelName(),
            metadata: $item->metadata,
            organizationId: $item->organizationId,
            userId: $item->userId,
            id: $item->id,
        );
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b) || empty($a)) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $val) {
            $dot += $val * $b[$i];
            $normA += $val * $val;
            $normB += $b[$i] * $b[$i];
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
