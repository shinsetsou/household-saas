<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\BillingService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Single source of truth: BillingService owns the plan catalog.
        app(BillingService::class)->seedDefaultPlans();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}