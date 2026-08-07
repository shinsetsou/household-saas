<?php

declare(strict_types=1);

namespace App\AI\Listeners;

use App\AI\Events\CustomerSuccessEscalationTriggered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyFounderOfEscalation implements ShouldQueue
{
    public string $queue = 'ai';

    public function handle(CustomerSuccessEscalationTriggered $event): void
    {
        $to = (string) config('ai.founder_email', config('mail.from.address'));

        if (! $to) {
            Log::warning('CS escalation fired but AI_FOUNDER_EMAIL is unset', [
                'organization_id' => $event->organizationId,
            ]);

            return;
        }

        Log::warning('Customer Success escalation', [
            'organization_id' => $event->organizationId,
            'score' => $event->score,
            'signals' => $event->signals,
        ]);

        Mail::raw(
            sprintf(
                "Customer Success escalation\n\nOrganization: %s\nScore: %d/100\nSignals: %s",
                $event->signals['organization_name'] ?? ('#'.$event->organizationId),
                $event->score,
                json_encode($event->signals['signals'] ?? [], JSON_PRETTY_PRINT),
            ),
            function ($message) use ($to, $event) {
                $message->to($to)
                    ->subject(sprintf('[BarangayBooks] High churn risk: org #%d (%d/100)', $event->organizationId, $event->score));
            }
        );
    }
}
