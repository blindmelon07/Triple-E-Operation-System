<?php

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Sale CRUD Operations', function () {
    it('can create a sale', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();

        $saleData = [
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'total' => 1000.00,
            'payment_status' => 'unpaid',
            'amount_paid' => 0,
        ];

        $sale = Sale::create($saleData);

        expect($sale)->toBeInstanceOf(Sale::class)
            ->and((float) $sale->total)->toBe(1000.00)
            ->and($sale->payment_status)->toBe('unpaid')
            ->and($sale->customer_id)->toBe($customer->id);

        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'total' => 1000.00,
        ]);
    });

    it('can read a sale', function () {
        actingAs($this->user);

        $sale = Sale::factory()->create();

        $foundSale = Sale::find($sale->id);

        expect($foundSale)->not->toBeNull()
            ->and($foundSale->id)->toBe($sale->id);
    });

    it('can read all sales', function () {
        actingAs($this->user);

        Sale::factory()->count(5)->create();

        $sales = Sale::all();

        expect($sales)->toHaveCount(5);
    });

    it('can update a sale', function () {
        actingAs($this->user);

        $sale = Sale::factory()->create([
            'payment_status' => 'unpaid',
            'amount_paid' => 0,
        ]);

        $sale->update([
            'payment_status' => 'partial',
            'amount_paid' => 500.00,
        ]);

        $fresh = $sale->fresh();
        expect($fresh->payment_status)->toBe('partial')
            ->and((float) $fresh->amount_paid)->toBe(500.00);
    });

    it('can delete a sale', function () {
        actingAs($this->user);

        $sale = Sale::factory()->create();
        $saleId = $sale->id;

        $sale->delete();

        $this->assertDatabaseMissing('sales', ['id' => $saleId]);
    });

    it('belongs to a customer', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create(['name' => 'Test Customer']);
        $sale = Sale::factory()->create(['customer_id' => $customer->id]);

        expect($sale->customer)->toBeInstanceOf(Customer::class)
            ->and($sale->customer->name)->toBe('Test Customer');
    });

    it('calculates balance correctly', function () {
        actingAs($this->user);

        $sale = Sale::factory()->create([
            'total' => 1000.00,
            'amount_paid' => 300.00,
        ]);

        expect($sale->balance)->toBe(700.00);
    });

    it('has sale_items relationship', function () {
        actingAs($this->user);

        $sale = Sale::factory()->create();

        expect($sale->sale_items())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});
