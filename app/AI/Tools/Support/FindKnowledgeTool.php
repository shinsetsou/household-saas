<?php

declare(strict_types=1);

namespace App\AI\Tools\Support;

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
        return 'Search the BarangayBooks knowledge base (docs, FAQs, KB articles). Returns the top matches with similarity scores (0-1) and source citations. Always call this before answering a question.';
    }

    public function schema(): array
    {
        return $this->objectSchema([
            'query' => [
                'type' => 'string',
                'description' => 'The user question, phrased as a search query.',
            ],
            'namespace' => [
                'type' => 'string',
                'description' => 'KB namespace to search. Defaults to "kb".',
                'enum' => ['kb', 'onboarding', 'billing'],
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Max results to return (1-10). Defaults to 3.',
                'minimum' => 1,
                'maximum' => 10,
            ],
        ], ['query']);
    }

    protected function handle(array $arguments): ToolResult
    {
        $query = trim((string) ($arguments['query'] ?? ''));
        $namespace = (string) ($arguments['namespace'] ?? 'kb');
        $limit = (int) ($arguments['limit'] ?? 3);

        if ($query === '') {
            return ToolResultPayload::failure('Missing required argument: query');
        }

        $embedder = $this->manager->resolveEmbedder();
        $store = $this->manager->resolveMemoryStore();

        $results = $store->search(new MemoryQuery(
            namespace: $namespace,
            query: $query,
            queryEmbedding: $embedder->embed($query),
            limit: max(1, min(10, $limit)),
        ));

        $items = [];

        foreach ($results as $r) {
            $items[] = [
                'score' => round($r['score'], 4),
                'content' => $r['item']->content,
                'source' => $r['item']->source,
                'source_id' => $r['item']->sourceId,
                'url' => $r['item']->metadata['url'] ?? null,
                'title' => $r['item']->metadata['title'] ?? null,
            ];
        }

        return ToolResultPayload::success([
            'matches' => $items,
            'best_score' => $items[0]['score'] ?? 0.0,
        ]);
    }
}
