<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Hammer', 'Screwdriver', 'Wrench', 'Drill', 'Saw', 'Tape Measure', 'Pliers', 'Chisel', 'Level', 'Utility Knife',
                'Paint Brush', 'Sandpaper', 'Ladder', 'Wheelbarrow', 'Shovel', 'Rake', 'Hoe', 'Axe', 'Crowbar', 'Flashlight'
            ]),
            'description' => $this->faker->sentence(),
            'category_id' => \App\Models\Category::inRandomOrder()->first()?->id ?? \App\Models\Category::factory(),
            'supplier_id' => \App\Models\Supplier::inRandomOrder()->first()?->id ?? \App\Models\Supplier::factory(),
            'price' => $this->faker->randomFloat(2, 10, 500),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\Product $product) {
            \App\Models\Inventory::create([
                'product_id' => $product->id,
                'quantity' => $this->faker->numberBetween(50, 500),
            ]);
        });
    }
}
