<?php

declare(strict_types=1);

namespace App\AI\Tools\Support;

use App\AI\Contracts\ToolResult;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use App\Models\SupportTicket;
use Illuminate\Support\Str;

class CreateSupportTicketTool extends BaseTool
{
    public function name(): string
    {
        return 'create_support_ticket';
    }

    public function description(): string
    {
        return 'Create a support ticket. Use this when KB confidence is below 0.55 OR when the user explicitly asks for a human.';
    }

    public function schema(): array
    {
        return $this->objectSchema([
            'subject' => ['type' => 'string', 'description' => 'Short ticket subject.'],
            'body' => ['type' => 'string', 'description' => 'Full description of the issue.'],
            'category' => ['type' => 'string', 'enum' => ['billing', 'onboarding', 'households', 'payments', 'reports', 'other']],
            'severity' => ['type' => 'string', 'enum' => ['low', 'normal', 'high', 'urgent']],
            'organization_id' => ['type' => 'integer'],
            'user_id' => ['type' => 'integer'],
            'kb_citations' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'KB sources that were searched before escalating.',
            ],
            'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
            'dispatch_id' => ['type' => 'string'],
        ], ['subject', 'body']);
    }

    protected function handle(array $arguments): ToolResult
    {
        $subject = trim((string) ($arguments['subject'] ?? ''));
        $body = trim((string) ($arguments['body'] ?? ''));

        if ($subject === '' || $body === '') {
            return ToolResultPayload::failure('Both subject and body are required.');
        }

        $ticket = SupportTicket::create([
            'uuid' => (string) Str::uuid(),
            'subject' => $subject,
            'body' => $body,
            'category' => $arguments['category'] ?? null,
            'severity' => $arguments['severity'] ?? 'normal',
            'source' => 'support_agent',
            'organization_id' => $arguments['organization_id'] ?? null,
            'user_id' => $arguments['user_id'] ?? null,
            'kb_citations' => $arguments['kb_citations'] ?? [],
            'confidence' => isset($arguments['confidence']) ? (float) $arguments['confidence'] : null,
            'dispatch_id' => $arguments['dispatch_id'] ?? null,
            'status' => 'open',
        ]);

        return ToolResultPayload::success([
            'ticket_id' => $ticket->id,
            'uuid' => $ticket->uuid,
            'status' => $ticket->status,
        ]);
    }
}
