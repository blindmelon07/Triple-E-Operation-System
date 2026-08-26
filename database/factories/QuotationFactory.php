<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quotation>
 */
class QuotationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'date' => fake()->dateTimeBetween('-30 days'),
            'valid_until' => fake()->dateTimeBetween('now', '+60 days'),
            'total' => fake()->randomFloat(2, 500, 10000),
            'down_payment' => 0,
            'notes' => fake()->optional()->sentence(),
            'status' => 'pending',
        ];
    }

    /**
     * State for an approved quotation.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }
}
