<?php

declare(strict_types=1);

namespace App\AI\Jobs;

use App\Mail\SalesFollowupMail;
use App\Models\Lead;
use App\Services\LeadCaptureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

/**
 * Stages the chain `day0 -> day3 -> day7`. Each step dispatches the next
 * via LeadCaptureService::markFollowupStageSent() once the queued mail
 * is delivered. Failures are logged and retried per Laravel queue policy
 * (exponential backoff); the chain resumes from the last successful stage.
 */
class SendSalesFollowupEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        public int $leadId,
        public string $stage,
    ) {
        $this->onQueue('ai');
    }

    public function handle(LeadCaptureService $service): void
    {
        $lead = Lead::find($this->leadId);

        if (! $lead) {
            Log::warning('sales.followup.missing_lead', [
                'lead_id' => $this->leadId,
                'stage' => $this->stage,
            ]);

            return;
        }

        if ($lead->followup_stage !== null && $this->stageIsPast($lead->followup_stage)) {
            Log::info('sales.followup.skipped_past_stage', [
                'lead_id' => $lead->id,
                'current_stage' => $lead->followup_stage,
                'requested_stage' => $this->stage,
            ]);

            return;
        }

        try {
            Mail::to($lead->email)->send(new SalesFollowupMail($lead, $this->stage));
        } catch (TransportException $e) {
            Log::error('sales.followup.transport_failure', [
                'lead_id' => $lead->id,
                'stage' => $this->stage,
                'error' => $e->getMessage(),
            ]);
            $this->release($this->backoff);

            return;
        }

        $service->markFollowupStageSent($lead->id, $this->stage);
    }

    protected function stageIsPast(string $currentStage): bool
    {
        $idx = array_search($currentStage, Lead::FOLLOWUP_CADENCE, true);
        $target = array_search($this->stage, Lead::FOLLOWUP_CADENCE, true);

        if ($idx === false || $target === false) {
            return false;
        }

        return $target <= $idx;
    }
}
