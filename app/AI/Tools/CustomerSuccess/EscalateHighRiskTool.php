<?php

declare(strict_types=1);

namespace App\AI\Tools\CustomerSuccess;

use App\AI\Contracts\ToolResult;
use App\AI\Events\CustomerSuccessEscalationTriggered;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use Illuminate\Support\Facades\Log;

class EscalateHighRiskTool extends BaseTool
{
    public function name(): string
    {
        return 'escalate_high_risk';
    }

    public function description(): string
    {
        return 'Escalate a high-risk customer to the founder. Emits CustomerSuccessEscalationTriggered so an alert can be delivered.';
    }

    public function schema(): array
    {
        return $this->objectSchema([
            'organization_id' => ['type' => 'integer'],
            'score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
            'reason' => ['type' => 'string'],
            'signals' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
        ], ['organization_id', 'score', 'reason']);
    }

    protected function handle(array $arguments): ToolResult
    {
        $orgId = (int) ($arguments['organization_id'] ?? 0);
        $score = (int) ($arguments['score'] ?? 0);
        $reason = (string) ($arguments['reason'] ?? '');
        $signals = (array) ($arguments['signals'] ?? []);

        if ($orgId <= 0) {
            return ToolResultPayload::failure('organization_id is required.');
        }

        CustomerSuccessEscalationTriggered::dispatch($orgId, $score, [
            'reason' => $reason,
            'signals' => $signals,
        ]);

        Log::info('Customer Success escalation', [
            'organization_id' => $orgId,
            'score' => $score,
            'reason' => $reason,
        ]);

        return ToolResultPayload::success([
            'escalated' => true,
            'organization_id' => $orgId,
        ]);
    }
}
