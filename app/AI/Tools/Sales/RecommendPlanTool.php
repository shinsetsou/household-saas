<?php

declare(strict_types=1);

namespace App\AI\Tools\Sales;

use App\AI\Contracts\ToolResult;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use App\Models\Plan;

class RecommendPlanTool extends BaseTool
{
    public function name(): string
    {
        return 'recommend_plan';
    }

    public function description(): string
    {
        return 'Return the current plan catalog. Use this whenever you need to recommend a plan — never invent prices.';
    }

    public function schema(): array
    {
        return $this->objectSchema([]);
    }

    protected function handle(array $arguments): ToolResult
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $p) => [
                'slug' => $p->slug,
                'name' => $p->name,
                'description' => $p->description,
                'price_cents' => $p->price_cents,
                'currency' => $p->currency,
                'interval' => $p->interval,
                'max_households' => $p->max_households,
                'csv_import' => (bool) $p->csv_import,
                'api_access' => (bool) $p->api_access,
            ])
            ->all();

        return ToolResultPayload::success(['plans' => $plans]);
    }
}
