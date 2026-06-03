<?php

namespace Database\Factories;

use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContributionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'amount' => 200,
            'photo' => null,
            'status' => 'paid',
            'reference_number' => (string) Str::uuid(),
            'verification_status' => 'pending',
            'rejection_reason' => null,
            'verified_by' => null,
            'verified_at' => null,
        ];
    }
}
