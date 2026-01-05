<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Seeder;

class SaleItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sales = Sale::all();
        $products = Product::all();

        if ($sales->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach ($sales as $sale) {
            $count = rand(2, 5);
            for ($i = 0; $i < $count; $i++) {
                SaleItem::factory()
                    ->for($sale)
                    ->create([
                        'product_id' => $products->random()->id,
                    ]);
            }
        }
    }
}
