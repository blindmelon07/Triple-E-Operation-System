<?php

use App\Exports\SalesReportExport;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lists the items sold for each sale, excluding voided lines', function () {
    $product = Product::factory()->create(['name' => 'Cement 40kg']);

    $sale = Sale::factory()->create([
        'date' => '2026-08-24',
        'total' => 500,
    ]);

    SaleItem::factory()->for($sale)->create([
        'product_id' => $product->id,
        'is_manual' => false,
        'quantity' => 2,
        'unit' => 'bag',
        'unit_price' => 200,
        'price' => 400,
    ]);

    SaleItem::factory()->for($sale)->create([
        'is_manual' => true,
        'product_description' => 'Custom Cut Plywood',
        'quantity' => 1,
        'unit' => 'pc',
        'unit_price' => 100,
        'price' => 100,
    ]);

    // Voided line should not appear in the export at all.
    SaleItem::factory()->for($sale)->create([
        'product_id' => $product->id,
        'is_manual' => false,
        'quantity' => 5,
        'unit' => 'bag',
        'price' => 1000,
        'is_voided' => true,
    ]);

    $export = new SalesReportExport(dateFrom: '2026-08-24', dateUntil: '2026-08-24');

    expect($export->getHeaders())
        ->toBe(['Date', 'Customer', 'Items Count', 'Items Sold', 'Total']);

    $row = $export->getData()->first();

    expect($row['Items Count'])->toBe(2)
        ->and($row['Items Sold'])->toBe('Cement 40kg x2, Custom Cut Plywood x1')
        ->and($row['Items Sold'])->not->toContain('x5')
        ->and($row['Total'])->toBe('500.00');
});

it('handles a sale with no items gracefully', function () {
    Sale::factory()->create([
        'date' => '2026-08-24',
        'total' => 0,
    ]);

    $export = new SalesReportExport(dateFrom: '2026-08-24', dateUntil: '2026-08-24');

    $row = $export->getData()->first();

    expect($row['Items Count'])->toBe(0)
        ->and($row['Items Sold'])->toBe('');
});

it('appends a grand total row summing all sales in range', function () {
    Sale::factory()->create(['date' => '2026-08-24', 'total' => 500]);
    Sale::factory()->create(['date' => '2026-08-24', 'total' => 250]);
    // Outside the filtered range — must not count toward the total.
    Sale::factory()->create(['date' => '2026-08-25', 'total' => 9999]);

    $export = new SalesReportExport(dateFrom: '2026-08-24', dateUntil: '2026-08-24');
    $rows = $export->getData();

    expect($rows)->toHaveCount(3); // 2 sales + grand total row

    $totalRow = $rows->last();

    expect($totalRow['Date'])->toBe('GRAND TOTAL')
        ->and($totalRow['Total'])->toBe('750.00');
});
