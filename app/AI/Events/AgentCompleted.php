<?php

declare(strict_types=1);

namespace App\AI\Events;

use App\AI\AgentResponse;
use Illuminate\Foundation\Events\Dispatchable;

class AgentCompleted
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $agent,
        public readonly ?string $task,
        public readonly AgentResponse $response,
        public readonly array $context = [],
    ) {}
}
