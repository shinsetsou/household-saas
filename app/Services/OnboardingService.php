<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    public function createOrganizationForUser(User $user, array $data): Organization
    {
        return DB::transaction(function () use ($user, $data): Organization {
            $organization = Organization::create([
                'name' => $data['name'],
                'monthly_due_amount' => $data['monthly_due_amount'],
            ]);

            $user->organizations()->attach($organization->id, ['role' => 'admin']);

            AuditLog::record(
                $organization,
                'organization.created',
                $user,
                $organization,
                ['via' => 'onboarding'],
            );

            return $organization;
        });
    }
}
