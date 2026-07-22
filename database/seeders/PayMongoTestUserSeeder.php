<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\BillingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PayMongoTestUserSeeder extends Seeder
{
    public function run(): void
    {
        app(BillingService::class)->ensurePlansExist();

        $community = Plan::where('slug', 'community')->firstOrFail();

        $user = User::updateOrCreate(
            ['email' => 'paymongo-test@example.com'],
            [
                'name' => 'PayMongo Test Admin',
                'password' => 'password',
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ],
        );

        $org = Organization::where('slug', 'paymongo-test-hoa')->first();

        if (! $org) {
            $org = Organization::create([
                'name' => 'PayMongo Test HOA',
                'slug' => 'paymongo-test-hoa',
                'monthly_due_amount' => 200,
                'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
                'plan_id' => $community->id,
                'trial_ends_at' => Carbon::now()->addDay(),
            ]);
        } else {
            $org->update([
                'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
                'plan_id' => $community->id,
                'trial_ends_at' => Carbon::now()->addDay(),
            ]);
        }

        $org->users()->syncWithoutDetaching([
            $user->id => ['role' => OrganizationRole::Admin->value],
        ]);

        Subscription::updateOrCreate(
            ['organization_id' => $org->id],
            [
                'plan_id' => $community->id,
                'status' => Subscription::STATUS_TRIALING,
                'current_period_start' => Carbon::now()->subDays(13),
                'current_period_end' => Carbon::now()->addDay(),
            ],
        );

        $this->command->info('');
        $this->command->info('PayMongo test user seeded:');
        $this->command->info('  Email:    paymongo-test@example.com');
        $this->command->info('  Password: password');
        $this->command->info('  Org slug: paymongo-test-hoa');
        $this->command->info('  Trial:    ends in 1 day (T-1 warning state)');
        $this->command->info('');
        $this->command->info('Log in and visit:');
        $this->command->info('  /paymongo-test-hoa/settings/billing');
        $this->command->info('Click "Switch to Barangay" to hit the PayMongo test checkout.');
    }
}
