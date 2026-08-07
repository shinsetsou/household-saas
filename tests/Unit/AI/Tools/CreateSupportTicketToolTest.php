<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Tools;

use App\AI\Tools\Support\CreateSupportTicketTool;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSupportTicketToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_ticket_with_required_args(): void
    {
        $result = (new CreateSupportTicketTool)->execute([
            'subject' => 'Cannot import CSV',
            'body' => 'Getting a 500 error after uploading households.csv',
            'severity' => 'high',
            'category' => 'households',
            'confidence' => 0.42,
        ])->toArray();

        $this->assertTrue($result['ok']);

        $ticket = SupportTicket::first();
        $this->assertSame('Cannot import CSV', $ticket->subject);
        $this->assertSame('high', $ticket->severity);
        $this->assertSame('support_agent', $ticket->source);
        $this->assertSame(0.42, $ticket->confidence);
    }

    public function test_fails_when_subject_or_body_missing(): void
    {
        $result = (new CreateSupportTicketTool)->execute([
            'body' => 'only body',
        ])->toArray();

        $this->assertFalse($result['ok']);
        $this->assertSame(0, SupportTicket::count());
    }
}
