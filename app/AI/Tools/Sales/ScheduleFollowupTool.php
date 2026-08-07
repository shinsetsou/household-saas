<?php

declare(strict_types=1);

namespace App\AI\Tools\Sales;

use App\AI\Contracts\ToolResult;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use App\Services\LeadCaptureService;

class ScheduleFollowupTool extends BaseTool
{
    public function __construct(protected readonly LeadCaptureService $leads) {}

    public function name(): string
    {
        return 'schedule_followup';
    }

    public function description(): string
    {
        return 'Schedule or accelerate the email follow-up cadence for an already-captured lead. Stages: day0 (thank-you, default on capture), day3 (nudge), day7 (demo offer). Use this when the visitor asks to be contacted sooner or wants to skip a step.';
    }

    public function schema(): array
    {
        return $this->objectSchema([
            'email' => ['type' => 'string', 'description' => 'Captured lead email.'],
            'stage' => [
                'type' => 'string',
                'description' => 'Next stage in the cadence to schedule.',
                'enum' => ['day0', 'day3', 'day7'],
            ],
            'note' => ['type' => 'string'],
        ], ['email']);
    }

    protected function handle(array $arguments): ToolResult
    {
        $email = trim((string) ($arguments['email'] ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ToolResultPayload::failure('Invalid email.');
        }

        $stageRaw = (string) ($arguments['stage'] ?? 'day0');
        $stageKey = 'day'.ltrim($stageRaw, 'day0');

        $stageMap = [
            'day0' => 'day0_sent',
            'day3' => 'day3_sent',
            'day7' => 'day7_sent',
        ];

        if (! isset($stageMap[$stageRaw])) {
            return ToolResultPayload::failure('Unknown stage. Use day0, day3, or day7.');
        }

        $targetStage = $stageMap[$stageRaw];

        $lead = $this->leads->capture(
            email: $email,
            role: null,
            organizationSize: null,
            source: 'sales_chat',
            metadata: ['preferred_stage' => $targetStage, 'schedule_note' => $arguments['note'] ?? null],
        );

        return ToolResultPayload::success([
            'lead_id' => $lead->id,
            'email' => $lead->email,
            'current_stage' => $lead->followup_stage,
            'requested_stage' => $targetStage,
        ]);
    }
}
