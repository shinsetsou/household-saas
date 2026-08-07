<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\AI\Contracts\Embedder;
use App\AI\Memory\EloquentMemoryStore;
use App\AI\MemoryItem;
use App\AI\Services\SupportAgentService;
use App\Models\Organization;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAgentRagTest extends TestCase
{
    use RefreshDatabase;

    public function test_high_confidence_match_returns_kb_answer_without_ticket(): void
    {
        // The dev/test embedder is deterministic by text. To force a high
        // cosine similarity we seed the KB with the exact query string.
        $question = 'How do I import households via CSV?';
        $this->seedKb($question, [
            'title' => 'CSV Imports',
            'url' => '/docs/csv-imports',
        ]);

        $service = $this->app->make(SupportAgentService::class);

        $result = $service->ask($question);

        $this->assertNull($result['ticket']);
        $this->assertGreaterThan(0.5, $result['confidence']);
        $this->assertStringContainsString('/docs/csv-imports', $result['reply']);
    }

    public function test_low_confidence_creates_support_ticket(): void
    {
        // KB only has unrelated content.
        $this->seedKb('The history of cats in ancient Egypt.', ['title' => 'Cats', 'url' => '/docs/cats']);

        $service = $this->app->make(SupportAgentService::class);

        $org = Organization::create(['name' => 'Test HOA', 'slug' => 'test', 'monthly_due_amount' => 100]);

        $result = $service->ask(
            question: 'How do refunds work?',
            org: $org,
        );

        $this->assertNotNull($result['ticket']);
        $this->assertSame('open', $result['ticket']->status);
        $this->assertSame($org->id, $result['ticket']->organization_id);
        $this->assertSame(1, SupportTicket::count());
    }

    public function test_threshold_is_configurable(): void
    {
        $this->seedKb('A specific answer about CSV imports.', ['url' => '/docs/csv']);

        $service = $this->app->make(SupportAgentService::class);

        // With an impossibly high threshold, even a match escalates.
        config()->set('ai.agents.support.rag_confidence_threshold', 0.999);

        $result = $service->ask('How do I import households?');

        $this->assertNotNull($result['ticket']);
    }

    public function test_empty_kb_returns_low_confidence(): void
    {
        $service = $this->app->make(SupportAgentService::class);

        $result = $service->ask('Anything');

        $this->assertNotNull($result['ticket']);
        $this->assertSame(0.0, $result['confidence']);
    }

    protected function seedKb(string $content, array $metadata = []): void
    {
        $embedder = $this->app->make(Embedder::class);
        $store = new EloquentMemoryStore($embedder);

        $store->remember(new MemoryItem(
            namespace: 'kb',
            content: $content,
            embedding: $embedder->embed($content),
            source: 'doc',
            sourceId: 'kb-test-'.uniqid(),
            metadata: $metadata,
        ));
    }
}
