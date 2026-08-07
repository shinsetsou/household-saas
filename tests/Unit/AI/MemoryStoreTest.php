<?php

declare(strict_types=1);

namespace Tests\Unit\AI;

use App\AI\Contracts\Embedder;
use App\AI\Memory\EloquentMemoryStore;
use App\AI\MemoryItem;
use App\AI\MemoryQuery;
use App\Models\AiMemory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_remember_persists_embedding_and_metadata(): void
    {
        $embedder = $this->app->make(Embedder::class);
        $store = new EloquentMemoryStore($embedder);

        $item = $store->remember(new MemoryItem(
            namespace: 'kb',
            content: 'BarangayBooks supports CSV imports.',
            embedding: [0.1, 0.2, 0.3],
            source: 'doc',
            sourceId: 'kb-001',
            metadata: ['url' => '/docs/csv'],
        ));

        $this->assertNotNull($item->id);
        $this->assertSame(1, AiMemory::count());
        $this->assertSame('kb', AiMemory::first()->namespace);
        $this->assertSame([0.1, 0.2, 0.3], AiMemory::first()->embedding);
    }

    public function test_search_ranks_higher_similarity_first(): void
    {
        $store = new EloquentMemoryStore($this->app->make(Embedder::class));

        // Seed two orthogonal vectors + one close-to-query.
        $store->remember(new MemoryItem('kb', 'about cats', [1.0, 0.0, 0.0], source: 'doc'));
        $store->remember(new MemoryItem('kb', 'about dogs', [0.0, 1.0, 0.0], source: 'doc'));
        $store->remember(new MemoryItem('kb', 'about CSV imports', [0.95, 0.1, 0.0], source: 'doc'));

        $results = $store->search(new MemoryQuery(
            namespace: 'kb',
            query: 'csv imports',
            queryEmbedding: [1.0, 0.1, 0.0],
            limit: 3,
        ));

        $this->assertGreaterThanOrEqual(2, count($results));
        $this->assertSame('about CSV imports', $results[0]['item']->content);
        $this->assertGreaterThan($results[1]['score'], $results[0]['score']);
    }

    public function test_search_filters_by_min_score(): void
    {
        $store = new EloquentMemoryStore($this->app->make(Embedder::class));
        $store->remember(new MemoryItem('kb', 'unrelated', [0.0, 1.0, 0.0], source: 'doc'));

        $results = $store->search(new MemoryQuery(
            namespace: 'kb',
            query: 'q',
            queryEmbedding: [1.0, 0.0, 0.0],
            limit: 5,
            minScore: 0.9,
        ));

        $this->assertCount(0, $results);
    }

    public function test_forget_removes_namespace(): void
    {
        $store = new EloquentMemoryStore($this->app->make(Embedder::class));
        $store->remember(new MemoryItem('kb', 'a', [1, 0], source: 'doc'));
        $store->remember(new MemoryItem('kb', 'b', [1, 0], source: 'doc'));
        $store->remember(new MemoryItem('onboarding', 'c', [1, 0], source: 'doc'));

        $count = $store->forget('kb');

        $this->assertSame(2, $count);
        $this->assertSame(1, AiMemory::count());
        $this->assertSame('onboarding', AiMemory::first()->namespace);
    }
}
