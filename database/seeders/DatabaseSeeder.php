<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlans();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    protected function seedPlans(): void
    {
        $plans = [
            ['slug' => 'community', 'name' => 'Community', 'description' => 'For small HOAs getting started.', 'price_cents' => 19900, 'max_households' => 50, 'csv_import' => true, 'api_access' => false, 'sort_order' => 1],
            ['slug' => 'barangay', 'name' => 'Barangay', 'description' => 'CSV import, photo verification, and full reports.', 'price_cents' => 49900, 'max_households' => 250, 'csv_import' => true, 'api_access' => false, 'sort_order' => 2],
            ['slug' => 'city', 'name' => 'City', 'description' => 'Unlimited households and API access for multi-HOA portfolios.', 'price_cents' => 99900, 'max_households' => null, 'csv_import' => true, 'api_access' => true, 'sort_order' => 3],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}