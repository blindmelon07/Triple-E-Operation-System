<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeCompensation>
 */
class EmployeeCompensationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'daily_rate' => fake()->randomFloat(2, 400, 1000),
            'pay_period' => 'semi_monthly',
            'days_off' => ['sunday'],
            'sss_enabled' => true,
            'philhealth_enabled' => true,
            'pagibig_enabled' => true,
            'overtime_rate_multiplier' => 1.25,
            'late_deduction_type' => 'per_minute',
            'late_deduction_amount' => 0,
            'allowance' => 0,
            'allowance_description' => null,
        ];
    }
}
