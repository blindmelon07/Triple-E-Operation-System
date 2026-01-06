<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceType>
 */
class MaintenanceTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            ['name' => 'Oil Change', 'interval_km' => 5000, 'interval_months' => 3],
            ['name' => 'Tire Rotation', 'interval_km' => 10000, 'interval_months' => 6],
            ['name' => 'Brake Inspection', 'interval_km' => 20000, 'interval_months' => 12],
            ['name' => 'Air Filter Replacement', 'interval_km' => 15000, 'interval_months' => 12],
            ['name' => 'Battery Check', 'interval_km' => null, 'interval_months' => 6],
            ['name' => 'Transmission Service', 'interval_km' => 50000, 'interval_months' => 24],
            ['name' => 'Coolant Flush', 'interval_km' => 40000, 'interval_months' => 24],
            ['name' => 'Spark Plug Replacement', 'interval_km' => 40000, 'interval_months' => null],
        ];

        $type = fake()->unique()->randomElement($types);

        return [
            'name' => $type['name'],
            'description' => fake()->sentence(),
            'recommended_interval_km' => $type['interval_km'],
            'recommended_interval_months' => $type['interval_months'],
            'is_active' => true,
        ];
    }
}
