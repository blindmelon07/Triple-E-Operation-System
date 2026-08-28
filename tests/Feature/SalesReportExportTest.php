<?php

use App\Exports\SalesReportExport;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ReportExportService;

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

it('exports the sales report as a downloadable PDF', function () {
    $product = Product::factory()->create(['name' => 'Cement 40kg']);

    $sale = Sale::factory()->create(['date' => '2026-08-24', 'total' => 500]);
    SaleItem::factory()->for($sale)->create([
        'product_id' => $product->id,
        'is_manual' => false,
        'quantity' => 2,
        'unit' => 'bag',
        'price' => 500,
    ]);

    $response = (new ReportExportService)->exportSalesReportPdf(
        period: null,
        dateFrom: '2026-08-24',
        dateUntil: '2026-08-24',
    );

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Disposition'))->toContain('sales-report');
});

it('sanitizes malformed UTF-8 in names so the export payload stays JSON-encodable', function () {
    // A lone 0xE9 byte is valid Latin-1 ("é") but invalid UTF-8 — the kind of
    // legacy/pasted-in byte that used to make json_encode() (and Livewire's
    // response for the export action) blow up with "Malformed UTF-8 characters".
    $badBytes = "Caf\xE9 Chairs";

    $product = Product::factory()->create(['name' => $badBytes]);
    $customer = \App\Models\Customer::factory()->create(['name' => $badBytes]);

    $sale = Sale::factory()->create([
        'customer_id' => $customer->id,
        'date' => '2026-08-24',
        'total' => 500,
    ]);

    SaleItem::factory()->for($sale)->create([
        'product_id' => $product->id,
        'is_manual' => false,
        'quantity' => 1,
        'unit' => 'pc',
        'price' => 500,
    ]);

    $export = new SalesReportExport(dateFrom: '2026-08-24', dateUntil: '2026-08-24');
    $row = $export->getData()->first();

    expect(mb_check_encoding($row['Customer'], 'UTF-8'))->toBeTrue()
        ->and(mb_check_encoding($row['Items Sold'], 'UTF-8'))->toBeTrue();

    // This is the exact call that threw InvalidArgumentException in production.
    expect(json_encode($export->getData()->all()))->not->toBeFalse();
});
