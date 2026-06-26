<?php

namespace App\Console\Commands;

use App\Mail\TrialEndingMail;
use App\Mail\TrialExpiredMail;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class ExpireTrials extends Command
{
    protected $signature = 'trials:expire
                            {--dry-run : Report what would change without modifying anything}';

    protected $description = 'Expire ended trials and send warning emails at T-7 and T-1 days.';

    /** @var array<int, int> */
    protected array $warningDays = [7, 1];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $now = Carbon::now();

        $expiredCount = $this->expireOverdue($now, $dryRun);
        $warningCount = $this->sendWarnings($now, $dryRun);

        $this->info(sprintf(
            '%s %d trial(s), %s %d warning email(s).',
            $dryRun ? 'Would expire' : 'Expired',
            $expiredCount,
            $dryRun ? 'would send' : 'sent',
            $warningCount,
        ));

        return self::SUCCESS;
    }

    protected function expireOverdue(Carbon $now, bool $dryRun): int
    {
        $count = 0;

        Organization::query()
            ->where('subscription_status', Organization::SUBSCRIPTION_TRIALING)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', $now)
            ->chunkById(100, function ($orgs) use ($dryRun, &$count) {
                foreach ($orgs as $org) {
                    if ($dryRun) {
                        $this->line("Would expire: {$org->name} (#{$org->id})");
                        $count++;
                        continue;
                    }

                    $org->update([
                        'subscription_status' => Organization::SUBSCRIPTION_PAST_DUE,
                    ]);

                    $this->sendToAdmins($org, new TrialExpiredMail($org));

                    AuditLog::record(
                        $org,
                        'organization.billing.trial_expired',
                        null,
                        $org,
                        [
                            'trial_ends_at' => $org->trial_ends_at?->toIso8601String(),
                        ],
                    );

                    $count++;
                }
            });

        return $count;
    }

    protected function sendWarnings(Carbon $now, bool $dryRun): int
    {
        $count = 0;

        foreach ($this->warningDays as $days) {
            $windowStart = $now->copy()->addDays($days)->startOfDay();
            $windowEnd = $now->copy()->addDays($days)->endOfDay();

            $orgs = Organization::query()
                ->where('subscription_status', Organization::SUBSCRIPTION_TRIALING)
                ->whereNotNull('trial_ends_at')
                ->whereBetween('trial_ends_at', [$windowStart, $windowEnd])
                ->get();

            foreach ($orgs as $org) {
                if ($org->hasWarningBeenSent($days)) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("Would warn T-{$days}: {$org->name} (#{$org->id})");
                    $count++;
                    continue;
                }

                $this->sendToAdmins($org, new TrialEndingMail($org, $days));

                $org->markWarningSent($days);

                AuditLog::record(
                    $org,
                    'organization.billing.trial_warning_sent',
                    null,
                    $org,
                    ['days_remaining' => $days],
                );

                $count++;
            }
        }

        return $count;
    }

    protected function sendToAdmins(Organization $org, $mailable): void
    {
        $emails = $org->users()
            ->wherePivot('role', \App\Enums\OrganizationRole::Admin->value)
            ->pluck('users.email')
            ->all();

        if ($emails === []) {
            return;
        }

        Mail::to($emails)->send($mailable);
    }
}