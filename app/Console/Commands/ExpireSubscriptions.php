<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Subscription;
use App\Services\Billing\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire
                            {--dry-run : Report what would change without modifying anything}';

    protected $description = 'Finalize subscriptions whose scheduled end-of-period cancellation has arrived.';

    public function handle(BillingService $billing): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $now = Carbon::now();

        if ($dryRun) {
            $pending = Subscription::query()
                ->whereNotNull('canceled_at')
                ->where('canceled_at', '<=', $now)
                ->count();
            $this->line("Would finalize {$pending} subscription(s).");
            return self::SUCCESS;
        }

        $finalized = 0;

Subscription::query()
            ->whereNotNull('canceled_at')
            ->where('canceled_at', '<=', $now)
            ->chunkById(100, function ($subs) use (&$finalized) {
                foreach ($subs as $sub) {
                    $sub->update(['status' => Subscription::STATUS_CANCELED]);
                    $sub->organization->update([
                        'subscription_status' => \App\Models\Organization::SUBSCRIPTION_CANCELED,
                        'subscription_ends_at' => $now,
                    ]);

                    AuditLog::record(
                        $sub->organization,
                        'organization.billing.canceled_finalized',
                        null,
                        $sub->organization,
                        ['subscription_id' => $sub->id],
                    );

                    $finalized++;
                }
            });

        $this->info("Finalized {$finalized} subscription(s).");
        return self::SUCCESS;
    }
}