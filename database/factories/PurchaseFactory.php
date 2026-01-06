<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 1000, 50000);

        return [
            'supplier_id' => Supplier::factory(),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'total' => $total,
            'payment_status' => fake()->randomElement(['unpaid', 'partial', 'paid']),
            'amount_paid' => fake()->randomFloat(2, 0, $total),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+60 days'),
            'paid_date' => null,
        ];
    }

    /**
     * State for paid purchases.
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
