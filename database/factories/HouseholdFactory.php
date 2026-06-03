<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class HouseholdFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'block' => fake()->unique()->numberBetween(1, 999),
            'lot' => fake()->unique()->numberBetween(1, 999),
        ];
    }
}
