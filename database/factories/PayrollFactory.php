<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payroll>
 */
class PayrollFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Not Payroll::generatePayrollNumber() — that looks up the
            // latest row in the DB, which races when a factory batch-creates
            // several payrolls at once (all built before any is persisted).
            'payroll_number' => 'PAY-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'pay_period_start' => now()->startOfMonth()->format('Y-m-d'),
            'pay_period_end' => now()->startOfMonth()->addDays(14)->format('Y-m-d'),
            'pay_period_type' => 'semi_monthly',
            'status' => 'draft',
            'generated_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
            'paid_at' => null,
            'total_gross' => 0,
            'total_deductions' => 0,
            'total_net' => 0,
            'notes' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'approved_at' => now(),
            'paid_at' => now(),
        ]);
    }
}
