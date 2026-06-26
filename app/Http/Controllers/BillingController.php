<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Plan;
use App\Services\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(protected BillingService $billing)
    {
    }

    public function checkout(Request $request, Organization $organization, Plan $plan): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($organization), 403);

        if (! $plan->is_active) {
            return redirect()
                ->route('organizations.settings.billing.show', $organization->slug)
                ->with('error', 'That plan is not available.');
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

        return redirect()
            ->route('organizations.settings.billing.show', $slug)
            ->with('success', 'Payment received. Your plan is now active.');
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

    public function webhook(Request $request): \Illuminate\Http\Response
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