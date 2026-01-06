<?php

namespace Database\Factories;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $priorities = ['low', 'normal', 'high', 'urgent'];
        $statuses = ['pending', 'approved', 'rejected'];

        return [
            'request_number' => MaintenanceRequest::generateRequestNumber(),
            'vehicle_id' => Vehicle::factory(),
            'maintenance_type_id' => MaintenanceType::factory(),
            'requested_by' => User::factory(),
            'priority' => fake()->randomElement($priorities),
            'status' => fake()->randomElement($statuses),
            'current_mileage' => fake()->numberBetween(10000, 100000),
            'description' => fake()->paragraph(),
            'estimated_cost' => fake()->randomFloat(2, 1000, 20000),
            'preferred_date' => fake()->dateTimeBetween('now', '+2 weeks'),
        ];
    }

    /**
     * State for pending requests.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * State for approved requests.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }
}
