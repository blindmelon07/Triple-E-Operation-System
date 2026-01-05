<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Database\Seeder;

class InventoryMovementSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            return;
        }

        foreach ($products->take(5) as $product) {
            // Add some "in" movements
            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => rand(10, 50),
                'reason' => 'Purchase Order',
                'reference_type' => 'App\Models\Purchase',
                'notes' => 'Stock received from supplier',
                'created_at' => now()->subDays(rand(1, 30)),
            ]);

            // Add some "out" movements
            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => rand(5, 20),
                'reason' => 'Sale',
                'reference_type' => 'App\Models\Sale',
                'notes' => 'Sold via POS',
                'created_at' => now()->subDays(rand(1, 15)),
            ]);

            // Add adjustment
            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => rand(1, 10),
                'reason' => 'Stock Adjustment',
                'notes' => 'Physical count adjustment',
                'created_at' => now()->subDays(rand(1, 10)),
            ]);
        }
    }
}
