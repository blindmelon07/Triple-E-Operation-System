<?php

namespace Database\Factories;

use App\Models\MaintenanceRecord;
use App\Models\MaintenanceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceRecord>
 */
class MaintenanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $partsCost = fake()->randomFloat(2, 500, 10000);
        $laborCost = fake()->randomFloat(2, 300, 5000);
        $maintenanceDate = fake()->dateTimeBetween('-1 year', 'now');
        $mileage = fake()->numberBetween(5000, 100000);

        return [
            'vehicle_id' => Vehicle::factory(),
            'maintenance_type_id' => MaintenanceType::factory(),
            'user_id' => User::factory(),
            'reference_number' => MaintenanceRecord::generateReferenceNumber(),
            'maintenance_date' => $maintenanceDate,
            'mileage_at_service' => $mileage,
            'cost' => $partsCost + $laborCost,
            'parts_cost' => $partsCost,
            'labor_cost' => $laborCost,
            'service_provider' => fake()->company().' Auto Service',
            'description' => fake()->sentence(),
            'parts_replaced' => fake()->optional()->sentence(),
            'next_service_date' => fake()->optional()->dateTimeBetween('now', '+6 months'),
            'next_service_mileage' => $mileage + fake()->randomElement([5000, 10000, 15000]),
            'status' => fake()->randomElement(['completed', 'completed', 'completed', 'scheduled', 'in_progress']),
            'invoice_path' => null,
        ];
    }

    /**
     * State for completed maintenance.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * State for scheduled maintenance.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'maintenance_date' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }
}
