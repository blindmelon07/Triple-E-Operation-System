<?php

use App\Exports\SupplierPriceComparisonExport;
use App\Filament\Pages\SupplierPriceComparisonReport;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProductPrice;
use App\Models\User;
use App\Services\ReportExportService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'View:SupplierPriceComparisonReport', 'guard_name' => 'web']);
    $this->user->givePermissionTo('View:SupplierPriceComparisonReport');
    actingAs($this->user);
});

describe('SupplierPriceComparisonExport', function () {
    it('builds one row per priced product with a column per priced supplier', function () {
        $product = Product::factory()->create(['name' => 'ANGLE BAR 3MM x 1 (1/4)']);
        $unpricedProduct = Product::factory()->create(['name' => 'Unpriced Product']);
        $jbj = Supplier::factory()->create(['name' => 'JBJ']);
        $casini = Supplier::factory()->create(['name' => 'Casini']);
        $unquotedSupplier = Supplier::factory()->create(['name' => 'Never Quoted']);

        SupplierProductPrice::factory()->create(['product_id' => $product->id, 'supplier_id' => $jbj->id, 'base_price' => 320]);
        SupplierProductPrice::factory()->create(['product_id' => $product->id, 'supplier_id' => $casini->id, 'base_price' => 332]);

        $export = new SupplierPriceComparisonExport;

        expect($export->getHeaders())->toBe(['Product', 'Category', 'Casini', 'JBJ'])
            ->and($export->getSuppliers()->pluck('name')->all())->toBe(['Casini', 'JBJ']);

        $data = $export->getData();

        expect($data)->toHaveCount(1); // the unpriced product is excluded
        expect($data->first())->toBe([
            'Product' => 'ANGLE BAR 3MM x 1 (1/4)',
            'Category' => $product->category->name,
            'Casini' => '332.00',
            'JBJ' => '320.00',
        ]);

        $unpricedProductName = $unpricedProduct->name;
        expect($data->pluck('Product'))->not->toContain($unpricedProductName);
        expect($export->getSuppliers()->pluck('name'))->not->toContain('Never Quoted');
    });

    it('filters by category when given', function () {
        $steel = Category::factory()->create(['name' => 'Steel']);
        $paint = Category::factory()->create(['name' => 'Paint']);
        $supplier = Supplier::factory()->create();

        $steelProduct = Product::factory()->create(['category_id' => $steel->id]);
        $paintProduct = Product::factory()->create(['category_id' => $paint->id]);

        SupplierProductPrice::factory()->create(['product_id' => $steelProduct->id, 'supplier_id' => $supplier->id]);
        SupplierProductPrice::factory()->create(['product_id' => $paintProduct->id, 'supplier_id' => $supplier->id]);

        $export = new SupplierPriceComparisonExport($steel->id);

        expect($export->getData())->toHaveCount(1)
            ->and($export->getData()->first()['Product'])->toBe($steelProduct->name);
    });
});

describe('SupplierPriceComparisonReport — exports', function () {
    it('generates a CSV export via the report page action', function () {
        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create(['name' => 'JBJ']);
        SupplierProductPrice::factory()->create(['product_id' => $product->id, 'supplier_id' => $supplier->id, 'base_price' => 150]);

        Livewire::test(SupplierPriceComparisonReport::class)
            ->callAction('export_csv', data: [])
            ->assertFileDownloaded();
    });

    it('generates a PDF export via the service', function () {
        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();
        SupplierProductPrice::factory()->create(['product_id' => $product->id, 'supplier_id' => $supplier->id, 'base_price' => 150]);

        $response = (new ReportExportService)->exportSupplierPriceComparisonPdf();

        expect($response->headers->get('content-type'))->toBe('application/pdf');
    });
});

describe('SupplierPriceComparisonReport — table', function () {
    it('shows a column per priced supplier with the right price, and a dash when unpriced', function () {
        $product = Product::factory()->create();
        $jbj = Supplier::factory()->create(['name' => 'JBJ']);
        $casini = Supplier::factory()->create(['name' => 'Casini']);

        // Only JBJ has quoted a price for this product.
        SupplierProductPrice::factory()->create(['product_id' => $product->id, 'supplier_id' => $jbj->id, 'base_price' => 320]);
        // Casini has quoted a price, but for a different product, so it still shows as a column.
        $otherProduct = Product::factory()->create();
        SupplierProductPrice::factory()->create(['product_id' => $otherProduct->id, 'supplier_id' => $casini->id, 'base_price' => 999]);

        Livewire::test(SupplierPriceComparisonReport::class)
            ->assertSee('₱320.00')
            ->assertSee('—');
    });

    it('excludes products with no recorded supplier price from the table', function () {
        $priced = Product::factory()->create(['name' => 'Priced Product']);
        $unpriced = Product::factory()->create(['name' => 'Unpriced Product']);
        $supplier = Supplier::factory()->create();
        SupplierProductPrice::factory()->create(['product_id' => $priced->id, 'supplier_id' => $supplier->id]);

        Livewire::test(SupplierPriceComparisonReport::class)
            ->assertSee('Priced Product')
            ->assertDontSee('Unpriced Product');
    });
});
