<?php

declare(strict_types=1);

namespace App\AI\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AgentDispatched
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $agent,
        public readonly ?string $task,
        public readonly array $context = [],
    ) {}
}
