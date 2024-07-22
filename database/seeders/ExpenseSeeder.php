<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\Organization;

class ExpenseSeeder extends Seeder
{
    public function run()
    {
        $organization = Organization::first();

        if ($organization) {
            Expense::factory()->count(50)->create([
                'organization_id' => $organization->id,
            ]);
        }
    }
}
