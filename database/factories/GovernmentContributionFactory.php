<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GovernmentContribution>
 */
class GovernmentContributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'sss',
            'salary_from' => 0,
            'salary_to' => 999999,
            'employee_share' => 100,
            'employer_share' => 200,
            'is_active' => true,
        ];
    }

    public function sss(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'sss']);
    }

    public function philhealth(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'philhealth']);
    }

    public function pagibig(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'pagibig']);
    }
}
