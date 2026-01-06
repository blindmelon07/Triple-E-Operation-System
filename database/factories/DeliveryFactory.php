<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Delivery>
 */
class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    public function definition(): array
    {
        $status = fake()->randomElement(DeliveryStatus::cases());
        $assignedAt = fake()->dateTimeBetween('-30 days', 'now');
        $deliveredAt = null;
        $pickedUpAt = null;

        if (in_array($status, [DeliveryStatus::PickedUp, DeliveryStatus::InTransit, DeliveryStatus::Delivered, DeliveryStatus::Returned])) {
            $pickedUpAt = fake()->dateTimeBetween($assignedAt, 'now');
        }

        if ($status === DeliveryStatus::Delivered) {
            $deliveredAt = fake()->dateTimeBetween($pickedUpAt ?? $assignedAt, 'now');
        }

        return [
            'sale_id' => Sale::factory(),
            'driver_id' => Driver::factory(),
            'status' => $status,
            'assigned_at' => $status !== DeliveryStatus::Pending ? $assignedAt : null,
            'picked_up_at' => $pickedUpAt,
            'delivered_at' => $deliveredAt,
            'delivery_address' => fake()->address(),
            'notes' => fake()->optional()->sentence(),
            'rating' => $status === DeliveryStatus::Delivered ? fake()->optional(0.7)->numberBetween(1, 5) : null,
            'customer_feedback' => $status === DeliveryStatus::Delivered ? fake()->optional(0.3)->sentence() : null,
            'distance_km' => fake()->randomFloat(2, 1, 50),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::Pending,
            'assigned_at' => null,
            'picked_up_at' => null,
            'delivered_at' => null,
            'rating' => null,
        ]);
    }

    public function delivered(): static
    {
        $assignedAt = fake()->dateTimeBetween('-30 days', '-1 day');
        $pickedUpAt = fake()->dateTimeBetween($assignedAt, '-1 hour');
        $deliveredAt = fake()->dateTimeBetween($pickedUpAt, 'now');

        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::Delivered,
            'assigned_at' => $assignedAt,
            'picked_up_at' => $pickedUpAt,
            'delivered_at' => $deliveredAt,
            'rating' => fake()->numberBetween(3, 5),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::Failed,
            'delivered_at' => null,
            'rating' => null,
        ]);
    }
}
