<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayrollItem>
 */
class PayrollItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dailyRate = fake()->randomFloat(2, 400, 1000);
        $daysWorked = fake()->randomFloat(2, 10, 13);
        $grossPay = round($dailyRate * $daysWorked, 2);

        return [
            'payroll_id' => Payroll::factory(),
            'employee_id' => Employee::factory(),
            'daily_rate' => $dailyRate,
            'days_worked' => $daysWorked,
            'days_absent' => 0,
            'overtime_hours' => 0,
            'overtime_pay' => 0,
            'bonus' => 0,
            'bonus_description' => null,
            'allowance' => 0,
            'gross_pay' => $grossPay,
            'late_count' => 0,
            'late_minutes' => 0,
            'late_deduction' => 0,
            'sss_deduction' => 0,
            'philhealth_deduction' => 0,
            'pagibig_deduction' => 0,
            'other_deduction' => 0,
            'other_deduction_description' => null,
            'total_deductions' => 0,
            'net_pay' => $grossPay,
        ];
    }
}
