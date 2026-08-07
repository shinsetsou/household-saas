<?php

declare(strict_types=1);

namespace App\AI\Jobs;

use App\AI\AgentRequest;
use App\AI\AIManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunAgentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $backoff = 10;

    /**
     * @param  array<string, mixed>  $requestArray
     */
    public function __construct(public array $requestArray)
    {
        $this->onQueue('ai');
    }

    public function handle(AIManager $manager): void
    {
        $request = AgentRequest::fromArray($this->requestArray);

        try {
            $manager->execute($request);
        } catch (\Throwable $e) {
            Log::warning('RunAgentJob failed', [
                'agent' => $request->agent,
                'task' => $request->task,
                'dispatch_id' => $request->dispatchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
