<?php

use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Inventory CRUD Operations', function () {
    it('can create an inventory record', function () {
        actingAs($this->user);

        $product = Product::factory()->create();

        $inventoryData = [
            'product_id' => $product->id,
            'quantity' => 100,
        ];

        $inventory = Inventory::create($inventoryData);

        expect($inventory)->toBeInstanceOf(Inventory::class)
            ->and($inventory->quantity)->toBe(100);

        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'quantity' => 100,
        ]);
    });

    it('can read an inventory record', function () {
        actingAs($this->user);

        $inventory = Inventory::factory()->create([
            'quantity' => 50,
        ]);

        $foundInventory = Inventory::find($inventory->id);

        expect($foundInventory)->not->toBeNull()
            ->and($foundInventory->quantity)->toBe(50);
    });

    it('can read all inventory records', function () {
        actingAs($this->user);

        // Create products without the afterCreating hook triggering duplicate inventories
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        // ProductFactory's afterCreating hook creates inventory records automatically
        // So we just need to verify that count matches the number of products created
        $inventories = Inventory::all();

        expect($inventories)->toHaveCount(3);
    });

    it('can update an inventory record', function () {
        actingAs($this->user);

        $inventory = Inventory::factory()->create([
            'quantity' => 50,
        ]);

        $inventory->update([
            'quantity' => 75,
        ]);

        $fresh = $inventory->fresh();
        expect($fresh->quantity)->toBe(75);
    });

    it('can delete an inventory record', function () {
        actingAs($this->user);

        $inventory = Inventory::factory()->create();
        $inventoryId = $inventory->id;

        $inventory->delete();

        $this->assertDatabaseMissing('inventories', ['id' => $inventoryId]);
    });

    it('belongs to a product', function () {
        actingAs($this->user);

        $product = Product::factory()->create(['name' => 'Test Product']);
        $inventory = Inventory::factory()->create(['product_id' => $product->id]);

        expect($inventory->product)->toBeInstanceOf(Product::class)
            ->and($inventory->product->name)->toBe('Test Product');
    });

    it('can detect low stock based on quantity', function () {
        actingAs($this->user);

        $lowStock = Inventory::factory()->create([
            'quantity' => 5,
        ]);

        $normalStock = Inventory::factory()->create([
            'quantity' => 50,
        ]);

        // Check that we can query items with low quantity
        $lowStockItems = Inventory::where('quantity', '<=', 10)->get();

        expect($lowStockItems)->toHaveCount(1)
            ->and($lowStockItems->first()->id)->toBe($lowStock->id);
    });

    it('can track stock quantities', function () {
        actingAs($this->user);

        $inventory = Inventory::factory()->create([
            'quantity' => 100,
        ]);

        // Simulate a stock decrease
        $inventory->decrement('quantity', 25);

        expect($inventory->fresh()->quantity)->toBe(75);

        // Simulate a stock increase
        $inventory->increment('quantity', 10);

        expect($inventory->fresh()->quantity)->toBe(85);
    });
});
