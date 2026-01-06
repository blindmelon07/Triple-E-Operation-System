<?php

use App\Models\Supplier;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Supplier CRUD Operations', function () {
    it('can create a supplier', function () {
        actingAs($this->user);

        $supplierData = [
            'name' => 'Test Supplier',
            'contact_person' => 'Jane Doe',
            'phone' => '09181234567',
            'email' => 'supplier@test.com',
            'address' => '456 Supplier Street',
        ];

        $supplier = Supplier::create($supplierData);

        expect($supplier)->toBeInstanceOf(Supplier::class)
            ->and($supplier->name)->toBe('Test Supplier')
            ->and($supplier->email)->toBe('supplier@test.com');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Test Supplier',
            'email' => 'supplier@test.com',
        ]);
    });

    it('can read a supplier', function () {
        actingAs($this->user);

        $supplier = Supplier::factory()->create([
            'name' => 'Read Test Supplier',
        ]);

        $foundSupplier = Supplier::find($supplier->id);

        expect($foundSupplier)->not->toBeNull()
            ->and($foundSupplier->name)->toBe('Read Test Supplier');
    });

    it('can read all suppliers', function () {
        actingAs($this->user);

        Supplier::factory()->count(5)->create();

        $suppliers = Supplier::all();

        expect($suppliers)->toHaveCount(5);
    });

    it('can update a supplier', function () {
        actingAs($this->user);

        $supplier = Supplier::factory()->create([
            'name' => 'Original Supplier',
        ]);

        $supplier->update([
            'name' => 'Updated Supplier',
        ]);

        $fresh = $supplier->fresh();
        expect($fresh->name)->toBe('Updated Supplier');
    });

    it('can delete a supplier', function () {
        actingAs($this->user);

        $supplier = Supplier::factory()->create();
        $supplierId = $supplier->id;

        $supplier->delete();

        $this->assertDatabaseMissing('suppliers', ['id' => $supplierId]);
    });

    it('can have purchases relationship', function () {
        actingAs($this->user);

        $supplier = Supplier::factory()->create();

        expect($supplier->purchases())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});
