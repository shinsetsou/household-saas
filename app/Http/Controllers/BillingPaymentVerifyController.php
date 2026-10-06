<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifyPaymentRequest;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingPaymentVerifyController extends Controller
{
    public function __construct(protected BillingService $billing) {}

    public function __invoke(VerifyPaymentRequest $request, Organization $organization): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminOf($organization)) {
            abort(403);
        }

        $validated = $request->validated();

        if ($validated['org_name'] !== $organization->name) {
            return redirect()
                ->route('organizations.settings.billing.show', $organization->slug)
                ->with('error', 'Organization name does not match our records.');
        }

        $planSlug = $validated['plan_slug'] ?? null;
        $reference = $validated['reference'];

        try {
            $paymongo = app('paymongo_client');
            $paymentResource = $paymongo->retrievePayment($reference);
        } catch (\Throwable $e) {
            Log::warning('PayMongo reference verification lookup failed', [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('organizations.settings.billing.show', $organization->slug)
                ->with('error', 'We could not find a PayMongo payment for that reference. Please check your receipt or email and try again.');
        }

        $attributes = data_get($paymentResource, 'attributes', []);
        $paymentId = data_get($paymentResource, 'id');
        $status = data_get($attributes, 'status');
        $livemode = data_get($attributes, 'livemode');
        $amount = (int) data_get($attributes, 'amount', 0);
        $currency = data_get($attributes, 'currency');

        if ($status !== 'paid' || $livemode !== true || $amount <= 0 || $currency !== 'PHP') {
            return redirect()
                ->route('organizations.settings.billing.show', $organization->slug)
                ->with('error', 'That PayMongo payment is not confirmed as paid yet, is in test mode, or is in a different currency. Please wait a moment if you just paid, or contact support.');
        }

        // Idempotency / duplicate org check.
        $existingPayment = Payment::where('paymongo_payment_id', $paymentId)->first();

        if ($existingPayment) {
            if ($existingPayment->organization_id === $organization->id) {
                return redirect()
                    ->route('organizations.settings.billing.show', $organization->slug)
                    ->with('success', 'That payment is already verified for '.$organization->name.'. Your plan is active.');
            }

            return redirect()
                ->route('organizations.settings.billing.show', $organization->slug)
                ->with('error', 'That PayMongo payment has already been used by another organization. If this is a mistake, contact support.');
        }

        $plan = $this->resolvePlan($attributes, $amount, $planSlug);

        if (! $plan) {
            return redirect()
                ->route('organizations.settings.billing.show', $organization->slug)
                ->withInput()
                ->with('error', 'We could not automatically determine which plan this payment was for. Please select a plan and try again.');
        }

        if ($amount !== $plan->price_cents) {
            return redirect()
                ->route('organizations.settings.billing.show', $organization->slug)
                ->withInput()
                ->with('error', 'The payment amount does not match the selected plan. Expected '.($plan->price_cents / 100).' PHP but received '.($amount / 100).' PHP.');
        }

        try {
            DB::transaction(function () use ($organization, $plan, $paymentResource, $paymentId, $attributes, $user, $amount, $currency) {
                // Idempotency: firstOrCreate by paymongo_payment_id. Another
                // row may have been created concurrently by the webhook path.
                $payment = Payment::firstOrCreate(
                    ['paymongo_payment_id' => $paymentId],
                    [
                        'organization_id' => $organization->id,
                        'plan_id' => $plan->id,
                        'amount_cents' => $amount,
                        'currency' => $currency,
                        'status' => Payment::STATUS_PAID,
                        'description' => 'Payment for '.$plan->name,
                        'paid_at' => now(),
                        'payload' => $paymentResource,
                        'verified_by_user_id' => $user->id,
                    ]
                );

                $this->billing->activateForOrganization(
                    $organization,
                    $plan,
                    $paymentResource,
                    $paymentId,
                    $attributes,
                    $payment,
                );
            });
        } catch (\Throwable $e) {
            Log::error('PayMongo reference verification activation failed', [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'reference' => $reference,
                'paymongo_payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('organizations.settings.billing.show', $organization->slug)
                ->with('error', 'We confirmed the payment with PayMongo, but could not activate your plan. Please contact support.');
        }

        return redirect()
            ->route('organizations.settings.billing.show', $organization->slug)
            ->with('success', 'Payment verified! Your '.$plan->name.' plan is now active.');
    }

    protected function resolvePlan(array $attributes, int $amount, ?string $planSlug): ?Plan
    {
        if ($planSlug) {
            return Plan::where('slug', $planSlug)->where('is_active', true)->first();
        }

        // 1) Direct amount match against configured plan amounts (centavos).
        $planAmounts = config('services.paymongo.plan_amounts', []);

        foreach ($planAmounts as $slug => $cents) {
            if ((int) $cents === $amount) {
                $plan = Plan::where('slug', $slug)->where('is_active', true)->first();

                if ($plan) {
                    return $plan;
                }
            }
        }

        // 2) Best-effort text match from description / statement descriptor.
        $haystack = strtolower(
            data_get($attributes, 'description', '').' '.
            data_get($attributes, 'statement_descriptor', '').' '.
            data_get($attributes, 'external_reference_number', '')
        );

        $tiers = ['city' => 'city', 'barangay' => 'barangay', 'community' => 'community'];

        foreach ($tiers as $needle => $slug) {
            if (str_contains($haystack, $needle)) {
                $plan = Plan::where('slug', $slug)->where('is_active', true)->first();

                if ($plan) {
                    return $plan;
                }
            }
        }

        // 3) Fall back to current org plan if its price exactly matches.
        // Do this only as a last resort to avoid surprise plan changes.
        $organization = request()->route('organization');

        if ($organization instanceof Organization && $organization->plan_id) {
            $currentPlan = Plan::where('id', $organization->plan_id)
                ->where('is_active', true)
                ->first();

            if ($currentPlan && $currentPlan->price_cents === $amount) {
                return $currentPlan;
            }
        }

        return null;
    }
}
