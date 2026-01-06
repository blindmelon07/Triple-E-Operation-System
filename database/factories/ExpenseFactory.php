<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentMethods = ['cash', 'bank_transfer', 'check', 'credit_card', 'gcash', 'maya'];
        $statuses = ['pending', 'approved', 'rejected'];

        return [
            'expense_category_id' => ExpenseCategory::factory(),
            'user_id' => User::factory(),
            'reference_number' => Expense::generateReferenceNumber(),
            'expense_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'payment_method' => fake()->randomElement($paymentMethods),
            'payee' => fake()->company(),
            'description' => fake()->sentence(),
            'receipt_path' => null,
            'status' => fake()->randomElement($statuses),
        ];
    }

    /**
     * State for approved expenses.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * State for pending expenses.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
