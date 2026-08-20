<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date' => fake()->unique()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'time_in' => '08:00:00',
            'time_out' => '17:00:00',
            'total_hours' => 8.00,
            'status' => 'present',
            'remarks' => null,
            'recorded_by' => null,
        ];
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_in' => '09:15:00',
            'status' => 'late',
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_in' => null,
            'time_out' => null,
            'total_hours' => null,
            'status' => 'absent',
        ]);
    }

    public function halfDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_out' => '12:00:00',
            'total_hours' => 4.00,
            'status' => 'half_day',
        ]);
    }
}
