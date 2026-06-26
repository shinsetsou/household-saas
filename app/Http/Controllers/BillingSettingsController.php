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

        $endsAt = $organization->subscription?->canceled_at;

        AuditLog::record(
            $organization,
            'organization.billing.cancel_scheduled',
            request()->user(),
            $organization,
            ['plan_id' => $organization->plan_id, 'ends_at' => $endsAt?->toIso8601String()],
        );

        $message = $endsAt
            ? "Your subscription will end on {$endsAt->format('M j, Y')}. You can resume anytime before then."
            : 'Your subscription has been cancelled.';

        return redirect()
            ->route('organizations.settings.billing.show', $organization->slug)
            ->with('info', $message);
    }

    public function resume(Organization $organization): RedirectResponse
    {
        $this->billing->resumeSubscription($organization);

        AuditLog::record(
            $organization,
            'organization.billing.resumed',
            request()->user(),
            $organization,
            ['plan_id' => $organization->plan_id],
        );

        return redirect()
            ->route('organizations.settings.billing.show', $organization->slug)
            ->with('success', 'Welcome back! Your subscription is active again.');
    }
}