<?php

declare(strict_types=1);

namespace App\AI;

use App\AI\Contracts\AIProvider;
use App\AI\Contracts\Embedder;

class ProviderEmbedder implements Embedder
{
    public function __construct(
        protected readonly AIProvider $provider,
        protected readonly string $modelName,
        protected readonly int $dimensions,
    ) {}

    public function embed(string $text): array
    {
        return $this->provider->embed($text);
    }

    public function embedBatch(array $texts): array
    {
        $vectors = [];

        foreach ($texts as $text) {
            $vectors[] = $this->embed($text);
        }

        return $vectors;
    }

    public function modelName(): string
    {
        return $this->modelName;
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }
}
