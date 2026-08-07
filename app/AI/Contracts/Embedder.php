<?php

declare(strict_types=1);

namespace App\AI\Contracts;

interface Embedder
{
    /**
     * Produce a fixed-length embedding vector for the given text. Implementations
     * should batch internally if given an array.
     *
     * @return array<int, float>
     */
    public function embed(string $text): array;

    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedBatch(array $texts): array;

    /**
     * Provider identifier + model name, used to record provenance in ai_memories.
     */
    public function modelName(): string;

    /**
     * Output vector dimensionality (e.g. 1536 for text-embedding-3-small).
     */
    public function dimensions(): int;
}
