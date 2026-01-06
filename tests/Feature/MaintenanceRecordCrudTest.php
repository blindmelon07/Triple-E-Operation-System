<?php

use App\Models\MaintenanceRecord;
use App\Models\MaintenanceType;
use App\Models\User;
use App\Models\Vehicle;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('MaintenanceRecord CRUD Operations', function () {
    it('can create a maintenance record', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create();
        $type = MaintenanceType::factory()->create();

        $recordData = [
            'vehicle_id' => $vehicle->id,
            'maintenance_type_id' => $type->id,
            'user_id' => $this->user->id,
            'reference_number' => MaintenanceRecord::generateReferenceNumber(),
            'maintenance_date' => now()->toDateString(),
            'mileage_at_service' => 50000,
            'cost' => 5000.00,
            'parts_cost' => 3000.00,
            'labor_cost' => 2000.00,
            'service_provider' => 'Test Garage',
            'description' => 'Routine maintenance',
            'status' => 'completed',
        ];

        $record = MaintenanceRecord::create($recordData);

        expect($record)->toBeInstanceOf(MaintenanceRecord::class)
            ->and((float) $record->cost)->toBe(5000.00)
            ->and($record->service_provider)->toBe('Test Garage')
            ->and($record->status)->toBe('completed');

        $this->assertDatabaseHas('maintenance_records', [
            'vehicle_id' => $vehicle->id,
            'service_provider' => 'Test Garage',
        ]);
    });

    it('can read a maintenance record', function () {
        actingAs($this->user);

        $record = MaintenanceRecord::factory()->create();

        $foundRecord = MaintenanceRecord::find($record->id);

        expect($foundRecord)->not->toBeNull()
            ->and($foundRecord->id)->toBe($record->id);
    });

    it('can read all maintenance records', function () {
        actingAs($this->user);

        MaintenanceRecord::factory()->count(5)->create();

        $records = MaintenanceRecord::all();

        expect($records)->toHaveCount(5);
    });

    it('can update a maintenance record', function () {
        actingAs($this->user);

        $record = MaintenanceRecord::factory()->create([
            'status' => 'scheduled',
            'cost' => 1000.00,
        ]);

        $record->update([
            'status' => 'completed',
            'cost' => 1500.00,
        ]);

        $fresh = $record->fresh();
        expect($fresh->status)->toBe('completed')
            ->and((float) $fresh->cost)->toBe(1500.00);
    });

    it('can delete a maintenance record', function () {
        actingAs($this->user);

        $record = MaintenanceRecord::factory()->create();
        $recordId = $record->id;

        $record->delete();

        $this->assertDatabaseMissing('maintenance_records', ['id' => $recordId]);
    });

    it('belongs to a vehicle', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create(['plate_number' => 'TEST 123']);
        $record = MaintenanceRecord::factory()->create(['vehicle_id' => $vehicle->id]);

        expect($record->vehicle)->toBeInstanceOf(Vehicle::class)
            ->and($record->vehicle->plate_number)->toBe('TEST 123');
    });

    it('belongs to a maintenance type', function () {
        actingAs($this->user);

        $type = MaintenanceType::factory()->create(['name' => 'Oil Change']);
        $record = MaintenanceRecord::factory()->create(['maintenance_type_id' => $type->id]);

        expect($record->maintenanceType)->toBeInstanceOf(MaintenanceType::class)
            ->and($record->maintenanceType->name)->toBe('Oil Change');
    });

    it('belongs to a user', function () {
        actingAs($this->user);

        $record = MaintenanceRecord::factory()->create(['user_id' => $this->user->id]);

        expect($record->user)->toBeInstanceOf(User::class)
            ->and($record->user->id)->toBe($this->user->id);
    });

    it('generates unique reference numbers', function () {
        actingAs($this->user);

        $ref1 = MaintenanceRecord::generateReferenceNumber();
        MaintenanceRecord::factory()->create(['reference_number' => $ref1]);
        $ref2 = MaintenanceRecord::generateReferenceNumber();

        expect($ref1)->not->toBe($ref2)
            ->and($ref1)->toStartWith('MNT-')
            ->and($ref2)->toStartWith('MNT-');
    });

    it('can filter by status', function () {
        actingAs($this->user);

        MaintenanceRecord::factory()->count(3)->create(['status' => 'completed']);
        MaintenanceRecord::factory()->count(2)->create(['status' => 'scheduled']);

        $completed = MaintenanceRecord::where('status', 'completed')->get();
        $scheduled = MaintenanceRecord::where('status', 'scheduled')->get();

        expect($completed)->toHaveCount(3)
            ->and($scheduled)->toHaveCount(2);
    });

    it('can filter by vehicle', function () {
        actingAs($this->user);

        $vehicle1 = Vehicle::factory()->create();
        $vehicle2 = Vehicle::factory()->create();

        MaintenanceRecord::factory()->count(3)->create(['vehicle_id' => $vehicle1->id]);
        MaintenanceRecord::factory()->count(2)->create(['vehicle_id' => $vehicle2->id]);

        $vehicle1Records = MaintenanceRecord::where('vehicle_id', $vehicle1->id)->get();

        expect($vehicle1Records)->toHaveCount(3);
    });
});
