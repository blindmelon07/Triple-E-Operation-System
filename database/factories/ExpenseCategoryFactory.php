<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Utilities' => 'Electricity, water, internet, and phone bills',
            'Office Supplies' => 'Paper, pens, staplers, and other office materials',
            'Rent' => 'Monthly rent and lease payments',
            'Salaries' => 'Employee wages and payroll',
            'Transportation' => 'Fuel, vehicle maintenance, and travel expenses',
            'Marketing' => 'Advertising, promotions, and marketing materials',
            'Maintenance' => 'Equipment and building maintenance costs',
            'Insurance' => 'Business insurance premiums',
            'Professional Services' => 'Legal, accounting, and consulting fees',
            'Miscellaneous' => 'Other uncategorized expenses',
        ];

        $name = fake()->unique()->randomElement(array_keys($categories));

        return [
            'name' => $name,
            'description' => $categories[$name],
            'is_active' => fake()->boolean(90),
        ];
    }
}
