<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuotationItem>
 */
class QuotationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 20);
        $unitPrice = fake()->randomFloat(2, 10, 500);

        return [
            'quotation_id' => Quotation::factory(),
            'product_id' => Product::factory(),
            'product_description' => null,
            'is_manual' => false,
            'unit' => 'piece',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => 0,
            'discount_is_flat' => false,
            'price' => $quantity * $unitPrice,
        ];
    }
}
