<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Purchases\Pages\CreatePurchase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductPrice;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Supplier base price — model', function () {
    it('belongs to a product and a supplier', function () {
        $price = SupplierProductPrice::factory()->create(['base_price' => 250]);

        expect($price->product)->toBeInstanceOf(Product::class)
            ->and($price->supplier)->toBeInstanceOf(Supplier::class)
            ->and((float) $price->base_price)->toBe(250.0);
    });

    it('can be listed from the product side', function () {
        $product = Product::factory()->create();
        SupplierProductPrice::factory()->count(3)->create(['product_id' => $product->id]);

        expect($product->supplierPrices)->toHaveCount(3);
    });

    it('prevents two prices for the same product and supplier', function () {
        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();

        SupplierProductPrice::factory()->create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
        ]);

        expect(fn () => SupplierProductPrice::factory()->create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });
});

describe('Supplier base price — managed on the product form', function () {
    beforeEach(function () {
        foreach (['ViewAny:Product', 'View:Product', 'Create:Product'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $this->user->givePermissionTo(['ViewAny:Product', 'View:Product', 'Create:Product']);
        actingAs($this->user);
    });

    it('saves supplier base prices when creating a product', function () {
        $category = Category::factory()->create();
        $primarySupplier = Supplier::factory()->create();
        $jbj = Supplier::factory()->create(['name' => 'JBJ']);
        $casini = Supplier::factory()->create(['name' => 'Casini']);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Angle Bar 1.5mm x1 (1/8)',
                'category_id' => $category->id,
                'supplier_id' => $primarySupplier->id,
                'price' => 300,
                'quantity' => 100,
                'unit' => 'piece',
                'supplierPrices' => [
                    ['supplier_id' => $jbj->id, 'base_price' => 230],
                    ['supplier_id' => $casini->id, 'base_price' => 274],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('name', 'Angle Bar 1.5mm x1 (1/8)')->first();

        expect($product->supplierPrices)->toHaveCount(2);

        $this->assertDatabaseHas('supplier_product_prices', [
            'product_id' => $product->id,
            'supplier_id' => $jbj->id,
            'base_price' => 230.00,
        ]);
        $this->assertDatabaseHas('supplier_product_prices', [
            'product_id' => $product->id,
            'supplier_id' => $casini->id,
            'base_price' => 274.00,
        ]);
    });
});

describe('Supplier base price — comparison on the Purchase Order', function () {
    beforeEach(function () {
        foreach (['ViewAny:Purchase', 'View:Purchase', 'Create:Purchase'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $this->user->givePermissionTo(['ViewAny:Purchase', 'View:Purchase', 'Create:Purchase']);
        actingAs($this->user);
    });

    it('shows every supplier\'s base price for the selected product', function () {
        $product = Product::factory()->create(['name' => 'Angle Bar 1.5mm x1 (1/8)']);
        $jbj = Supplier::factory()->create(['name' => 'JBJ']);
        $casini = Supplier::factory()->create(['name' => 'Casini']);
        $fussen = Supplier::factory()->create(['name' => 'Fussen']);

        SupplierProductPrice::factory()->create(['product_id' => $product->id, 'supplier_id' => $jbj->id, 'base_price' => 230]);
        SupplierProductPrice::factory()->create(['product_id' => $product->id, 'supplier_id' => $casini->id, 'base_price' => 274]);
        SupplierProductPrice::factory()->create(['product_id' => $product->id, 'supplier_id' => $fussen->id, 'base_price' => 250]);

        // The repeater starts with one default (blank) row — reuse its key so
        // our fillForm data replaces it instead of being appended alongside it.
        $test = Livewire::test(CreatePurchase::class);
        $itemKey = array_key_first($test->get('data.purchase_items'));

        $test->fillForm([
            'supplier_id' => $casini->id,
            'date' => now()->toDateString(),
            'purchase_items' => [
                $itemKey => [
                    'product_id' => $product->id,
                    'unit' => 'piece',
                    'quantity' => 10,
                    'quantity_received' => 0,
                    'price' => 274,
                ],
            ],
        ]);

        $test->assertSee('JBJ: ₱230.00')
            ->assertSee('Casini: ₱274.00')
            ->assertSee('Fussen: ₱250.00')
            ->assertSee('selected supplier');
    });

    it('tells the user when no supplier base prices are recorded yet', function () {
        $product = Product::factory()->create();
        Supplier::factory()->create();

        $test = Livewire::test(CreatePurchase::class);
        $itemKey = array_key_first($test->get('data.purchase_items'));

        $test->fillForm([
            'date' => now()->toDateString(),
            'purchase_items' => [
                $itemKey => [
                    'product_id' => $product->id,
                    'unit' => 'piece',
                    'quantity' => 1,
                    'quantity_received' => 0,
                    'price' => 100,
                ],
            ],
        ]);

        $test->assertSee('No supplier base prices recorded yet for this product.');
    });
});
