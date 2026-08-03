<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Plan;
use App\Services\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(protected BillingService $billing) {}

    public function checkout(Request $request, Organization $organization, Plan $plan): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($organization), 403);

        if (! $plan->is_active) {
            return redirect()
                ->route('organizations.settings.billing.show', $organization->slug)
                ->with('error', 'That plan is not available.');
        }

        // Downgrade = switching to a plan that costs less than the current one.
        // Schedule the current subscription to end at period end (keeping access
        // until then) so the user doesn't double-pay for the same month, then
        // kick off the new checkout for the smaller plan.
        $currentPlan = $organization->plan;
        if ($currentPlan && $plan->price_cents < $currentPlan->price_cents) {
            $this->billing->cancelSubscription($organization);

            AuditLog::record(
                $organization,
                'organization.billing.downgrade_scheduled',
                $request->user(),
                $organization,
                [
                    'from_plan_id' => $currentPlan->id,
                    'to_plan_id' => $plan->id,
                ],
            );
        }

        try {
            $session = $this->billing->startCheckout($organization, $plan);
        } catch (\Throwable $e) {
            Log::error('PayMongo checkout failed', ['error' => $e->getMessage()]);

            return redirect()
                ->route('organizations.settings.billing.show', $organization->slug)
                ->with('error', 'Could not start checkout. Please try again.');
        }

        return redirect()->away($session['checkout_url']);
    }

    public function returnSuccess(Request $request): RedirectResponse
    {
        $slug = $request->query('organization');

        if (! $slug) {
            return redirect()->route('home');
        }

        // PayMongo always redirects the user back to the success URL once
        // they leave the hosted checkout — even if the payment hasn't actually
        // settled yet, or failed, or the webhook is delayed. Check the org's
        // real subscription status to decide which flash to show.
        $organization = Organization::where('slug', $slug)->first();
        $isActive = $organization
            && $organization->subscription_status === Organization::SUBSCRIPTION_ACTIVE;

        $billingRoute = 'organizations.settings.billing.show';

        if ($isActive) {
            return redirect()
                ->route($billingRoute, $slug)
                ->with('success', 'Payment received. Your plan is now active.');
        }

        return redirect()
            ->route($billingRoute, $slug)
            ->with('info', 'Thanks! We\'re processing your payment. Your plan will activate in a few seconds — refresh this page if it doesn\'t update automatically.');
    }

    public function returnCancel(Request $request): RedirectResponse
    {
        $slug = $request->query('organization');

        if (! $slug) {
            return redirect()->route('home');
        }

        return redirect()
            ->route('organizations.settings.billing.show', $slug)
            ->with('info', 'Checkout cancelled.');
    }

    public function webhook(Request $request): Response
    {
        $payload = $request->json()->all();
        $eventType = data_get($payload, 'data.attributes.type');

        if ($eventType === 'payment.paid') {
            $paymentPayload = data_get($payload, 'data.attributes.data', []);
            $this->billing->handlePaymentPaid($paymentPayload);
        }

        return response('OK', 200);
    }
}
