<?php

declare(strict_types=1);

namespace App\AI\Tools\CustomerSuccess;

use App\AI\Contracts\ToolResult;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use App\Models\Organization;

class GetOnboardingStatusTool extends BaseTool
{
    public function name(): string
    {
        return 'get_onboarding_status';
    }

    public function description(): string
    {
        return 'Score an organization (0-100) for churn risk. Higher = more risk. Signals: no logins, no household imports, no verified payments, incomplete onboarding (no members beyond the owner).';
    }

    public function schema(): array
    {
        return $this->objectSchema([
            'organization_id' => ['type' => 'integer'],
        ], ['organization_id']);
    }

    protected function handle(array $arguments): ToolResult
    {
        $orgId = (int) ($arguments['organization_id'] ?? 0);

        if ($orgId <= 0) {
            return ToolResultPayload::failure('organization_id is required.');
        }

        $org = Organization::query()->with('households', 'users')->find($orgId);

        if (! $org) {
            return ToolResultPayload::failure("Organization {$orgId} not found.");
        }

        $score = 0;
        $signals = [];

        if ($org->households()->count() === 0) {
            $score += 35;
            $signals[] = 'no_households';
        }

        if ($org->users()->count() <= 1) {
            $score += 20;
            $signals[] = 'no_other_members';
        }

        $verifiedPayments = $org->totalContributions();

        if ($org->households()->count() > 0 && $verifiedPayments == 0) {
            $score += 30;
            $signals[] = 'no_verified_payments';
        }

        if ($org->isOnTrial()) {
            $daysLeft = $org->daysUntilTrialEnds() ?? 0;
            if ($daysLeft <= 3) {
                $score += 15;
                $signals[] = "trial_ending_soon:{$daysLeft}d";
            }
        }

        return ToolResultPayload::success([
            'organization_id' => $orgId,
            'score' => min(100, $score),
            'signals' => $signals,
            'subscription_status' => $org->subscription_status,
            'trial_ends_at' => $org->trial_ends_at?->toIso8601String(),
        ]);
    }
}
