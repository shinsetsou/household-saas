<?php

namespace App\Services\Billing;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PayMongo\PayMongoClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingService
{
    public function __construct(protected PayMongoClient $paymongo)
    {
    }

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
        $metadata = data_get($attributes, 'metadata', []);

        $organizationId = $metadata['organization_id'] ?? null;
        $planId = $metadata['plan_id'] ?? null;

        if (! $organizationId || ! $planId) {
            return;
        }

        $organization = Organization::find($organizationId);
        $plan = Plan::find($planId);

        if (! $organization || ! $plan) {
            return;
        }

        DB::transaction(function () use ($organization, $plan, $payload, $paymentId) {
            $payment = Payment::where('organization_id', $organization->id)
                ->where('paymongo_payment_id', $paymentId)
                ->first();

            if (! $payment) {
                $sessionId = data_get($payload, 'id');
                $sourceSessionId = data_get($payload, 'attributes.source.id');

                $payment = Payment::where('organization_id', $organization->id)
                    ->where(function ($query) use ($sessionId, $sourceSessionId) {
                        $query->where('paymongo_checkout_session_id', $sessionId);

                        if ($sourceSessionId) {
                            $query->orWhere('paymongo_checkout_session_id', $sourceSessionId);
                        }
                    })
                    ->first();
            }

            if ($payment) {
                $payment->update([
                    'paymongo_payment_id' => $paymentId,
                    'status' => Payment::STATUS_PAID,
                    'amount_cents' => data_get($attributes, 'amount', $payment->amount_cents),
                    'paid_at' => Carbon::now(),
                    'payload' => $payload,
                ]);
            } else {
                $payment = Payment::create([
                    'organization_id' => $organization->id,
                    'paymongo_payment_id' => $paymentId,
                    'amount_cents' => data_get($attributes, 'amount', $plan->price_cents),
                    'currency' => data_get($attributes, 'currency', $plan->currency),
                    'status' => Payment::STATUS_PAID,
                    'description' => 'Payment for '.$plan->name,
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
        });
    }

    public function cancelSubscription(Organization $organization): void
    {
        DB::transaction(function () use ($organization) {
            $subscription = $organization->subscription;

            if ($subscription) {
                $subscription->update([
                    'status' => Subscription::STATUS_CANCELED,
                    'canceled_at' => Carbon::now(),
                ]);
            }

            $organization->update([
                'subscription_status' => Organization::SUBSCRIPTION_CANCELED,
                'subscription_ends_at' => Carbon::now(),
            ]);
        });
    }

    public function startTrial(Organization $organization): void
    {
        $trialDays = (int) config('paymongo.trial_days', 14);
        $communityPlan = Plan::where('slug', 'community')->first();

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
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }

    protected function buildReturnUrl(string $type, Organization $organization): string
    {
        $base = $type === 'success'
            ? (string) config('paymongo.success_url')
            : (string) config('paymongo.cancel_url');

        return $base.'?organization='.$organization->slug;
    }
}