<?php

use App\Models\User;
use App\Models\Vehicle;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Vehicle CRUD Operations', function () {
    it('can create a vehicle', function () {
        actingAs($this->user);

        $vehicleData = [
            'plate_number' => 'ABC 1234',
            'make' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2022,
            'color' => 'White',
            'vin' => 'VIN123456789ABCDE',
            'engine_number' => 'ENG123456',
            'fuel_type' => 'diesel',
            'transmission' => 'automatic',
            'current_mileage' => 25000,
            'acquisition_date' => now()->subYear(),
            'acquisition_cost' => 1500000.00,
            'status' => 'active',
        ];

        $vehicle = Vehicle::create($vehicleData);

        expect($vehicle)->toBeInstanceOf(Vehicle::class)
            ->and($vehicle->plate_number)->toBe('ABC 1234')
            ->and($vehicle->make)->toBe('Toyota')
            ->and($vehicle->model)->toBe('Hilux')
            ->and($vehicle->year)->toBe(2022)
            ->and($vehicle->status)->toBe('active');

        $this->assertDatabaseHas('vehicles', [
            'plate_number' => 'ABC 1234',
            'make' => 'Toyota',
        ]);
    });

    it('can read a vehicle', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'XYZ 5678',
        ]);

        $foundVehicle = Vehicle::find($vehicle->id);

        expect($foundVehicle)->not->toBeNull()
            ->and($foundVehicle->plate_number)->toBe('XYZ 5678');
    });

    it('can read all vehicles', function () {
        actingAs($this->user);

        Vehicle::factory()->count(5)->create();

        $vehicles = Vehicle::all();

        expect($vehicles)->toHaveCount(5);
    });

    it('can update a vehicle', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create([
            'current_mileage' => 10000,
            'status' => 'active',
        ]);

        $vehicle->update([
            'current_mileage' => 15000,
            'status' => 'maintenance',
        ]);

        $fresh = $vehicle->fresh();
        expect($fresh->current_mileage)->toBe(15000)
            ->and($fresh->status)->toBe('maintenance');
    });

    it('can delete a vehicle', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create();
        $vehicleId = $vehicle->id;

        $vehicle->delete();

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicleId]);
    });

    it('has correct full name attribute', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create([
            'year' => 2023,
            'make' => 'Honda',
            'model' => 'City',
        ]);

        expect($vehicle->full_name)->toBe('2023 Honda City');
    });

    it('has correct display name attribute', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create([
            'plate_number' => 'DEF 9999',
            'year' => 2023,
            'make' => 'Honda',
            'model' => 'City',
        ]);

        expect($vehicle->display_name)->toBe('DEF 9999 - 2023 Honda City');
    });

    it('can filter by status', function () {
        actingAs($this->user);

        Vehicle::factory()->count(3)->create(['status' => 'active']);
        Vehicle::factory()->count(2)->create(['status' => 'maintenance']);
        Vehicle::factory()->count(1)->create(['status' => 'inactive']);

        $activeVehicles = Vehicle::where('status', 'active')->get();
        $maintenanceVehicles = Vehicle::where('status', 'maintenance')->get();

        expect($activeVehicles)->toHaveCount(3)
            ->and($maintenanceVehicles)->toHaveCount(2);
    });

    it('has maintenance records relationship', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create();

        expect($vehicle->maintenanceRecords())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('calculates total maintenance cost', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create();

        // Without any maintenance records, total should be 0
        expect($vehicle->total_maintenance_cost)->toBe(0.0);
    });
});
