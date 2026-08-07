<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Tools;

use App\AI\AIManager;
use App\AI\Contracts\Embedder;
use App\AI\Memory\EloquentMemoryStore;
use App\AI\MemoryItem;
use App\AI\Tools\Support\FindKnowledgeTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindKnowledgeToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_failure_when_query_is_empty(): void
    {
        $result = (new FindKnowledgeTool($this->app->make(AIManager::class)))->execute([])->toArray();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Missing required argument', $result['error']);
    }

    public function test_searches_kb_and_returns_top_matches(): void
    {
        $store = new EloquentMemoryStore($this->app->make(Embedder::class));

        $store->remember(new MemoryItem(
            namespace: 'kb',
            content: 'BarangayBooks supports CSV household imports.',
            embedding: [1.0, 0.0, 0.0],
            source: 'doc',
            sourceId: 'csv-001',
            metadata: ['title' => 'CSV Imports', 'url' => '/docs/csv-imports'],
        ));

        $store->remember(new MemoryItem(
            namespace: 'kb',
            content: 'Unrelated article about cats.',
            embedding: [0.0, 1.0, 0.0],
            source: 'doc',
            sourceId: 'cats-001',
            metadata: ['title' => 'Cats', 'url' => '/docs/cats'],
        ));

        $tool = new FindKnowledgeTool($this->app->make(AIManager::class));

        $result = $tool->execute(['query' => 'how do I import households via CSV?'])->toArray();

        $this->assertTrue($result['ok']);
        $matches = $result['data']['matches'];
        $this->assertGreaterThanOrEqual(1, count($matches));
        $this->assertSame('csv-001', $matches[0]['source_id']);
        $this->assertSame('/docs/csv-imports', $matches[0]['url']);
    }
}
