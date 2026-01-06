<?php

use App\Models\Driver;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Driver CRUD Operations', function () {
    it('can create a driver', function () {
        actingAs($this->user);

        $driverData = [
            'name' => 'Juan Dela Cruz',
            'phone' => '09171234567',
            'license_number' => 'N01-12-123456',
            'vehicle_type' => 'Motorcycle',
            'vehicle_plate' => 'ABC 123',
            'is_active' => true,
        ];

        $driver = Driver::create($driverData);

        expect($driver)->toBeInstanceOf(Driver::class)
            ->and($driver->name)->toBe('Juan Dela Cruz')
            ->and($driver->phone)->toBe('09171234567')
            ->and($driver->license_number)->toBe('N01-12-123456')
            ->and($driver->is_active)->toBeTrue();

        $this->assertDatabaseHas('drivers', [
            'name' => 'Juan Dela Cruz',
            'license_number' => 'N01-12-123456',
        ]);
    });

    it('can read a driver', function () {
        actingAs($this->user);

        $driver = Driver::factory()->create([
            'name' => 'Test Driver',
        ]);

        $foundDriver = Driver::find($driver->id);

        expect($foundDriver)->not->toBeNull()
            ->and($foundDriver->name)->toBe('Test Driver');
    });

    it('can read all drivers', function () {
        actingAs($this->user);

        Driver::factory()->count(5)->create();

        $drivers = Driver::all();

        expect($drivers)->toHaveCount(5);
    });

    it('can update a driver', function () {
        actingAs($this->user);

        $driver = Driver::factory()->create([
            'name' => 'Original Name',
            'is_active' => true,
        ]);

        $driver->update([
            'name' => 'Updated Name',
            'is_active' => false,
        ]);

        $fresh = $driver->fresh();
        expect($fresh->name)->toBe('Updated Name')
            ->and($fresh->is_active)->toBeFalse();
    });

    it('can delete a driver', function () {
        actingAs($this->user);

        $driver = Driver::factory()->create();
        $driverId = $driver->id;

        $driver->delete();

        $this->assertDatabaseMissing('drivers', ['id' => $driverId]);
    });

    it('can filter active drivers', function () {
        actingAs($this->user);

        Driver::factory()->count(4)->create(['is_active' => true]);
        Driver::factory()->count(2)->create(['is_active' => false]);

        $activeDrivers = Driver::where('is_active', true)->get();
        $inactiveDrivers = Driver::where('is_active', false)->get();

        expect($activeDrivers)->toHaveCount(4)
            ->and($inactiveDrivers)->toHaveCount(2);
    });

    it('has deliveries relationship', function () {
        actingAs($this->user);

        $driver = Driver::factory()->create();

        expect($driver->deliveries())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('has completed deliveries relationship', function () {
        actingAs($this->user);

        $driver = Driver::factory()->create();

        expect($driver->completedDeliveries())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('calculates delivery count attribute', function () {
        actingAs($this->user);

        $driver = Driver::factory()->create();

        // Without deliveries, count should be 0
        expect($driver->delivery_count)->toBe(0);
    });

    it('calculates success rate attribute', function () {
        actingAs($this->user);

        $driver = Driver::factory()->create();

        // Without deliveries, success rate should be 0
        expect($driver->success_rate)->toBe(0.0);
    });
});
