<?php

use App\Enums\ProductUnit;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImportService;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Product Import', function () {
    it('can import products from csv file', function () {
        actingAs($this->user);

        $csvContent = "Name,Category,Price,Stock,Unit\n";
        $csvContent .= "Product One,Electronics,150.00,25,piece\n";
        $csvContent .= "Product Two,Groceries,50.00,100,kilo\n";
        $csvContent .= "Product Three,Electronics,200.00,10,box\n";

        $fileName = 'test_products_'.uniqid().'.csv';
        $storagePath = storage_path('app/public/'.$fileName);
        file_put_contents($storagePath, $csvContent);

        $service = new ProductImportService;
        $result = $service->importFromCsv($fileName);

        expect($result['success'])->toBeTrue()
            ->and($result['imported'])->toBe(3)
            ->and(Product::count())->toBe(3)
            ->and(Category::count())->toBe(2)
            ->and(Inventory::count())->toBe(3);

        $this->assertDatabaseHas('products', [
            'name' => 'Product One',
            'price' => 150.00,
            'quantity' => 25,
            'unit' => ProductUnit::Piece->value,
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Product Two',
            'price' => 50.00,
            'quantity' => 100,
            'unit' => ProductUnit::Kilo->value,
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Product Three',
            'price' => 200.00,
            'quantity' => 10,
            'unit' => ProductUnit::Box->value,
        ]);

        $this->assertDatabaseHas('categories', ['name' => 'Electronics']);
        $this->assertDatabaseHas('categories', ['name' => 'Groceries']);
    });

    it('creates inventory records for imported products', function () {
        actingAs($this->user);

        $csvContent = "Name,Category,Price,Stock,Unit\n";
        $csvContent .= "Test Product,Test Category,100.00,50,piece\n";

        $fileName = 'test_inventory_'.uniqid().'.csv';
        $storagePath = storage_path('app/public/'.$fileName);
        file_put_contents($storagePath, $csvContent);

        $service = new ProductImportService;
        $result = $service->importFromCsv($fileName);

        expect($result['success'])->toBeTrue();

        $product = Product::where('name', 'Test Product')->first();

        expect($product)->not->toBeNull();

        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'quantity' => 50,
        ]);
    });

    it('uses existing category if already exists', function () {
        actingAs($this->user);

        $existingCategory = Category::factory()->create(['name' => 'Existing Category']);

        $csvContent = "Name,Category,Price,Stock,Unit\n";
        $csvContent .= "New Product,Existing Category,100.00,10,piece\n";

        $fileName = 'test_category_'.uniqid().'.csv';
        $storagePath = storage_path('app/public/'.$fileName);
        file_put_contents($storagePath, $csvContent);

        $service = new ProductImportService;
        $result = $service->importFromCsv($fileName);

        expect($result['success'])->toBeTrue()
            ->and(Category::count())->toBe(1);

        $product = Product::where('name', 'New Product')->first();
        expect($product->category_id)->toBe($existingCategory->id);
    });

    it('defaults to piece unit when invalid unit provided', function () {
        actingAs($this->user);

        $csvContent = "Name,Category,Price,Stock,Unit\n";
        $csvContent .= "Product With Invalid Unit,Test,100.00,10,invalid_unit\n";

        $fileName = 'test_unit_'.uniqid().'.csv';
        $storagePath = storage_path('app/public/'.$fileName);
        file_put_contents($storagePath, $csvContent);

        $service = new ProductImportService;
        $result = $service->importFromCsv($fileName);

        expect($result['success'])->toBeTrue();

        $product = Product::where('name', 'Product With Invalid Unit')->first();
        expect($product->unit)->toBe(ProductUnit::Piece);
    });

    it('returns error when file not found', function () {
        $service = new ProductImportService;
        $result = $service->importFromCsv('nonexistent_file.csv');

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toBe('File not found.');
    });

    it('can download csv template', function () {
        actingAs($this->user);

        Livewire::test(ListProducts::class)
            ->callAction('downloadTemplate')
            ->assertFileDownloaded('products_import_template.csv');
    });
});
