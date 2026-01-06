<?php

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Purchase CRUD Operations', function () {
    it('can create a purchase', function () {
        actingAs($this->user);

        $supplier = Supplier::factory()->create();

        $purchaseData = [
            'supplier_id' => $supplier->id,
            'date' => now()->toDateString(),
            'total' => 5000.00,
            'payment_status' => 'unpaid',
            'amount_paid' => 0,
        ];

        $purchase = Purchase::create($purchaseData);

        expect($purchase)->toBeInstanceOf(Purchase::class)
            ->and($purchase->payment_status)->toBe('unpaid')
            ->and($purchase->supplier_id)->toBe($supplier->id);

        $this->assertDatabaseHas('purchases', [
            'supplier_id' => $supplier->id,
        ]);
    });

    it('can read a purchase', function () {
        actingAs($this->user);

        $purchase = Purchase::factory()->create();

        $foundPurchase = Purchase::find($purchase->id);

        expect($foundPurchase)->not->toBeNull()
            ->and($foundPurchase->id)->toBe($purchase->id);
    });

    it('can read all purchases', function () {
        actingAs($this->user);

        Purchase::factory()->count(5)->create();

        $purchases = Purchase::all();

        expect($purchases)->toHaveCount(5);
    });

    it('can update a purchase', function () {
        actingAs($this->user);

        $purchase = Purchase::factory()->create([
            'payment_status' => 'unpaid',
            'amount_paid' => 0,
        ]);

        $purchase->update([
            'payment_status' => 'paid',
            'amount_paid' => $purchase->total,
            'paid_date' => now(),
        ]);

        $fresh = $purchase->fresh();
        expect($fresh->payment_status)->toBe('paid')
            ->and($fresh->paid_date)->not->toBeNull();
    });

    it('can delete a purchase', function () {
        actingAs($this->user);

        $purchase = Purchase::factory()->create();
        $purchaseId = $purchase->id;

        $purchase->delete();

        $this->assertDatabaseMissing('purchases', ['id' => $purchaseId]);
    });

    it('belongs to a supplier', function () {
        actingAs($this->user);

        $supplier = Supplier::factory()->create(['name' => 'Test Supplier']);
        $purchase = Purchase::factory()->create(['supplier_id' => $supplier->id]);

        expect($purchase->supplier)->toBeInstanceOf(Supplier::class)
            ->and($purchase->supplier->name)->toBe('Test Supplier');
    });

    it('calculates balance correctly', function () {
        actingAs($this->user);

        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        // Create purchase without items first
        $purchase = Purchase::create([
            'supplier_id' => $supplier->id,
            'date' => now(),
            'amount_paid' => 500.00,
            'payment_status' => 'partial',
        ]);

        // Add purchase items
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 1000.00,
        ]);

        // Trigger save event to recalculate total from purchase items
        $purchase->touch();
        $purchase->refresh();

        // Total should now be 2000 (2 * 1000), balance = 2000 - 500 = 1500
        expect((float) $purchase->total)->toBe(2000.00)
            ->and($purchase->balance)->toBe(1500.00);
    });

    it('has purchase_items relationship', function () {
        actingAs($this->user);

        $purchase = Purchase::factory()->create();

        expect($purchase->purchase_items())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});
