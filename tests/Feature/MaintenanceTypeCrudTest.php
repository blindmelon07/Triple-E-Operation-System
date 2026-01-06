<?php

use App\Models\MaintenanceType;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('MaintenanceType CRUD Operations', function () {
    it('can create a maintenance type', function () {
        actingAs($this->user);

        $typeData = [
            'name' => 'Oil Change',
            'description' => 'Regular oil change service',
            'recommended_interval_months' => 3,
            'recommended_interval_km' => 5000,
            'is_active' => true,
        ];

        $type = MaintenanceType::create($typeData);

        expect($type)->toBeInstanceOf(MaintenanceType::class)
            ->and($type->name)->toBe('Oil Change')
            ->and($type->recommended_interval_months)->toBe(3)
            ->and($type->recommended_interval_km)->toBe(5000)
            ->and($type->is_active)->toBeTrue();

        $this->assertDatabaseHas('maintenance_types', [
            'name' => 'Oil Change',
        ]);
    });

    it('can read a maintenance type', function () {
        actingAs($this->user);

        $type = MaintenanceType::factory()->create([
            'name' => 'Brake Inspection',
        ]);

        $foundType = MaintenanceType::find($type->id);

        expect($foundType)->not->toBeNull()
            ->and($foundType->name)->toBe('Brake Inspection');
    });

    it('can read all maintenance types', function () {
        actingAs($this->user);

        MaintenanceType::factory()->count(5)->create();

        $types = MaintenanceType::all();

        expect($types)->toHaveCount(5);
    });

    it('can update a maintenance type', function () {
        actingAs($this->user);

        $type = MaintenanceType::factory()->create([
            'name' => 'Original Name',
        ]);

        $type->update([
            'name' => 'Updated Name',
            'recommended_interval_km' => 15000,
        ]);

        $fresh = $type->fresh();
        expect($fresh->name)->toBe('Updated Name')
            ->and($fresh->recommended_interval_km)->toBe(15000);
    });

    it('can delete a maintenance type', function () {
        actingAs($this->user);

        $type = MaintenanceType::factory()->create();
        $typeId = $type->id;

        $type->delete();

        $this->assertDatabaseMissing('maintenance_types', ['id' => $typeId]);
    });

    it('can filter active maintenance types', function () {
        actingAs($this->user);

        MaintenanceType::factory()->count(3)->create(['is_active' => true]);
        MaintenanceType::factory()->count(2)->create(['is_active' => false]);

        $activeTypes = MaintenanceType::where('is_active', true)->get();
        $inactiveTypes = MaintenanceType::where('is_active', false)->get();

        expect($activeTypes)->toHaveCount(3)
            ->and($inactiveTypes)->toHaveCount(2);
    });

    it('has maintenance records relationship', function () {
        actingAs($this->user);

        $type = MaintenanceType::factory()->create();

        expect($type->maintenanceRecords())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});
