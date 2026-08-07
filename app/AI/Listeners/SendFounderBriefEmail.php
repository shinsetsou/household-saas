<?php

declare(strict_types=1);

namespace App\AI\Listeners;

use App\AI\Events\FounderBriefGenerated;
use App\AI\Mail\FounderBriefMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendFounderBriefEmail implements ShouldQueue
{
    public string $queue = 'ai';

    public function handle(FounderBriefGenerated $event): void
    {
        $to = (string) config('ai.founder_email', config('mail.from.address'));

        if (! $to) {
            Log::warning('Founder brief fired but AI_FOUNDER_EMAIL is unset', [
                'dispatch_id' => $event->dispatchId,
            ]);

            return;
        }

        Mail::to($to)->send(new FounderBriefMail(
            payload: $event->payload,
            dispatchId: $event->dispatchId,
        ));

        Log::info('Founder brief sent', [
            'to' => $to,
            'dispatch_id' => $event->dispatchId,
        ]);
    }
}
