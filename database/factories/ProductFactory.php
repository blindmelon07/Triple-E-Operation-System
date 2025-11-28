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
            'category_id' => \App\Models\Category::factory(),
            'supplier_id' => \App\Models\Supplier::factory(),
            'price' => $this->faker->randomFloat(2, 10, 500),
        ];
    }
}
