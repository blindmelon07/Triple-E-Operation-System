<?php

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Sale;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Delivery CRUD Operations', function () {
    it('can create a delivery', function () {
        actingAs($this->user);

        $sale = Sale::factory()->create();
        $driver = Driver::factory()->create();

        $deliveryData = [
            'sale_id' => $sale->id,
            'driver_id' => $driver->id,
            'status' => DeliveryStatus::Pending,
            'delivery_address' => '123 Test Street, Test City',
            'notes' => 'Handle with care',
        ];

        $delivery = Delivery::create($deliveryData);

        expect($delivery)->toBeInstanceOf(Delivery::class)
            ->and($delivery->status)->toBe(DeliveryStatus::Pending)
            ->and($delivery->delivery_address)->toBe('123 Test Street, Test City')
            ->and($delivery->notes)->toBe('Handle with care');

        $this->assertDatabaseHas('deliveries', [
            'sale_id' => $sale->id,
            'driver_id' => $driver->id,
        ]);
    });

    it('can read a delivery', function () {
        actingAs($this->user);

        $delivery = Delivery::factory()->create();

        $foundDelivery = Delivery::find($delivery->id);

        expect($foundDelivery)->not->toBeNull()
            ->and($foundDelivery->id)->toBe($delivery->id);
    });

    it('can read all deliveries', function () {
        actingAs($this->user);

        Delivery::factory()->count(5)->create();

        $deliveries = Delivery::all();

        expect($deliveries)->toHaveCount(5);
    });

    it('can update a delivery', function () {
        actingAs($this->user);

        $delivery = Delivery::factory()->create([
            'status' => DeliveryStatus::Pending,
        ]);

        $delivery->update([
            'status' => DeliveryStatus::Assigned,
            'assigned_at' => now(),
        ]);

        $fresh = $delivery->fresh();
        expect($fresh->status)->toBe(DeliveryStatus::Assigned)
            ->and($fresh->assigned_at)->not->toBeNull();
    });

    it('can delete a delivery', function () {
        actingAs($this->user);

        $delivery = Delivery::factory()->create();
        $deliveryId = $delivery->id;

        $delivery->delete();

        $this->assertDatabaseMissing('deliveries', ['id' => $deliveryId]);
    });

    it('belongs to a sale', function () {
        actingAs($this->user);

        $sale = Sale::factory()->create();
        $delivery = Delivery::factory()->create(['sale_id' => $sale->id]);

        expect($delivery->sale)->toBeInstanceOf(Sale::class)
            ->and($delivery->sale->id)->toBe($sale->id);
    });

    it('belongs to a driver', function () {
        actingAs($this->user);

        $driver = Driver::factory()->create(['name' => 'Test Driver']);
        $delivery = Delivery::factory()->create(['driver_id' => $driver->id]);

        expect($delivery->driver)->toBeInstanceOf(Driver::class)
            ->and($delivery->driver->name)->toBe('Test Driver');
    });

    it('casts status to DeliveryStatus enum', function () {
        actingAs($this->user);

        $delivery = Delivery::factory()->create(['status' => DeliveryStatus::InTransit]);

        expect($delivery->status)->toBeInstanceOf(DeliveryStatus::class)
            ->and($delivery->status)->toBe(DeliveryStatus::InTransit);
    });

    it('can filter by status', function () {
        actingAs($this->user);

        Delivery::factory()->count(2)->create(['status' => DeliveryStatus::Pending]);
        Delivery::factory()->count(3)->create(['status' => DeliveryStatus::Delivered]);
        Delivery::factory()->count(1)->create(['status' => DeliveryStatus::Failed]);

        $pending = Delivery::where('status', DeliveryStatus::Pending)->get();
        $delivered = Delivery::where('status', DeliveryStatus::Delivered)->get();

        expect($pending)->toHaveCount(2)
            ->and($delivered)->toHaveCount(3);
    });

    it('can progress through delivery statuses', function () {
        actingAs($this->user);

        $delivery = Delivery::factory()->create([
            'status' => DeliveryStatus::Pending,
        ]);

        // Assign driver
        $delivery->update([
            'status' => DeliveryStatus::Assigned,
            'assigned_at' => now(),
        ]);
        expect($delivery->fresh()->status)->toBe(DeliveryStatus::Assigned);

        // Pick up
        $delivery->update([
            'status' => DeliveryStatus::PickedUp,
            'picked_up_at' => now(),
        ]);
        expect($delivery->fresh()->status)->toBe(DeliveryStatus::PickedUp);

        // In transit
        $delivery->update([
            'status' => DeliveryStatus::InTransit,
        ]);
        expect($delivery->fresh()->status)->toBe(DeliveryStatus::InTransit);

        // Delivered
        $delivery->update([
            'status' => DeliveryStatus::Delivered,
            'delivered_at' => now(),
        ]);
        expect($delivery->fresh()->status)->toBe(DeliveryStatus::Delivered);
    });

    it('can record customer feedback and rating', function () {
        actingAs($this->user);

        $delivery = Delivery::factory()->create([
            'status' => DeliveryStatus::Delivered,
            'delivered_at' => now(),
        ]);

        $delivery->update([
            'rating' => 5,
            'customer_feedback' => 'Excellent service!',
        ]);

        $fresh = $delivery->fresh();
        expect($fresh->rating)->toBe(5)
            ->and($fresh->customer_feedback)->toBe('Excellent service!');
    });
});
