<?php

declare(strict_types=1);

namespace App\AI\Jobs;

use App\AI\AIManager;
use App\AI\Events\CustomerSuccessEscalationTriggered;
use App\AI\Tools\CustomerSuccess\GetOnboardingStatusTool;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CustomerSuccessScanJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct() {}

    public function handle(AIManager $manager): void
    {
        $threshold = (int) config('ai.agents.customer_success.escalation_score', 70);

        $orgs = Organization::query()
            ->whereIn('subscription_status', [
                Organization::SUBSCRIPTION_TRIALING,
                Organization::SUBSCRIPTION_ACTIVE,
                Organization::SUBSCRIPTION_PAST_DUE,
            ])
            ->get();

        foreach ($orgs as $org) {
            $tool = new GetOnboardingStatusTool;
            $result = $tool->execute(['organization_id' => $org->id])->toArray();

            if (! $result['ok']) {
                continue;
            }

            $score = (int) ($result['data']['score'] ?? 0);

            if ($score >= $threshold) {
                CustomerSuccessEscalationTriggered::dispatch($org->id, $score, [
                    'reason' => 'score >= threshold',
                    'signals' => $result['data']['signals'] ?? [],
                    'organization_name' => $org->name,
                ]);
            }
        }
    }
}
