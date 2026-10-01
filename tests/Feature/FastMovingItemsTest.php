<?php

use App\Filament\Pages\CustomReportBuilder;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductUnitPrice;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Support\ReportBuilder\FastMovingItemsService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function sellItem(Product $product, float $quantity, float $price, string $date, array $overrides = [], bool $saleVoided = false): SaleItem
{
    $sale = Sale::factory()->create(['date' => $date, 'is_voided' => $saleVoided]);

    return SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'unit' => $product->unit?->value ?? 'piece',
        'unit_price' => $price,
        'quantity' => $quantity,
        'price' => $quantity * $price,
        ...$overrides,
    ]);
}

describe('FastMovingItemsService', function () {
    it('ranks products by base-unit quantity sold, ignoring voided items and sales', function () {
        $nails = Product::factory()->create(['name' => 'Nails', 'unit' => 'piece']);
        $cement = Product::factory()->create(['name' => 'Cement', 'unit' => 'bag']);
        $paint = Product::factory()->create(['name' => 'Paint', 'unit' => 'gallon']);
        ProductUnitPrice::create(['product_id' => $nails->id, 'unit' => 'box', 'price' => 100, 'conversion_factor' => 50]);

        sellItem($nails, 10, 2, '2026-09-10');
        sellItem($nails, 1, 100, '2026-09-11', ['unit' => 'box']); // 50 pieces
        sellItem($cement, 30, 250, '2026-09-12');
        sellItem($cement, 100, 250, '2026-09-12', ['is_voided' => true]); // returned
        sellItem($paint, 100, 500, '2026-09-12', [], saleVoided: true);
        sellItem($paint, 5, 500, '2026-09-13');
        sellItem($paint, 999, 500, '2026-08-01'); // outside range

        $rows = (new FastMovingItemsService)->build('2026-09-01', '2026-09-30');

        expect(array_column($rows, 'name'))->toBe(['Nails', 'Cement', 'Paint'])
            ->and($rows[0]['rank'])->toBe(1)
            ->and($rows[0]['quantity_sold'])->toBe(60.0)
            ->and($rows[0]['transactions'])->toBe(2)
            ->and($rows[0]['sales_amount'])->toBe(120.0)
            ->and($rows[0]['avg_daily'])->toBe(2.0)
            ->and($rows[1]['quantity_sold'])->toBe(30.0)
            ->and($rows[2]['quantity_sold'])->toBe(5.0);
    });

    it('reports stock in and out within the period', function () {
        $nails = Product::factory()->create(['unit' => 'piece']);

        sellItem($nails, 10, 2, now()->toDateString()); // observer logs a 10-piece "out"
        InventoryMovement::create(['product_id' => $nails->id, 'type' => 'in', 'quantity' => 100, 'reason' => 'Purchase']);
        InventoryMovement::create(['product_id' => $nails->id, 'type' => 'in', 'quantity' => 999, 'reason' => 'Purchase'])
            ->forceFill(['created_at' => now()->subMonths(2)])->save(); // outside range

        $rows = (new FastMovingItemsService)->build(now()->subDays(29)->toDateString(), now()->toDateString());

        expect($rows[0]['stock_in'])->toBe(100.0)
            ->and($rows[0]['stock_out'])->toBe(10.0);
    });

    it('filters by category and respects the limit', function () {
        $tools = Category::factory()->create();
        $other = Category::factory()->create();
        $a = Product::factory()->create(['category_id' => $tools->id]);
        $b = Product::factory()->create(['category_id' => $tools->id]);
        $c = Product::factory()->create(['category_id' => $other->id]);

        sellItem($a, 5, 10, '2026-09-10');
        sellItem($b, 8, 10, '2026-09-10');
        sellItem($c, 50, 10, '2026-09-10');

        $rows = (new FastMovingItemsService)->build('2026-09-01', '2026-09-30', $tools->id, 1);

        expect($rows)->toHaveCount(1)
            ->and($rows[0]['product_id'])->toBe($b->id);
    });
});

it('generates and exports the fast moving report from the Report Builder', function () {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'View:CustomReportBuilder', 'guard_name' => 'web']);
    $user->givePermissionTo('View:CustomReportBuilder');
    actingAs($user);

    sellItem(Product::factory()->create(), 3, 10, now()->toDateString());

    Livewire::test(CustomReportBuilder::class)
        ->set('reportMode', 'fast_moving')
        ->call('generateFastMoving')
        ->assertSet('fastGenerated', true)
        ->assertCount('fastRows', 1)
        ->callAction('exportFastMovingPdf')
        ->assertFileDownloaded();
});
