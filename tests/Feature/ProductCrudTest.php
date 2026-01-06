<?php

use App\Enums\ProductUnit;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Product CRUD Operations', function () {
    it('can create a product', function () {
        actingAs($this->user);

        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();

        $productData = [
            'name' => 'Test Product',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'price' => 100.50,
            'quantity' => 50,
            'unit' => ProductUnit::Piece,
        ];

        $product = Product::create($productData);

        expect($product)->toBeInstanceOf(Product::class)
            ->and($product->name)->toBe('Test Product')
            ->and((float) $product->price)->toBe(100.50)
            ->and($product->quantity)->toBe(50)
            ->and($product->unit)->toBe(ProductUnit::Piece);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'category_id' => $category->id,
        ]);
    });

    it('can read a product', function () {
        actingAs($this->user);

        $product = Product::factory()->create([
            'name' => 'Read Test Product',
        ]);

        $foundProduct = Product::find($product->id);

        expect($foundProduct)->not->toBeNull()
            ->and($foundProduct->name)->toBe('Read Test Product');
    });

    it('can read all products', function () {
        actingAs($this->user);

        Product::factory()->count(10)->create();

        $products = Product::all();

        expect($products)->toHaveCount(10);
    });

    it('can update a product', function () {
        actingAs($this->user);

        $product = Product::factory()->create([
            'name' => 'Original Product',
            'price' => 50.00,
        ]);

        $product->update([
            'name' => 'Updated Product',
            'price' => 75.00,
        ]);

        $fresh = $product->fresh();
        expect($fresh->name)->toBe('Updated Product')
            ->and((float) $fresh->price)->toBe(75.00);
    });

    it('can delete a product', function () {
        actingAs($this->user);

        $product = Product::factory()->create();
        $productId = $product->id;

        $product->delete();

        $this->assertDatabaseMissing('products', ['id' => $productId]);
    });

    it('belongs to a category', function () {
        actingAs($this->user);

        $category = Category::factory()->create(['name' => 'Test Category']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        expect($product->category)->toBeInstanceOf(Category::class)
            ->and($product->category->name)->toBe('Test Category');
    });

    it('belongs to a supplier', function () {
        actingAs($this->user);

        $supplier = Supplier::factory()->create(['name' => 'Test Supplier']);
        $product = Product::factory()->create(['supplier_id' => $supplier->id]);

        expect($product->supplier)->toBeInstanceOf(Supplier::class)
            ->and($product->supplier->name)->toBe('Test Supplier');
    });

    it('casts unit to ProductUnit enum', function () {
        actingAs($this->user);

        $product = Product::factory()->create(['unit' => ProductUnit::Kilo]);

        expect($product->unit)->toBeInstanceOf(ProductUnit::class)
            ->and($product->unit)->toBe(ProductUnit::Kilo);
    });
});
