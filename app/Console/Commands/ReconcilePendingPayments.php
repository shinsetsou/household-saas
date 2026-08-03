<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PayMongo\PayMongoClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconcile orphaned pending payments against PayMongo's ground truth.
 *
 * Why this exists: when a user paid on PayMongo's hosted checkout but the
 * `payment.paid` webhook never reached the app (network blip, dashboard
 * misconfiguration, deploy mid-checkout, etc.), the local `payments` row
 * stays `pending` forever and the user is stuck in trialing / past_due.
 * This command walks every pending row, asks PayMongo for the session's
 * real status, and:
 *
 *   - activates the org if PayMongo says the session is paid
 *   - marks the local row `failed` if the session expired or has no payments
 *   - leaves the row alone if PayMongo can't find the session (manual review)
 *
 * Run --dry-run first to see what would change.
 */
class ReconcilePendingPayments extends Command
{
    protected $signature = 'billing:reconcile-pending
                            {--org= : Only reconcile payments for this organization slug}
                            {--payment= : Only reconcile this specific payment id}
                            {--older-than-minutes=0 : Only touch payments older than N minutes (skips fresh in-flight checkouts)}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Reconcile orphaned pending payments against PayMongo.';

    public function handle(PayMongoClient $paymongo): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyOrg = $this->option('org');
        $onlyPayment = $this->option('payment');
        $olderThanMinutes = max(0, (int) $this->option('older-than-minutes'));
        $cutoff = $olderThanMinutes > 0 ? Carbon::now()->subMinutes($olderThanMinutes) : null;

        $query = Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->whereNotNull('paymongo_checkout_session_id')
            ->orderBy('id');

        if ($onlyOrg) {
            $org = Organization::where('slug', $onlyOrg)->first();
            if (! $org) {
                $this->error("No organization with slug '{$onlyOrg}'.");

                return self::FAILURE;
            }
            $query->where('organization_id', $org->id);
        }

        if ($onlyPayment) {
            $query->where('id', $onlyPayment);
        }

        if ($cutoff) {
            $query->where('created_at', '<', $cutoff);
        }

        $pending = $query->get();

        if ($pending->isEmpty()) {
            $this->info('No pending payments to reconcile.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d pending payment(s).',
            $dryRun ? 'Would reconcile' : 'Reconciling',
            $pending->count(),
        ));

        $recovered = 0;
        $failed = 0;
        $skipped = 0;
        $unresolved = 0;

        foreach ($pending as $payment) {
            $row = sprintf(
                '#%d org=%s session=%s amount=%.2f',
                $payment->id,
                $payment->organization?->slug ?? $payment->organization_id,
                $payment->paymongo_checkout_session_id,
                $payment->amount_cents / 100,
            );

            try {
                $session = $paymongo->getCheckoutSession($payment->paymongo_checkout_session_id);
            } catch (\Throwable $e) {
                $this->warn("  {$row} — PayMongo API error: {$e->getMessage()}");
                $unresolved++;

                continue;
            }

            $attributes = data_get($session, 'attributes', []);
            $sessionPayments = data_get($attributes, 'payments', []);
            $paidPayment = collect($sessionPayments)->first(
                fn ($p) => data_get($p, 'attributes.status') === 'paid',
            );

            if ($paidPayment) {
                $paymongoPaymentId = data_get($paidPayment, 'id');

                $this->line("  {$row} — PAID on PayMongo as {$paymongoPaymentId}");

                if ($dryRun) {
                    $recovered++;

                    continue;
                }

                // Orphan rows from the pre-fix code path may have plan_id=NULL.
                // Recover the plan by looking up the unique price (every plan
                // has a distinct price_cents — see BillingService::seedDefaultPlans).
                if (! $payment->plan_id) {
                    $paymongoAmount = (int) data_get($paidPayment, 'attributes.amount');
                    $recoveredPlan = Plan::where('price_cents', $paymongoAmount)->first();

                    if ($recoveredPlan) {
                        $payment->update(['plan_id' => $recoveredPlan->id]);
                        $this->line("    recovered plan_id={$recoveredPlan->id} ({$recoveredPlan->name}) from amount {$paymongoAmount}");
                    } else {
                        $this->warn("    no plan matches amount {$paymongoAmount} — skipping activation");
                        $unresolved++;

                        continue;
                    }
                }

                DB::transaction(function () use ($payment, $paidPayment, $paymongoPaymentId) {
                    $payment->update([
                        'paymongo_payment_id' => $paymongoPaymentId,
                        'status' => Payment::STATUS_PAID,
                        'amount_cents' => data_get($paidPayment, 'attributes.amount', $payment->amount_cents),
                        'paid_at' => Carbon::now(),
                    ]);

                    $organization = $payment->organization;
                    $plan = $payment->plan;

                    if ($organization && $plan) {
                        $periodStart = Carbon::now();
                        $periodEnd = $periodStart->copy()->addMonth();

                        $subscription = Subscription::updateOrCreate(
                            ['organization_id' => $organization->id],
                            [
                                'plan_id' => $plan->id,
                                'status' => Subscription::STATUS_ACTIVE,
                                'current_period_start' => $periodStart,
                                'current_period_end' => $periodEnd,
                                'canceled_at' => null,
                            ],
                        );

                        $organization->update([
                            'plan_id' => $plan->id,
                            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
                            'trial_ends_at' => null,
                            'subscription_ends_at' => $periodEnd,
                        ]);

                        $payment->update(['subscription_id' => $subscription->id]);
                    }
                });

                $recovered++;

                continue;
            }

            // No `paid` payment on the session. Either abandoned, expired, or
            // the user is still on PayMongo's hosted page.
            $sessionStatus = data_get($attributes, 'status'); // 'active' | 'expired' | 'completed'
            $row .= " session_status={$sessionStatus}";

            if (empty($sessionPayments) && $sessionStatus !== 'active') {
                $this->line("  {$row} — abandoned/expired (no payments on session)");

                if (! $dryRun) {
                    $payment->update(['status' => Payment::STATUS_FAILED]);
                }
                $failed++;

                continue;
            }

            $this->line("  {$row} — still in flight (payments=".count($sessionPayments).')');
            $skipped++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s — recovered=%d failed=%d skipped=%d unresolved=%d',
            $dryRun ? 'Dry run complete' : 'Done',
            $recovered,
            $failed,
            $skipped,
            $unresolved,
        ));

        Log::info('billing.reconcile_pending.completed', [
            'dry_run' => $dryRun,
            'recovered' => $recovered,
            'failed' => $failed,
            'skipped' => $skipped,
            'unresolved' => $unresolved,
        ]);

        return self::SUCCESS;
    }
}
