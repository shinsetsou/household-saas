<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Organization;
use Illuminate\Database\Seeder;

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
