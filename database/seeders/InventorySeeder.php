<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = \App\Models\Product::doesntHave('inventory')->get();
        
        foreach ($products as $product) {
            \App\Models\Inventory::create([
                'product_id' => $product->id,
                'quantity' => rand(50, 500),
            ]);
        }

        $this->command->info('Created inventory for ' . $products->count() . ' products');
    }
}
