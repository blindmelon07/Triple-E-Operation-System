<?php

namespace Database\Seeders;

use App\Enums\ProductUnit;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PaintSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create paint category
        $category = Category::firstOrCreate(
            ['name' => 'Paint'],
            ['description' => 'Paint and coatings']
        );

        // Get first supplier or create a default one
        $supplier = Supplier::first();
        if (! $supplier) {
            $supplier = Supplier::create([
                'name' => 'Default Supplier',
                'contact_person' => 'Contact',
                'email' => 'supplier@example.com',
                'phone' => '0000000000',
            ]);
        }

        // Create paint products
        $paints = [
            [
                'name' => 'Acrylic Paint - White',
                'description' => 'High quality white acrylic paint',
                'price' => 250.00,
                'quantity' => 100,
                'unit' => ProductUnit::Liter,
            ],
            [
                'name' => 'Acrylic Paint - Red',
                'description' => 'High quality red acrylic paint',
                'price' => 250.00,
                'quantity' => 75,
                'unit' => ProductUnit::Liter,
            ],
            [
                'name' => 'Enamel Paint - Industrial',
                'description' => 'Durable industrial enamel paint',
                'price' => 300.00,
                'quantity' => 50,
                'unit' => ProductUnit::Liter,
            ],
            [
                'name' => 'Latex Paint - Premium',
                'description' => 'Premium latex paint for walls',
                'price' => 280.00,
                'quantity' => 60,
                'unit' => ProductUnit::Liter,
            ],
        ];

        foreach ($paints as $paintData) {
            $product = Product::firstOrCreate(
                ['name' => $paintData['name']],
                [
                    'description' => $paintData['description'],
                    'category_id' => $category->id,
                    'supplier_id' => $supplier->id,
                    'price' => $paintData['price'],
                    'quantity' => $paintData['quantity'],
                    'unit' => $paintData['unit'],
                ]
            );

            // Create or update inventory
            $product->inventory()->updateOrCreate(
                ['product_id' => $product->id],
                ['quantity' => $paintData['quantity']]
            );
        }
    }
}
