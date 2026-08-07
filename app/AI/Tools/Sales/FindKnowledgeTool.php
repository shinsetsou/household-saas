<?php

declare(strict_types=1);

namespace App\AI\Tools\Sales;

use App\AI\AIManager;
use App\AI\Contracts\ToolResult;
use App\AI\MemoryQuery;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;

class FindKnowledgeTool extends BaseTool
{
    public function __construct(protected readonly AIManager $manager) {}

    public function name(): string
    {
        return 'find_knowledge';
    }

    public function description(): string
    {
        return 'Search the BarangayBooks marketing & product knowledge base (feature highlights, pricing context, FAQ). Returns top matches with similarity scores. Use this whenever the visitor asks about features, what the product does, who it is for, or how the trial works.';
    }

    public function schema(): array
    {
        return $this->objectSchema([
            'query' => [
                'type' => 'string',
                'description' => 'The visitor question, phrased as a search query.',
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Max results to return (1-5). Defaults to 3.',
                'minimum' => 1,
                'maximum' => 5,
            ],
        ], ['query']);
    }

    protected function handle(array $arguments): ToolResult
    {
        $query = trim((string) ($arguments['query'] ?? ''));
        $limit = (int) ($arguments['limit'] ?? 3);

        if ($query === '') {
            return ToolResultPayload::failure('Missing required argument: query');
        }

        $embedder = $this->manager->resolveEmbedder();
        $store = $this->manager->resolveMemoryStore();

        $results = $store->search(new MemoryQuery(
            namespace: 'marketing',
            query: $query,
            queryEmbedding: $embedder->embed($query),
            limit: max(1, min(5, $limit)),
        ));

        $items = [];

        foreach ($results as $r) {
            $items[] = [
                'score' => round($r['score'], 4),
                'content' => $r['item']->content,
                'source' => $r['item']->source,
                'source_id' => $r['item']->sourceId,
                'title' => $r['item']->metadata['title'] ?? null,
                'url' => $r['item']->metadata['url'] ?? null,
            ];
        }

        return ToolResultPayload::success([
            'matches' => $items,
            'best_score' => $items[0]['score'] ?? 0.0,
        ]);
    }
}
