<?php

declare(strict_types=1);

namespace App\AI\Events;

use Illuminate\Foundation\Events\Dispatchable;

class FounderBriefGenerated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $dispatchId,
        public readonly array $payload,
    ) {}
}
