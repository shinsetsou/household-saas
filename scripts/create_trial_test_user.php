<?php

use App\Models\User;
use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

DB::transaction(function () {
    $existing = User::where('email', 'trial-test@household.test')->first();
    if ($existing) {
        $existing->delete();
        echo "deleted existing user" . PHP_EOL;
    }

    $user = User::create([
        'name' => 'Trial Test',
        'email' => 'trial-test@household.test',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
    echo "user_id=" . $user->id . PHP_EOL;

    $existingOrg = Organization::where('slug', 'trial-test-org')->first();
    if ($existingOrg) {
        $existingOrg->delete();
        echo "deleted existing org" . PHP_EOL;
    }

    $community = Plan::where('slug', 'community')->first();
    if (! $community) {
        echo "NO COMMUNITY PLAN - run db:seed" . PHP_EOL;
        return;
    }

    $org = Organization::create([
        'name' => 'Trial Test Org',
        'slug' => 'trial-test-org',
        'plan_id' => $community->id,
        'subscription_status' => 'trialing',
        'trial_ends_at' => now()->subDays(2),
        'monthly_due_amount' => 50,
    ]);
    echo "org_id=" . $org->id . " trial_ends_at=" . $org->trial_ends_at . " status=" . $org->subscription_status . PHP_EOL;

    $org->users()->attach($user->id, ['role' => 'admin']);

    if (Schema::hasColumn('organizations', 'trial_warning_sent_at')) {
        DB::table('organizations')->where('id', $org->id)->update([
            'trial_warning_sent_at' => json_encode(['T-7' => now()->subDays(9)->toIso8601String()]),
        ]);
        echo "trial_warning_sent_at reset so T-7/T-1 warnings can re-fire" . PHP_EOL;
    } else {
        echo "no trial_warning_sent_at column - warnings may be suppressed" . PHP_EOL;
    }
});

echo PHP_EOL . "DONE" . PHP_EOL;
echo "login:  trial-test@household.test / password" . PHP_EOL;
echo "url:    /trial-test-org" . PHP_EOL;
echo "expire: php artisan trials:expire" . PHP_EOL;
