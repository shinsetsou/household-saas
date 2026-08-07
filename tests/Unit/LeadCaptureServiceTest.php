<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\AI\Jobs\SendSalesFollowupEmail;
use App\Models\Lead;
use App\Services\LeadCaptureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class LeadCaptureServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_capture_creates_lead_and_dispatches_day0_followup(): void
    {
        Bus::fake();

        $service = app(LeadCaptureService::class);
        $lead = $service->capture(email: 'jane@example.com', role: 'treasurer');

        $this->assertSame('jane@example.com', $lead->email);
        $this->assertSame('treasurer', $lead->role);
        $this->assertNull($lead->followup_stage);
        $this->assertDatabaseHas('leads', ['email' => 'jane@example.com']);

        Bus::assertDispatched(SendSalesFollowupEmail::class, function ($job) use ($lead) {
            return $job->stage === Lead::FOLLOWUP_DAY_0
                && $job->leadId === $lead->id;
        });
    }

    public function test_capture_is_idempotent_on_email(): void
    {
        Bus::fake();

        $service = app(LeadCaptureService::class);
        $service->capture(email: 'jane@example.com', role: 'treasurer');
        $service->capture(email: 'jane@example.com', role: 'president');

        $this->assertSame(1, Lead::count());
        $this->assertSame('president', Lead::first()->role);
        Bus::assertDispatchedTimes(SendSalesFollowupEmail::class, 1);
    }

    public function test_next_stage_progression(): void
    {
        $service = app(LeadCaptureService::class);

        $this->assertSame(Lead::FOLLOWUP_DAY_0, $service->nextStage(null));
        $this->assertSame(Lead::FOLLOWUP_DAY_3, $service->nextStage(Lead::FOLLOWUP_DAY_0));
        $this->assertSame(Lead::FOLLOWUP_DAY_7, $service->nextStage(Lead::FOLLOWUP_DAY_3));
        $this->assertNull($service->nextStage(Lead::FOLLOWUP_DAY_7));
    }

    public function test_mark_followup_stage_sent_advances_chain(): void
    {
        Bus::fake();

        $service = app(LeadCaptureService::class);
        $lead = $service->capture(email: 'jane@example.com');

        Bus::assertDispatched(SendSalesFollowupEmail::class, function ($j) use ($lead) {
            return $j->stage === Lead::FOLLOWUP_DAY_0 && $j->leadId === $lead->id;
        });
        Bus::assertDispatchedTimes(SendSalesFollowupEmail::class, 1);

        $service->markFollowupStageSent($lead->id, Lead::FOLLOWUP_DAY_0);

        $this->assertSame(Lead::FOLLOWUP_DAY_0, $lead->fresh()->followup_stage);
        $this->assertNotNull($lead->fresh()->last_followup_at);

        Bus::assertDispatched(SendSalesFollowupEmail::class, function ($j) use ($lead) {
            return $j->stage === Lead::FOLLOWUP_DAY_3 && $j->leadId === $lead->id;
        });
    }

    public function test_mark_followup_returns_null_for_missing_lead(): void
    {
        $service = app(LeadCaptureService::class);

        $this->assertNull($service->markFollowupStageSent(99999, Lead::FOLLOWUP_DAY_0));
    }

    public function test_human_followup_records_metadata(): void
    {
        $service = app(LeadCaptureService::class);
        $lead = $service->capture(email: 'jane@example.com');

        $service->requestHumanFollowup($lead->id, 'Wants custom pricing');

        $lead->refresh();
        $this->assertTrue($lead->metadata['human_followup']);
        $this->assertSame('Wants custom pricing', $lead->metadata['human_followup_note']);
        $this->assertArrayHasKey('human_followup_requested_at', $lead->metadata);
    }
}
