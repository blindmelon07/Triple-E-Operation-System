<?php

namespace Database\Factories;

use App\Models\FuelLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FuelLog>
 */
class FuelLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $liters = fake()->randomFloat(2, 10, 60);
        $pricePerLiter = fake()->randomFloat(2, 55, 75);

        return [
            'vehicle_id' => Vehicle::factory(),
            'user_id' => User::factory(),
            'reference_number' => FuelLog::generateReferenceNumber(),
            'fuel_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'odometer_reading' => fake()->numberBetween(5000, 100000),
            'liters' => $liters,
            'price_per_liter' => $pricePerLiter,
            'cost' => round($liters * $pricePerLiter, 2),
            'fuel_station' => fake()->company().' Gas Station',
            'notes' => fake()->optional()->sentence(),
            'receipt_path' => null,
        ];
    }
}
