<?php

namespace Database\Factories;

use App\Models\CashAdvance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CashAdvance>
 */
class CashAdvanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomElement([1000, 2000, 3000, 5000]);

        return [
            'employee_id' => Employee::factory(),
            'user_id' => null,
            'reference_number' => CashAdvance::generateReferenceNumber(),
            'date_granted' => fake()->dateTimeBetween('-3 months', 'now'),
            'amount' => $amount,
            'deduction_per_payroll' => $amount / 4,
            'purpose' => fake()->optional()->sentence(3),
            'notes' => null,
        ];
    }
}
