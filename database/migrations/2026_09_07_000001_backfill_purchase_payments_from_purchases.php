<?php

use App\Models\Purchase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Purchases recorded a payment before purchase_payments existed by just
     * overwriting amount_paid/paid_date on the purchase itself, with no history
     * of individual installments. This collapses that into a single backfilled
     * payment row per purchase so the amount is at least reflected in the new
     * statement-of-account reports — any prior multi-installment detail (which
     * months a partial payment actually happened in) is not recoverable from
     * the old data and is lost.
     */
    public function up(): void
    {
        Purchase::query()
            ->where('amount_paid', '>', 0)
            ->whereDoesntHave('purchasePayments')
            ->each(function (Purchase $purchase) {
                DB::table('purchase_payments')->insert([
                    'purchase_id' => $purchase->id,
                    'amount' => $purchase->amount_paid,
                    'payment_method' => 'cash',
                    'reference_number' => null,
                    'paid_date' => $purchase->paid_date ?? $purchase->date,
                    'recorded_by_id' => null,
                    'balance_after' => $purchase->balance,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Backfilled rows are indistinguishable from real ones once created;
        // nothing to safely reverse.
    }
};
