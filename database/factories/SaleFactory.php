<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 500, 10000);

        return [
            'customer_id' => Customer::factory(),
            'date' => fake()->dateTimeBetween('-30 days'),
            'total' => $total,
            'payment_status' => fake()->randomElement(['unpaid', 'partial', 'paid']),
            'amount_paid' => fake()->randomFloat(2, 0, $total),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+60 days'),
            'paid_date' => null,
        ];
    }

    /**
     * State for paid sales.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'amount_paid' => $attributes['total'],
            'paid_date' => now(),
        ]);
    }
}
