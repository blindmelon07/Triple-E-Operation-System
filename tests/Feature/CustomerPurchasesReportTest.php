<?php

use App\Filament\Pages\CustomerPurchasesReport;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Support\ReportBuilder\CustomerPurchasesReportService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'View:CustomerPurchasesReport', 'guard_name' => 'web']);
    $user->givePermissionTo('View:CustomerPurchasesReport');
    actingAs($user);

    $this->juan = Customer::factory()->create(['name' => 'Juan Dela Cruz']);
    $other = Customer::factory()->create(['name' => 'Maria Clara']);
    $product = Product::factory()->create(['name' => 'Cement 40kg']);

    $sale = fn (Customer $c, string $date, array $attrs = []) => Sale::factory()->create(array_merge([
        'customer_id' => $c->id,
        'date' => $date,
        'reference_number' => 'SI-'.$date.'-'.uniqid(),
        'is_voided' => false,
    ], $attrs));

    $item = fn (Sale $s, string $desc, float $qty, float $unitPrice, float $price, array $attrs = []) => SaleItem::create(array_merge([
        'sale_id' => $s->id,
        'is_manual' => true,
        'product_description' => $desc,
        'unit' => 'piece',
        'quantity' => $qty,
        'unit_price' => $unitPrice,
        'price' => $price,
    ], $attrs));

    // Oct 5: two sales on the same day for Juan
    $a = $sale($this->juan, '2026-10-05');
    $item($a, 'Hollow Blocks', 100, 15, 1500);
    $item($a, 'Rebar 10mm', 10, 200, 1900); // 100 discount
    $b = $sale($this->juan, '2026-10-05');
    $item($b, 'Cement 40kg', 5, 280, 1400, ['is_manual' => false, 'product_id' => $product->id, 'product_description' => null, 'unit' => 'bag']);
    $item($b, 'Voided line', 1, 999, 999, ['is_voided' => true]);

    // Oct 20
    $c = $sale($this->juan, '2026-10-20');
    $item($c, 'Sand', 2, 1200, 2400);

    // Excluded: voided sale, other customer, out of range
    $item($sale($this->juan, '2026-10-21', ['is_voided' => true]), 'Voided sale item', 1, 500, 500);
    $item($sale($other, '2026-10-06'), 'Gravel', 1, 800, 800);
    $item($sale($this->juan, '2026-11-02'), 'Paint', 1, 650, 650);
});

it('itemizes a customer\'s purchases grouped by date', function () {
    $result = (new CustomerPurchasesReportService)->build($this->juan->id, '2026-10-01', '2026-10-31');

    expect(collect($result['groups'])->pluck('date')->all())->toBe(['Oct 05, 2026', 'Oct 20, 2026'])
        ->and(collect($result['groups'][0]['items'])->pluck('product')->all())->toBe(['Hollow Blocks', 'Rebar 10mm', 'Cement 40kg'])
        ->and($result['groups'][0]['items'][1]['discount'])->toBe(100.0)
        ->and($result['groups'][0]['items'][2]['unit'])->toBe('Bag')
        ->and($result['groups'][0]['subtotal'])->toBe(4800.0)
        ->and($result['groups'][0]['sales_count'])->toBe(2)
        ->and($result['groups'][1]['subtotal'])->toBe(2400.0)
        ->and($result['totals']['amount'])->toBe(7200.0)
        ->and($result['totals']['sales'])->toBe(3)
        ->and($result['totals']['items'])->toBe(4);
});

it('includes all dates when no range is given', function () {
    $result = (new CustomerPurchasesReportService)->build($this->juan->id);

    expect(collect($result['groups'])->pluck('date')->all())->toBe(['Oct 05, 2026', 'Oct 20, 2026', 'Nov 02, 2026'])
        ->and($result['totals']['amount'])->toBe(7850.0);
});

it('renders the report page and downloads CSV and PDF', function () {
    Livewire::test(CustomerPurchasesReport::class)
        ->assertOk()
        ->set('customerId', $this->juan->id)
        ->set('dateFrom', '2026-10-01')
        ->set('dateTo', '2026-10-31')
        ->call('generate')
        ->assertSee('Hollow Blocks')
        ->assertDontSee('Gravel')
        ->assertDontSee('Voided line')
        ->assertSee('7,200.00')
        ->callAction('exportCsv')
        ->assertFileDownloaded()
        ->callAction('exportPdf')
        ->assertFileDownloaded();
});

it('requires a customer before generating', function () {
    Livewire::test(CustomerPurchasesReport::class)
        ->call('generate')
        ->assertNotified('Please select a customer.')
        ->assertSet('generated', false);
});
