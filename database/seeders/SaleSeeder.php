<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            return;
        }

        Sale::factory(10)
            ->recycle($customers)
            ->create();
    }
}
