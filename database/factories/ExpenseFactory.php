<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition()
    {
        return [
            'organization_id' => Organization::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'description' => $this->faker->sentence,
            'status' => Expense::STATUS_PENDING,
            'notes' => null,
            'created_at' => $this->faker->dateTimeThisYear,
            'updated_at' => $this->faker->dateTimeThisYear,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => Expense::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => Expense::STATUS_CANCELLED,
        ]);
    }
}
