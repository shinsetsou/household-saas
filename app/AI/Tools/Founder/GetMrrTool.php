<?php

declare(strict_types=1);

namespace App\AI\Tools\Founder;

use App\AI\Contracts\ToolResult;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use App\Models\Subscription;

class GetMrrTool extends BaseTool
{
    public function name(): string
    {
        return 'get_mrr';
    }

    public function description(): string
    {
        return 'Compute current MRR (monthly recurring revenue) in USD cents. Active subscriptions only.';
    }

    public function schema(): array
    {
        return $this->objectSchema([]);
    }

    protected function handle(array $arguments): ToolResult
    {
        $active = Subscription::query()
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->with('plan')
            ->get();

        $mrr = 0;

        foreach ($active as $sub) {
            if (! $sub->plan) {
                continue;
            }

            $mrr += (int) $sub->plan->price_cents;
        }

        return ToolResultPayload::success([
            'mrr_usd_cents' => $mrr,
            'active_subscriptions' => $active->count(),
        ]);
    }
}
