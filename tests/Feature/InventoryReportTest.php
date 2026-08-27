<?php

use App\Exports\InventoryReportExport;
use App\Filament\Pages\InventoryReport;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'View:InventoryReport', 'guard_name' => 'web']);
    $this->user->givePermissionTo('View:InventoryReport');
    actingAs($this->user);
});

function makeMovement(Product $product, string $type, float $quantity = 1): InventoryMovement
{
    return InventoryMovement::create([
        'product_id' => $product->id,
        'type' => $type,
        'quantity' => $quantity,
        'reason' => $type === 'in' ? 'Restock' : 'Sale',
    ]);
}

describe('InventoryReportExport', function () {
    it('includes both in and out movements when no type is given', function () {
        $product = Product::factory()->create();
        makeMovement($product, 'in');
        makeMovement($product, 'out');

        $export = new InventoryReportExport;

        expect($export->getData())->toHaveCount(2);
    });

    it('filters to only "in" movements when type is "in"', function () {
        $product = Product::factory()->create();
        makeMovement($product, 'in', 5);
        makeMovement($product, 'out', 3);

        $export = new InventoryReportExport(type: 'in');
        $data = $export->getData();

        expect($data)->toHaveCount(1)
            ->and($data->first()['Type'])->toBe('In')
            ->and($data->first()['Quantity'])->toBe(5.0);
    });

    it('filters to only "out" movements when type is "out"', function () {
        $product = Product::factory()->create();
        makeMovement($product, 'in', 5);
        makeMovement($product, 'out', 3);

        $export = new InventoryReportExport(type: 'out');
        $data = $export->getData();

        expect($data)->toHaveCount(1)
            ->and($data->first()['Type'])->toBe('Out')
            ->and($data->first()['Quantity'])->toBe(3.0);
    });

    it('includes the type in the exported filename when filtered', function () {
        $export = new InventoryReportExport(period: 'today', type: 'in');

        expect($export->getFilename())->toContain('today-in');
    });
});

describe('InventoryReport — table', function () {
    it('lists both in and out movements by default', function () {
        $product = Product::factory()->create(['name' => 'Widget']);
        makeMovement($product, 'in');
        makeMovement($product, 'out');

        Livewire::test(InventoryReport::class)
            ->assertSee('In')
            ->assertSee('Out');
    });

    it('filters the table to only "in" movements', function () {
        $product = Product::factory()->create();
        $inMovement = makeMovement($product, 'in');
        $outMovement = makeMovement($product, 'out');

        Livewire::test(InventoryReport::class)
            ->filterTable('type', 'in')
            ->assertCanSeeTableRecords([$inMovement])
            ->assertCanNotSeeTableRecords([$outMovement]);
    });

    it('filters the table to only "out" movements', function () {
        $product = Product::factory()->create();
        $inMovement = makeMovement($product, 'in');
        $outMovement = makeMovement($product, 'out');

        Livewire::test(InventoryReport::class)
            ->filterTable('type', 'out')
            ->assertCanSeeTableRecords([$outMovement])
            ->assertCanNotSeeTableRecords([$inMovement]);
    });
});

describe('InventoryReport — export action', function () {
    it('generates a CSV export via the report page action', function () {
        $product = Product::factory()->create();
        makeMovement($product, 'in');

        Livewire::test(InventoryReport::class)
            ->callAction('export', data: ['period' => 'this_month'])
            ->assertFileDownloaded();
    });
});
