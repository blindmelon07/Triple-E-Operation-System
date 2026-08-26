<?php

use App\Filament\Resources\Quotations\Pages\CreateQuotation;
use App\Filament\Resources\Quotations\Pages\EditQuotation;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Quotation down payment — model', function () {
    it('is fillable and defaults to zero', function () {
        $quotation = Quotation::factory()->create(['total' => 1000]);

        expect((float) $quotation->down_payment)->toBe(0.0);
    });

    it('calculates balance as total minus down payment', function () {
        $quotation = Quotation::factory()->create([
            'total' => 1000,
            'down_payment' => 400,
        ]);

        expect($quotation->balance)->toBe(600.0);
    });

    it('has a full balance when no down payment is recorded', function () {
        $quotation = Quotation::factory()->create([
            'total' => 750,
            'down_payment' => 0,
        ]);

        expect($quotation->balance)->toBe(750.0);
    });
});

describe('Quotation down payment — walk-in (POS)', function () {
    it('accepts an optional down payment when creating a quotation', function () {
        actingAs($this->user);

        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100]);

        $response = postJson('/pos/quotation', [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'id' => $product->id,
                    'is_manual' => false,
                    'name' => $product->name,
                    'quantity' => 2,
                    'price' => 200,
                    'unit_price' => 100,
                    'unit' => 'piece',
                ],
            ],
            'total' => 200,
            'down_payment' => 50,
            'valid_days' => 30,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $quotation = Quotation::latest('id')->first();

        expect((float) $quotation->total)->toBe(200.0)
            ->and((float) $quotation->down_payment)->toBe(50.0)
            ->and($quotation->balance)->toBe(150.0);
    });

    it('defaults the down payment to zero when omitted', function () {
        actingAs($this->user);

        $product = Product::factory()->create(['price' => 100]);

        $response = postJson('/pos/quotation', [
            'customer_id' => null,
            'items' => [
                [
                    'id' => $product->id,
                    'is_manual' => false,
                    'name' => $product->name,
                    'quantity' => 1,
                    'price' => 100,
                    'unit_price' => 100,
                    'unit' => 'piece',
                ],
            ],
            'total' => 100,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $quotation = Quotation::latest('id')->first();

        expect((float) $quotation->down_payment)->toBe(0.0)
            ->and($quotation->balance)->toBe(100.0);
    });

    it('clamps a down payment that exceeds the total', function () {
        actingAs($this->user);

        $product = Product::factory()->create(['price' => 100]);

        $response = postJson('/pos/quotation', [
            'customer_id' => null,
            'items' => [
                [
                    'id' => $product->id,
                    'is_manual' => false,
                    'name' => $product->name,
                    'quantity' => 1,
                    'price' => 100,
                    'unit_price' => 100,
                    'unit' => 'piece',
                ],
            ],
            'total' => 100,
            'down_payment' => 999,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $quotation = Quotation::latest('id')->first();

        expect((float) $quotation->down_payment)->toBe(100.0)
            ->and($quotation->balance)->toBe(0.0);
    });
});

describe('Quotation down payment — online transaction (admin panel)', function () {
    beforeEach(function () {
        foreach (['ViewAny:Quotation', 'View:Quotation', 'Create:Quotation', 'Update:Quotation'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $this->user->givePermissionTo(['ViewAny:Quotation', 'View:Quotation', 'Create:Quotation', 'Update:Quotation']);
        actingAs($this->user);
    });

    it('saves an optional down payment when creating a quotation', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100]);

        // The form starts with one blank repeater row (defaultItems(1)) keyed by
        // an auto-generated UUID. We reuse that key so our data replaces the
        // blank row instead of being appended alongside it.
        $test = Livewire::test(CreateQuotation::class);
        $itemKey = array_key_first($test->get('data')['quotation_items']);

        $test->fillForm([
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'status' => 'pending',
            'down_payment' => 50,
            'quotation_items' => [
                $itemKey => [
                    'is_manual' => false,
                    'product_id' => $product->id,
                    'unit' => 'piece',
                    'quantity' => 2,
                    'unit_price' => 100,
                    'price' => 200,
                ],
            ],
        ])
            ->call('create')
            ->assertHasNoFormErrors();

        $quotation = Quotation::latest('id')->first();

        expect((float) $quotation->total)->toBe(200.0)
            ->and((float) $quotation->down_payment)->toBe(50.0)
            ->and($quotation->balance)->toBe(150.0);
    });

    it('clamps a down payment that exceeds the total when creating a quotation', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100]);

        $test = Livewire::test(CreateQuotation::class);
        $itemKey = array_key_first($test->get('data')['quotation_items']);

        $test->fillForm([
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'status' => 'pending',
            'down_payment' => 9999,
            'quotation_items' => [
                $itemKey => [
                    'is_manual' => false,
                    'product_id' => $product->id,
                    'unit' => 'piece',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'price' => 100,
                ],
            ],
        ])
            ->call('create')
            ->assertHasNoFormErrors();

        $quotation = Quotation::latest('id')->first();

        expect((float) $quotation->down_payment)->toBe(100.0)
            ->and($quotation->balance)->toBe(0.0);
    });

    it('updates and re-clamps the down payment when editing a quotation', function () {
        $quotation = Quotation::factory()->create(['total' => 200, 'down_payment' => 0]);
        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'quantity' => 2,
            'unit_price' => 100,
            'price' => 200,
        ]);

        Livewire::test(EditQuotation::class, ['record' => $quotation->getRouteKey()])
            ->fillForm(['down_payment' => 9999])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $quotation->fresh();

        expect((float) $fresh->down_payment)->toBe(200.0)
            ->and($fresh->balance)->toBe(0.0);
    });
});
