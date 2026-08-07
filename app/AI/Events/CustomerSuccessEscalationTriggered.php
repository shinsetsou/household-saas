<?php

declare(strict_types=1);

namespace App\AI\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CustomerSuccessEscalationTriggered
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $signals
     */
    public function __construct(
        public readonly int $organizationId,
        public readonly int $score,
        public readonly array $signals,
    ) {}
}
