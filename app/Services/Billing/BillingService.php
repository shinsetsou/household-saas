<?php

namespace App\Services\Billing;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PayMongo\PayMongoClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BillingService
{
    public function __construct(protected PayMongoClient $paymongo) {}

    public function startCheckout(Organization $organization, Plan $plan): array
    {
        $success = $this->buildReturnUrl('success', $organization);
        $cancel = $this->buildReturnUrl('cancel', $organization);

        $session = $this->paymongo->createCheckoutSession([
            'billing' => [
                'name' => 'BarangayBooks — '.$plan->name,
                'email' => $organization->users()->first()?->email,
                'statement_descriptor' => 'BarangayBooks',
            ],
            'send_email_receipts' => true,
            'show_description' => true,
            'show_line_items' => true,
            'line_items' => [[
                'currency' => $plan->currency,
                'amount' => $plan->price_cents,
                'name' => $plan->name.' (monthly)',
                'quantity' => 1,
                'description' => $plan->description,
            ]],
            'payment_method_types' => ['card', 'gcash', 'paymaya', 'grab_pay'],
            'success_url' => $success,
            'cancel_url' => $cancel,
            'reference_number' => (string) Str::uuid(),
            'metadata' => [
                'organization_id' => (string) $organization->id,
                'organization_slug' => $organization->slug,
                'plan_id' => (string) $plan->id,
                'plan_slug' => $plan->slug,
            ],
        ]);

        $checkoutId = data_get($session, 'id');
        $checkoutUrl = data_get($session, 'attributes.checkout_url');

        Payment::create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'amount_cents' => $plan->price_cents,
            'currency' => $plan->currency,
            'status' => Payment::STATUS_PENDING,
            'description' => 'Checkout for '.$plan->name,
            'paymongo_checkout_session_id' => $checkoutId,
            'payload' => $session,
        ]);

        return [
            'checkout_id' => $checkoutId,
            'checkout_url' => $checkoutUrl,
        ];
    }

    public function handlePaymentPaid(array $payload): void
    {
        $paymentId = data_get($payload, 'id');
        $attributes = data_get($payload, 'attributes', []);

        // Source of truth for which org + plan this payment is for is the
        // local `payments` row we created in `startCheckout()` (we set both
        // `organization_id` and `plan_id` there). The PayMongo payment.paid
        // event's metadata is PayMongo's own (`pm_reference_number`), not
        // ours — so we look up the local row by `paymongo_payment_id` first,
        // then fall back to the checkout session id.
        $payment = null;
        if ($paymentId) {
            $payment = Payment::where('paymongo_payment_id', $paymentId)->first();
        }

        if (! $payment) {
            $sessionId = data_get($attributes, 'payment_intent_id')
                ?: data_get($payload, 'payment_intent_id');

            if ($sessionId) {
                $payment = Payment::where('paymongo_checkout_session_id', $sessionId)->first();
            }
        }

        if (! $payment) {
            // Last-resort metadata fallback. Some upstream callers / older
            // flows may still stamp `organization_id` + `plan_id` into the
            // payment metadata; honor that for backward compat.
            $metadata = data_get($attributes, 'metadata', []);
            $organizationId = $metadata['organization_id'] ?? null;
            $planId = $metadata['plan_id'] ?? null;

            if ($organizationId && $planId) {
                $organization = Organization::find($organizationId);
                $plan = Plan::find($planId);

                if ($organization && $plan) {
                    DB::transaction(function () use ($organization, $plan, $payload, $paymentId, $attributes) {
                        $this->activateForOrganization(
                            $organization,
                            $plan,
                            $payload,
                            $paymentId,
                            $attributes,
                            null,
                        );
                    });

                    return;
                }
            }

            Log::warning('payment.paid webhook: no matching local payment row', [
                'paymongo_payment_id' => $paymentId,
                'paymongo_payment_intent_id' => data_get($attributes, 'payment_intent_id'),
                'amount' => data_get($attributes, 'amount'),
            ]);

            return;
        }

        // Re-fetch the org + plan from the local row, not from the webhook.
        $organization = $payment->organization;
        $plan = $payment->plan;

        if (! $organization || ! $plan) {
            Log::warning('payment.paid webhook: payment row missing org/plan', [
                'paymongo_payment_id' => $paymentId,
                'payment_id' => $payment->id,
                'organization_id' => $payment->organization_id,
                'plan_id' => $payment->plan_id,
            ]);

            return;
        }

        // Idempotency: if this payment is already marked paid, skip the
        // second activation. PayMongo retries webhooks aggressively.
        if ($payment->status === Payment::STATUS_PAID) {
            return;
        }

        DB::transaction(function () use ($payment, $organization, $plan, $payload, $paymentId, $attributes) {
            $this->activateForOrganization(
                $organization,
                $plan,
                $payload,
                $paymentId,
                $attributes,
                $payment,
            );
        });
    }

    public function activateForOrganization(
        Organization $organization,
        Plan $plan,
        array $payload,
        ?string $paymentId,
        array $attributes,
        ?Payment $existingPayment,
    ): void {
        $payment = $existingPayment;

        if (! $payment) {
            $payment = Payment::create([
                'organization_id' => $organization->id,
                'plan_id' => $plan->id,
                'paymongo_payment_id' => $paymentId,
                'amount_cents' => data_get($attributes, 'amount', $plan->price_cents),
                'currency' => data_get($attributes, 'currency', $plan->currency),
                'status' => Payment::STATUS_PAID,
                'description' => 'Payment for '.$plan->name,
                'paid_at' => Carbon::now(),
                'payload' => $payload,
            ]);
        } else {
            $payment->update([
                'paymongo_payment_id' => $paymentId,
                'status' => Payment::STATUS_PAID,
                'amount_cents' => data_get($attributes, 'amount', $payment->amount_cents),
                'paid_at' => Carbon::now(),
                'payload' => $payload,
            ]);
        }

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

        $payment->update(['subscription_id' => $subscription->id]);

        $organization->update([
            'plan_id' => $plan->id,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'trial_ends_at' => null,
            'subscription_ends_at' => $periodEnd,
        ]);
    }

    public function cancelSubscription(Organization $organization): void
    {
        DB::transaction(function () use ($organization) {
            $subscription = $organization->subscription;

            if ($subscription && $subscription->status !== Subscription::STATUS_CANCELED) {
                // Schedule cancellation at the end of the current billing period.
                // The daily `subscriptions:expire` command flips status to canceled
                // once `current_period_end` actually arrives.
                $periodEnd = $subscription->current_period_end ?: Carbon::now();

                $subscription->update([
                    'canceled_at' => $periodEnd,
                ]);

                $organization->update([
                    'subscription_ends_at' => $periodEnd,
                ]);
            }
        });
    }

    public function resumeSubscription(Organization $organization): void
    {
        DB::transaction(function () use ($organization) {
            $subscription = $organization->subscription;

            if ($subscription && $subscription->canceled_at) {
                $subscription->update(['canceled_at' => null]);
            }

            $organization->update([
                'subscription_ends_at' => null,
            ]);
        });
    }

    /**
     * End-of-period cancellations whose `current_period_end` is now in the past.
     * Called by the daily `subscriptions:expire` command. Idempotent.
     */
    public function finalizeDueCancellations(): int
    {
        $now = Carbon::now();
        $count = 0;

        Subscription::query()
            ->whereNotNull('canceled_at')
            ->where('canceled_at', '<=', $now)
            ->chunkById(100, function ($subs) use ($now, &$count) {
                foreach ($subs as $sub) {
                    $sub->update(['status' => Subscription::STATUS_CANCELED]);
                    $sub->organization->update([
                        'subscription_status' => Organization::SUBSCRIPTION_CANCELED,
                        'subscription_ends_at' => $now,
                    ]);
                    $count++;
                }
            });

        return $count;
    }

    public function startTrial(Organization $organization): void
    {
        $trialDays = (int) config('paymongo.trial_days', 14);

        // Make sure the Community plan row exists before we attach it. This
        // runs even on the (rare) boot where AppServiceProvider hasn't
        // auto-seeded yet — e.g. queued jobs, tinker, fresh test DB.
        $communityPlan = Plan::where('slug', 'community')->first();
        if (! $communityPlan) {
            $this->seedDefaultPlans();
            $communityPlan = Plan::where('slug', 'community')->first();
        }

        if (! $communityPlan) {
            return;
        }

        $organization->update([
            'plan_id' => $communityPlan->id,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'trial_ends_at' => Carbon::now()->addDays($trialDays),
            'subscription_ends_at' => null,
        ]);

        Subscription::create([
            'organization_id' => $organization->id,
            'plan_id' => $communityPlan->id,
            'status' => Subscription::STATUS_TRIALING,
            'current_period_start' => Carbon::now(),
            'current_period_end' => Carbon::now()->addDays($trialDays),
        ]);
    }

    public function ensurePlansExist(): bool
    {
        if (! Schema::hasTable('plans')) {
            return false;
        }

        if (Plan::query()->where('is_active', true)->exists()) {
            return false;
        }

        $this->seedDefaultPlans();

        return true;
    }

    public function seedDefaultPlans(): void
    {
        $plans = [
            [
                'slug' => 'community',
                'name' => 'Community',
                'description' => 'For small HOAs getting started.',
                'price_cents' => 19900,
                'max_households' => 50,
                'csv_import' => true,
                'api_access' => false,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'slug' => 'barangay',
                'name' => 'Barangay',
                'description' => 'CSV import, photo verification, and full reports.',
                'price_cents' => 49900,
                'max_households' => 250,
                'csv_import' => true,
                'api_access' => false,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'slug' => 'city',
                'name' => 'City',
                'description' => 'Unlimited households and API access for multi-HOA portfolios.',
                'price_cents' => 99900,
                'max_households' => null,
                'csv_import' => true,
                'api_access' => true,
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }

    protected function buildReturnUrl(string $type, Organization $organization): string
    {
        $configured = $type === 'success'
            ? (string) config('paymongo.success_url')
            : (string) config('paymongo.cancel_url');

        // The example env files use `${APP_URL}` placeholders, but PHP's env()
        // does NOT interpolate them — the literal string is shipped to PayMongo
        // and the hosted checkout then redirects the user to a broken URL.
        // Fall back to a runtime-resolved absolute URL whenever the configured
        // value is empty or still contains an unresolved placeholder.
        if ($configured === '' || preg_match('/\$\{[A-Z_]+\}/', $configured)) {
            $configured = url('/billing/return/'.$type);
        }

        return $configured.'?organization='.$organization->slug;
    }
}
