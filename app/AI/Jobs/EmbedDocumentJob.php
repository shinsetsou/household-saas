<?php

declare(strict_types=1);

namespace App\AI\Jobs;

use App\AI\AIManager;
use App\AI\MemoryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EmbedDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    /**
     * @param  array<int, string>  $chunks
     */
    public function __construct(
        public string $namespace,
        public string $source,
        public ?string $sourceId,
        public array $chunks,
        public array $metadata = [],
        public ?int $organizationId = null,
    ) {
        $this->onQueue('ai');
    }

    public function handle(AIManager $manager): void
    {
        $store = $manager->resolveMemoryStore();
        $embedder = $manager->resolveEmbedder();

        foreach ($this->chunks as $i => $chunk) {
            $vector = $embedder->embed($chunk);

            $store->remember(new MemoryItem(
                namespace: $this->namespace,
                content: $chunk,
                embedding: $vector,
                source: $this->source,
                sourceId: $this->sourceId !== null ? $this->sourceId.':'.$i : (string) $i,
                embeddingModel: $embedder->modelName(),
                metadata: array_merge($this->metadata, ['chunk_index' => $i]),
                organizationId: $this->organizationId,
            ));
        }
    }
}
