<?php

namespace Database\Seeders;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Sale;
use Illuminate\Database\Seeder;

class DriverDeliverySeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 drivers
        $drivers = Driver::factory(10)->create();

        // Get existing sales or create some
        $sales = Sale::all();
        if ($sales->isEmpty()) {
            $this->command->info('No sales found. Please run sale seeders first.');

            return;
        }

        // Create deliveries for existing sales
        foreach ($sales as $sale) {
            $driver = $drivers->random();
            $status = fake()->randomElement(DeliveryStatus::cases());

            $assignedAt = fake()->dateTimeBetween('-30 days', 'now');
            $pickedUpAt = null;
            $deliveredAt = null;
            $rating = null;

            if (in_array($status, [DeliveryStatus::PickedUp, DeliveryStatus::InTransit, DeliveryStatus::Delivered, DeliveryStatus::Returned])) {
                $pickedUpAt = fake()->dateTimeBetween($assignedAt, 'now');
            }

            if ($status === DeliveryStatus::Delivered) {
                $deliveredAt = fake()->dateTimeBetween($pickedUpAt ?? $assignedAt, 'now');
                $rating = fake()->numberBetween(1, 5);
            }

            Delivery::create([
                'sale_id' => $sale->id,
                'driver_id' => $driver->id,
                'status' => $status,
                'assigned_at' => $status !== DeliveryStatus::Pending ? $assignedAt : null,
                'picked_up_at' => $pickedUpAt,
                'delivered_at' => $deliveredAt,
                'delivery_address' => fake()->address(),
                'notes' => fake()->optional()->sentence(),
                'rating' => $rating,
                'customer_feedback' => $rating ? fake()->optional()->sentence() : null,
                'distance_km' => fake()->randomFloat(2, 1, 50),
            ]);
        }

        $this->command->info('Created '.$drivers->count().' drivers and '.$sales->count().' deliveries.');
    }
}
