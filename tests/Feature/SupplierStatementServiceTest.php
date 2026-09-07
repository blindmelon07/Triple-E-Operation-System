<?php

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Support\ReportBuilder\SupplierStatementService;

describe('SupplierStatementService', function () {
    it('computes opening/closing balances across months with mixed purchases and payments', function () {
        $supplier = Supplier::factory()->create();

        $jan = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'date' => '2026-01-10',
            'total' => 1000,
            'amount_paid' => 400,
            'payment_status' => 'partial',
        ]);
        PurchasePayment::create([
            'purchase_id' => $jan->id,
            'amount' => 400,
            'payment_method' => 'cash',
            'paid_date' => '2026-01-15',
            'balance_after' => 600,
        ]);

        $feb = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'date' => '2026-02-05',
            'total' => 500,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
        ]);
        // Pay off the rest of January's purchase in February.
        PurchasePayment::create([
            'purchase_id' => $jan->id,
            'amount' => 600,
            'payment_method' => 'bank',
            'paid_date' => '2026-02-20',
            'balance_after' => 0,
        ]);

        $statement = (new SupplierStatementService($supplier->id))->build();

        expect($statement['months'])->toHaveCount(2);

        $january = $statement['months'][0];
        expect($january['month'])->toBe('2026-01')
            ->and($january['opening_balance'])->toBe(0.0)
            ->and($january['purchases_total'])->toBe(1000.0)
            ->and($january['payments_total'])->toBe(400.0)
            ->and($january['closing_balance'])->toBe(600.0);

        $february = $statement['months'][1];
        expect($february['month'])->toBe('2026-02')
            ->and($february['opening_balance'])->toBe(600.0)
            ->and($february['purchases_total'])->toBe(500.0)
            ->and($february['payments_total'])->toBe(600.0)
            ->and($february['closing_balance'])->toBe(500.0);
    });

    it('carries the correct opening balance into a date-filtered window', function () {
        $supplier = Supplier::factory()->create();

        Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'date' => '2026-01-10',
            'total' => 1000,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
        ]);

        Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'date' => '2026-02-05',
            'total' => 200,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
        ]);

        // Only ask for February onward — January still needs to count toward
        // February's opening balance even though it's outside the window.
        $statement = (new SupplierStatementService($supplier->id))->build('2026-02-01', '2026-02-28');

        expect($statement['months'])->toHaveCount(1);

        $february = $statement['months'][0];
        expect($february['opening_balance'])->toBe(1000.0)
            ->and($february['closing_balance'])->toBe(1200.0);
    });
});
