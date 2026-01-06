<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class TestAgingDataSeeder extends Seeder
{
    public function run(): void
    {
        // Update customers with payment terms
        Customer::query()->take(3)->get()->each(function ($customer, $index) {
            $terms = [30, 45, 60];
            $customer->update(['payment_term_days' => $terms[$index] ?? 30]);
        });

        // Update suppliers with payment terms
        Supplier::query()->take(2)->get()->each(function ($supplier, $index) {
            $terms = [30, 60];
            $supplier->update(['payment_term_days' => $terms[$index] ?? 30]);
        });

        // Create overdue sales (past due date)
        Sale::query()->take(3)->get()->each(function ($sale, $index) {
            $daysAgo = ($index + 1) * 15; // 15, 30, 45 days overdue
            $sale->update([
                'due_date' => now()->subDays($daysAgo),
                'payment_status' => 'unpaid',
                'amount_paid' => 0,
            ]);
        });

        // Create overdue purchases
        Purchase::query()->take(2)->get()->each(function ($purchase, $index) {
            $daysAgo = ($index + 1) * 20; // 20, 40 days overdue
            $purchase->update([
                'due_date' => now()->subDays($daysAgo),
                'payment_status' => 'unpaid',
                'amount_paid' => 0,
            ]);
        });

        // Create sales due soon (within 7 days)
        Sale::query()->skip(3)->take(2)->get()->each(function ($sale, $index) {
            $daysUntil = ($index + 1) * 2; // 2, 4 days from now
            $sale->update([
                'due_date' => now()->addDays($daysUntil),
                'payment_status' => 'unpaid',
                'amount_paid' => 0,
            ]);
        });

        // Create purchases due soon
        Purchase::query()->skip(2)->take(1)->get()->each(function ($purchase) {
            $purchase->update([
                'due_date' => now()->addDays(3),
                'payment_status' => 'unpaid',
                'amount_paid' => 0,
            ]);
        });
    }
}
