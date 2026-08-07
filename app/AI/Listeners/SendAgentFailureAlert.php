<?php

declare(strict_types=1);

namespace App\AI\Listeners;

use App\AI\Events\AgentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAgentFailureAlert implements ShouldQueue
{
    public string $queue = 'ai';

    public function handle(AgentFailed $event): void
    {
        $to = (string) config('ai.failure_alert_email', config('mail.from.address'));

        Log::error('AI agent failed', [
            'agent' => $event->agent,
            'task' => $event->task,
            'error' => $event->error,
            'context' => $event->context,
        ]);

        if (! $to) {
            return;
        }

        Mail::raw(sprintf(
            "AI agent [%s] failed at task [%s]\n\nError: %s\nContext: %s",
            $event->agent,
            $event->task ?? '(none)',
            $event->error,
            json_encode($event->context, JSON_PRETTY_PRINT),
        ), function ($message) use ($to) {
            $message->to($to)
                ->subject('[BarangayBooks AI] Agent failure: '.$event->agent);
        });
    }
}
