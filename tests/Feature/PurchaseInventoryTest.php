<?php

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Filament\Resources\Purchases\Pages\CreatePurchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

function stockOf(Product $product): float
{
    return (float) Inventory::where('product_id', $product->id)->value('quantity');
}

function actAsPurchaseCreator(): void
{
    $user = User::factory()->create();
    foreach (['ViewAny:Purchase', 'View:Purchase', 'Create:Purchase'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
    $user->givePermissionTo(['ViewAny:Purchase', 'View:Purchase', 'Create:Purchase']);
    actingAs($user);
}

it('takes received stock back out when a purchase is deleted', function () {
    // Products get a starting stock from elsewhere, so assert deltas.
    $product = Product::factory()->create();
    $start = stockOf($product);

    $purchase = Purchase::factory()->create();
    PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'product_id' => $product->id,
        'quantity' => 50,
        'quantity_received' => 50,
        'price' => 10,
    ]);

    expect(stockOf($product))->toBe($start + 50);

    $purchase->delete();

    expect(stockOf($product))->toBe($start)
        ->and(PurchaseItem::count())->toBe(0)
        ->and(InventoryMovement::where('product_id', $product->id)->where('type', 'out')->sum('quantity'))->toEqual(50);
});

it('moves received stock to the new product when a line\'s product changes', function () {
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();
    [$startA, $startB] = [stockOf($productA), stockOf($productB)];

    $purchase = Purchase::factory()->create();
    $item = PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'product_id' => $productA->id,
        'quantity' => 10,
        'quantity_received' => 10,
        'price' => 10,
    ]);

    // Fresh instance, like Filament's edit form loading it from the DB.
    $item = PurchaseItem::find($item->id);
    $item->update(['product_id' => $productB->id]);

    expect(stockOf($productA))->toBe($startA)
        ->and(stockOf($productB))->toBe($startB + 10);
});

it('handles a product change and received qty change in the same save', function () {
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();
    [$startA, $startB] = [stockOf($productA), stockOf($productB)];

    $purchase = Purchase::factory()->create();
    $item = PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'product_id' => $productA->id,
        'quantity' => 10,
        'quantity_received' => 4,
        'price' => 10,
    ]);

    $item = PurchaseItem::find($item->id);
    $item->update(['product_id' => $productB->id, 'quantity_received' => 10]);

    expect(stockOf($productA))->toBe($startA)
        ->and(stockOf($productB))->toBe($startB + 10);
});

describe('custom items', function () {
    it('counts toward the total but never touches inventory', function () {
        $purchase = Purchase::factory()->create();
        $movementsBefore = InventoryMovement::count();
        $inventoryBefore = Inventory::sum('quantity');

        $item = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'custom_item_name' => 'Welding service',
            'quantity' => 2,
            'quantity_received' => 2,
            'unit' => 'piece',
            'price' => 1500,
        ]);

        expect((float) $purchase->fresh()->total)->toBe(3000.0)
            ->and($item->item_name)->toBe('Welding service');

        $item->update(['quantity_received' => 1]);
        $purchase->delete();

        expect(InventoryMovement::count())->toBe($movementsBefore)
            ->and(Inventory::sum('quantity'))->toEqual($inventoryBefore);
    });

    it('moves stock correctly when a line switches between product and custom', function () {
        $product = Product::factory()->create();
        $start = stockOf($product);

        $item = PurchaseItem::create([
            'purchase_id' => Purchase::factory()->create()->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quantity_received' => 5,
            'price' => 10,
        ]);
        expect(stockOf($product))->toBe($start + 5);

        PurchaseItem::find($item->id)->update(['product_id' => null, 'custom_item_name' => 'Other thing']);
        expect(stockOf($product))->toBe($start);

        PurchaseItem::find($item->id)->update(['product_id' => $product->id, 'custom_item_name' => null]);
        expect(stockOf($product))->toBe($start + 5);
    });

    it('can be added from the create purchase form', function () {
        actAsPurchaseCreator();

        $test = Livewire::test(CreatePurchase::class);
        $itemKey = array_key_first($test->get('data.purchase_items'));

        $test->fillForm([
            'supplier_id' => Supplier::factory()->create()->id,
            'date' => now()->toDateString(),
            'payment_status' => 'unpaid',
            'purchase_items' => [
                $itemKey => [
                    'is_custom' => true,
                    'custom_item_name' => 'Delivery fee',
                    'unit' => 'piece',
                    'quantity' => 1,
                    'quantity_received' => 1,
                    'price' => 800,
                ],
            ],
        ])->call('create')->assertHasNoFormErrors();

        $item = PurchaseItem::sole();
        expect($item->custom_item_name)->toBe('Delivery fee')
            ->and($item->product_id)->toBeNull()
            ->and((float) $item->purchase->total)->toBe(800.0);
    });

    it('requires a name when the line is marked custom', function () {
        actAsPurchaseCreator();

        $test = Livewire::test(CreatePurchase::class);
        $itemKey = array_key_first($test->get('data.purchase_items'));

        $test->fillForm([
            'supplier_id' => Supplier::factory()->create()->id,
            'date' => now()->toDateString(),
            'purchase_items' => [
                $itemKey => ['is_custom' => true, 'unit' => 'piece', 'quantity' => 1, 'quantity_received' => 0, 'price' => 1],
            ],
        ])->call('create')->assertHasFormErrors(["purchase_items.{$itemKey}.custom_item_name" => 'required']);

        expect(PurchaseItem::count())->toBe(0);
    });
});

it('switches an existing line to a custom item from the edit form', function () {
    $user = User::factory()->create();
    foreach (['ViewAny:Purchase', 'View:Purchase', 'Update:Purchase', 'RecordPaymentPurchase', 'Delete:Purchase'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
    $user->givePermissionTo(['ViewAny:Purchase', 'View:Purchase', 'Update:Purchase']);
    actingAs($user);

    $product = Product::factory()->create();
    $start = stockOf($product);
    $purchase = Purchase::factory()->create(['payment_status' => 'unpaid']);
    $item = PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'product_id' => $product->id,
        'unit' => 'piece',
        'quantity' => 3,
        'quantity_received' => 3,
        'price' => 100,
    ]);

    $test = Livewire::test(\App\Filament\Resources\Purchases\Pages\EditPurchase::class, ['record' => $purchase->getRouteKey()]);
    $itemKey = array_key_first($test->get('data.purchase_items'));

    $test->set("data.purchase_items.{$itemKey}.is_custom", true)
        ->set("data.purchase_items.{$itemKey}.custom_item_name", 'Cutting service')
        ->call('save')
        ->assertHasNoFormErrors();

    $item->refresh();
    expect($item->product_id)->toBeNull()
        ->and($item->custom_item_name)->toBe('Cutting service')
        ->and(stockOf($product))->toBe($start);
});
