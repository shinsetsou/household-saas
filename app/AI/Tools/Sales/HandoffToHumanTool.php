<?php

declare(strict_types=1);

namespace App\AI\Tools\Sales;

use App\AI\Contracts\ToolResult;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use App\Services\LeadCaptureService;
use Illuminate\Support\Facades\Event;

class HandoffToHumanTool extends BaseTool
{
    public function __construct(protected readonly LeadCaptureService $leads) {}

    public function name(): string
    {
        return 'handoff_to_human';
    }

    public function description(): string
    {
        return 'Hand the conversation off to a human sales rep. Use this when the visitor explicitly asks to talk to a person, or when intent signals suggest they want a custom quote. The lead is flagged for human follow-up and a notification is fired.';
    }

    public function schema(): array
    {
        return $this->objectSchema([
            'email' => ['type' => 'string', 'description' => 'Visitor email if captured.'],
            'reason' => ['type' => 'string', 'description' => 'Why a human is needed (eg: custom pricing, complex HOA structure, integration request).'],
            'summary' => ['type' => 'string', 'description' => 'A short summary the founder can read in 30 seconds.'],
        ]);
    }

    protected function handle(array $arguments): ToolResult
    {
        $reason = trim((string) ($arguments['reason'] ?? 'visitor-requested'));
        $summary = trim((string) ($arguments['summary'] ?? ''));
        $email = trim((string) ($arguments['email'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $lead = $this->leads->capture(email: $email, source: 'sales_chat_handoff');
            $this->leads->requestHumanFollowup($lead->id, $summary);
            $leadId = $lead->id;
        } else {
            $leadId = null;
        }

        Event::dispatch('sales.handoff_requested', [
            'email' => $email ?: null,
            'reason' => $reason,
            'summary' => $summary,
            'lead_id' => $leadId,
        ]);

        return ToolResultPayload::success([
            'lead_id' => $leadId,
            'email' => $email ?: null,
            'notified' => true,
        ]);
    }
}
