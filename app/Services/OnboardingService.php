<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\BillingService;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    public function __construct(protected BillingService $billing)
    {
    }

    /**
     * Create a new organization for the given user, attach the user as its
     * first admin, write the creation to the audit log, and start the
     * 14-day Community-plan trial — all in one transaction.
     */
    public function createOrganizationForUser(User $user, array $data): Organization
    {
        return DB::transaction(function () use ($user, $data): Organization {
            $organization = Organization::create([
                'name' => $data['name'],
                'monthly_due_amount' => $data['monthly_due_amount'],
                'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            ]);

            $user->organizations()->attach($organization->id, ['role' => OrganizationRole::Admin->value]);

            AuditLog::record(
                $organization,
                'organization.created',
                $user,
                $organization,
                ['via' => 'onboarding'],
            );

            $this->billing->startTrial($organization);

            return $organization;
        });
    }
}