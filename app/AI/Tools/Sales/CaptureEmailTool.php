<?php

declare(strict_types=1);

namespace App\AI\Tools\Sales;

use App\AI\Contracts\ToolResult;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use App\Services\LeadCaptureService;

class CaptureEmailTool extends BaseTool
{
    public function __construct(protected readonly LeadCaptureService $leads) {}

    public function name(): string
    {
        return 'capture_email';
    }

    public function description(): string
    {
        return 'Save a visitor email to the leads table and start the day0 follow-up email. Idempotent by email. Call this once the visitor gives you a way to reach them.';
    }

    public function schema(): array
    {
        return $this->objectSchema([
            'email' => ['type' => 'string', 'description' => 'Lead email address.'],
            'name' => ['type' => 'string', 'description' => 'Optional visitor name.'],
            'role' => ['type' => 'string', 'description' => 'Their HOA role: president, treasurer, secretary, board_member, other.'],
            'organization_size' => ['type' => 'string', 'description' => 'household count bucket: small (<30), medium (30-100), large (100+).'],
            'note' => ['type' => 'string'],
        ], ['email']);
    }

    protected function handle(array $arguments): ToolResult
    {
        $email = trim((string) ($arguments['email'] ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ToolResultPayload::failure('Invalid email.');
        }

        $lead = $this->leads->capture(
            email: $email,
            name: $arguments['name'] ?? null,
            role: $arguments['role'] ?? null,
            organizationSize: $arguments['organization_size'] ?? null,
            source: 'sales_chat',
            metadata: ['note' => $arguments['note'] ?? null],
        );

        return ToolResultPayload::success([
            'lead_id' => $lead->id,
            'email' => $lead->email,
            'followup_stage' => $lead->followup_stage,
        ]);
    }
}
