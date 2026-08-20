<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+30 days');
        $endDate = (clone $startDate)->modify('+2 days');

        return [
            // Not LeaveRequest::generateRequestNumber() — that looks up the
            // latest row in the DB, which races when a factory batch-creates
            // several requests at once (all built before any is persisted).
            'request_number' => 'LR-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_days' => 3,
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'approved_by' => null,
            'rejection_reason' => null,
            'approved_at' => null,
            'rejected_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => fake()->sentence(),
            'rejected_at' => now(),
        ]);
    }
}
