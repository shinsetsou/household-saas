<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Plan;
use App\Services\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BillingSettingsController extends Controller
{
    public function __construct(protected BillingService $billing)
    {
    }

    public function show(Organization $organization): View
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('organizations.settings.billing', [
            'organization' => $organization,
            'plans' => $plans,
            'payments' => $organization->payments()->latest()->limit(10)->get(),
        ]);
    }

    public function cancel(Organization $organization): RedirectResponse
    {
        $this->billing->cancelSubscription($organization);

        AuditLog::record(
            $organization,
            'organization.billing.canceled',
            request()->user(),
            $organization,
            ['plan_id' => $organization->plan_id],
        );

        return redirect()
            ->route('organizations.settings.billing.show', $organization->slug)
            ->with('info', 'Your subscription has been cancelled.');
    }
}