<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Vacation Leave', 'Sick Leave', 'Emergency Leave']),
            'description' => fake()->sentence(),
            'max_days_per_year' => 15,
            'is_paid' => true,
            'is_active' => true,
        ];
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => false,
        ]);
    }
}
