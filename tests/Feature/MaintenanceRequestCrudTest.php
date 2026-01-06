<?php

use App\Models\MaintenanceRecord;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceType;
use App\Models\User;
use App\Models\Vehicle;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('MaintenanceRequest CRUD Operations', function () {
    it('can create a maintenance request', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create();
        $type = MaintenanceType::factory()->create();

        $requestData = [
            'request_number' => MaintenanceRequest::generateRequestNumber(),
            'vehicle_id' => $vehicle->id,
            'maintenance_type_id' => $type->id,
            'requested_by' => $this->user->id,
            'priority' => 'normal',
            'status' => 'pending',
            'current_mileage' => 50000,
            'description' => 'Vehicle needs oil change',
            'estimated_cost' => 3000.00,
            'preferred_date' => now()->addDays(7),
        ];

        $request = MaintenanceRequest::create($requestData);

        expect($request)->toBeInstanceOf(MaintenanceRequest::class)
            ->and($request->priority)->toBe('normal')
            ->and($request->status)->toBe('pending')
            ->and($request->current_mileage)->toBe(50000);

        $this->assertDatabaseHas('maintenance_requests', [
            'vehicle_id' => $vehicle->id,
            'priority' => 'normal',
        ]);
    });

    it('can read a maintenance request', function () {
        actingAs($this->user);

        $request = MaintenanceRequest::factory()->create();

        $foundRequest = MaintenanceRequest::find($request->id);

        expect($foundRequest)->not->toBeNull()
            ->and($foundRequest->id)->toBe($request->id);
    });

    it('can read all maintenance requests', function () {
        actingAs($this->user);

        // Create each request with explicit unique request numbers
        for ($i = 1; $i <= 5; $i++) {
            MaintenanceRequest::factory()->create([
                'request_number' => "REQ-TEST-{$i}",
            ]);
        }

        $requests = MaintenanceRequest::all();

        expect($requests)->toHaveCount(5);
    });

    it('can update a maintenance request', function () {
        actingAs($this->user);

        $request = MaintenanceRequest::factory()->create([
            'priority' => 'low',
            'status' => 'pending',
        ]);

        $request->update([
            'priority' => 'high',
            'description' => 'Updated description',
        ]);

        $fresh = $request->fresh();
        expect($fresh->priority)->toBe('high')
            ->and($fresh->description)->toBe('Updated description');
    });

    it('can delete a maintenance request', function () {
        actingAs($this->user);

        $request = MaintenanceRequest::factory()->create();
        $requestId = $request->id;

        $request->delete();

        $this->assertDatabaseMissing('maintenance_requests', ['id' => $requestId]);
    });

    it('belongs to a vehicle', function () {
        actingAs($this->user);

        $vehicle = Vehicle::factory()->create(['plate_number' => 'REQ 123']);
        $request = MaintenanceRequest::factory()->create(['vehicle_id' => $vehicle->id]);

        expect($request->vehicle)->toBeInstanceOf(Vehicle::class)
            ->and($request->vehicle->plate_number)->toBe('REQ 123');
    });

    it('belongs to a maintenance type', function () {
        actingAs($this->user);

        $type = MaintenanceType::factory()->create(['name' => 'Tire Rotation']);
        $request = MaintenanceRequest::factory()->create(['maintenance_type_id' => $type->id]);

        expect($request->maintenanceType)->toBeInstanceOf(MaintenanceType::class)
            ->and($request->maintenanceType->name)->toBe('Tire Rotation');
    });

    it('belongs to a requester user', function () {
        actingAs($this->user);

        $request = MaintenanceRequest::factory()->create(['requested_by' => $this->user->id]);

        expect($request->requestedBy)->toBeInstanceOf(User::class)
            ->and($request->requestedBy->id)->toBe($this->user->id);
    });

    it('generates unique request numbers', function () {
        actingAs($this->user);

        $num1 = MaintenanceRequest::generateRequestNumber();
        MaintenanceRequest::factory()->create(['request_number' => $num1]);
        $num2 = MaintenanceRequest::generateRequestNumber();

        expect($num1)->not->toBe($num2)
            ->and($num1)->toStartWith('REQ-')
            ->and($num2)->toStartWith('REQ-');
    });

    it('can filter by status', function () {
        actingAs($this->user);

        for ($i = 1; $i <= 3; $i++) {
            MaintenanceRequest::factory()->create([
                'request_number' => "REQ-PENDING-{$i}",
                'status' => 'pending',
            ]);
        }
        for ($i = 1; $i <= 2; $i++) {
            MaintenanceRequest::factory()->create([
                'request_number' => "REQ-APPROVED-{$i}",
                'status' => 'approved',
            ]);
        }
        MaintenanceRequest::factory()->create([
            'request_number' => 'REQ-REJECTED-1',
            'status' => 'rejected',
        ]);

        $pending = MaintenanceRequest::where('status', 'pending')->get();
        $approved = MaintenanceRequest::where('status', 'approved')->get();
        $rejected = MaintenanceRequest::where('status', 'rejected')->get();

        expect($pending)->toHaveCount(3)
            ->and($approved)->toHaveCount(2)
            ->and($rejected)->toHaveCount(1);
    });

    it('can filter by priority', function () {
        actingAs($this->user);

        for ($i = 1; $i <= 2; $i++) {
            MaintenanceRequest::factory()->create([
                'request_number' => "REQ-URGENT-{$i}",
                'priority' => 'urgent',
            ]);
        }
        for ($i = 1; $i <= 3; $i++) {
            MaintenanceRequest::factory()->create([
                'request_number' => "REQ-NORMAL-{$i}",
                'priority' => 'normal',
            ]);
        }

        $urgent = MaintenanceRequest::where('priority', 'urgent')->get();
        $normal = MaintenanceRequest::where('priority', 'normal')->get();

        expect($urgent)->toHaveCount(2)
            ->and($normal)->toHaveCount(3);
    });

    it('can be approved', function () {
        actingAs($this->user);

        $request = MaintenanceRequest::factory()->create([
            'status' => 'pending',
        ]);

        $request->update([
            'status' => 'approved',
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        $fresh = $request->fresh();
        expect($fresh->status)->toBe('approved')
            ->and($fresh->approved_by)->toBe($this->user->id)
            ->and($fresh->approved_at)->not->toBeNull();
    });

    it('can be rejected', function () {
        actingAs($this->user);

        $request = MaintenanceRequest::factory()->create([
            'status' => 'pending',
        ]);

        $request->update([
            'status' => 'rejected',
            'rejection_reason' => 'Budget constraints',
            'rejected_at' => now(),
        ]);

        $fresh = $request->fresh();
        expect($fresh->status)->toBe('rejected')
            ->and($fresh->rejection_reason)->toBe('Budget constraints')
            ->and($fresh->rejected_at)->not->toBeNull();
    });

    it('can have a linked maintenance record after approval', function () {
        actingAs($this->user);

        $request = MaintenanceRequest::factory()->create();
        $record = MaintenanceRecord::factory()->create();

        $request->update([
            'maintenance_record_id' => $record->id,
        ]);

        expect($request->fresh()->maintenance_record_id)->toBe($record->id);
    });
});
