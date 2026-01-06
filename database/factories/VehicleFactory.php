<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $makes = ['Toyota', 'Honda', 'Mitsubishi', 'Nissan', 'Ford', 'Isuzu', 'Hyundai', 'Suzuki', 'Kia'];
        $models = [
            'Toyota' => ['Vios', 'Innova', 'Fortuner', 'Hilux', 'Hiace'],
            'Honda' => ['City', 'Civic', 'CR-V', 'BR-V', 'HR-V'],
            'Mitsubishi' => ['Mirage', 'Xpander', 'Montero Sport', 'L300', 'Strada'],
            'Nissan' => ['Almera', 'Navara', 'Terra', 'Urvan'],
            'Ford' => ['EcoSport', 'Ranger', 'Everest', 'Territory'],
            'Isuzu' => ['D-Max', 'mu-X', 'Traviz'],
            'Hyundai' => ['Accent', 'Tucson', 'Santa Fe', 'Starex'],
            'Suzuki' => ['Swift', 'Ertiga', 'Dzire', 'Carry'],
            'Kia' => ['Picanto', 'Soluto', 'Seltos', 'Carnival'],
        ];
        $colors = ['White', 'Black', 'Silver', 'Gray', 'Red', 'Blue', 'Brown'];

        $make = fake()->randomElement($makes);

        return [
            'plate_number' => strtoupper(fake()->randomLetter().fake()->randomLetter().fake()->randomLetter().' '.fake()->numberBetween(100, 9999)),
            'make' => $make,
            'model' => fake()->randomElement($models[$make]),
            'year' => fake()->numberBetween(2015, 2025),
            'color' => fake()->randomElement($colors),
            'vin' => strtoupper(fake()->bothify('??#??##?#?#######')),
            'engine_number' => strtoupper(fake()->bothify('???###????')),
            'fuel_type' => fake()->randomElement(['gasoline', 'diesel']),
            'transmission' => fake()->randomElement(['automatic', 'manual']),
            'current_mileage' => fake()->numberBetween(5000, 150000),
            'acquisition_date' => fake()->dateTimeBetween('-5 years', '-6 months'),
            'acquisition_cost' => fake()->randomFloat(2, 500000, 3000000),
            'status' => fake()->randomElement(['active', 'active', 'active', 'maintenance', 'inactive']),
            'assigned_driver' => fake()->optional()->name(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * State for active vehicles.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * State for vehicles in maintenance.
     */
    public function inMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'maintenance',
        ]);
    }
}
