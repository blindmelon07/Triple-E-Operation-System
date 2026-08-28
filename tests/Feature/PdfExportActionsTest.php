<?php

use App\Filament\Pages\FinancialDashboard;
use App\Filament\Pages\ProfitLossReport;
use App\Filament\Pages\SalesReport;
use App\Filament\Pages\SupplierPriceComparisonReport;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierProductPrice;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Every one of these "Export PDF" buttons returns a Barryvdh\DomPDF
 * Response directly from a Filament/Livewire action closure. Livewire only
 * recognizes StreamedResponse/BinaryFileResponse as a file download (see
 * SupportFileDownloads) and base64-encodes it; a plain Response instead
 * falls through to Livewire's normal JSON 'returns' payload — and raw PDF
 * bytes are never valid UTF-8, so json_encode() throws "Malformed UTF-8
 * characters", turning the click into a 500 every time, regardless of what
 * data is in the report.
 *
 * These tests exercise the buttons exactly as the browser does — through
 * Livewire's callAction() — which a call straight to the service class
 * would NOT catch (the service method alone works fine).
 */
function grantAndActAs(string $permission): void
{
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    $user->givePermissionTo($permission);
    actingAs($user);
}

it('downloads the Sales Report PDF via its Livewire action', function () {
    grantAndActAs('View:SalesReport');

    Sale::factory()->create(['date' => now(), 'total' => 500]);

    Livewire::test(SalesReport::class)
        ->callAction('exportPdf', data: ['period' => 'this_month'])
        ->assertFileDownloaded();
});

it('downloads the Profit & Loss PDF via its Livewire action', function () {
    grantAndActAs('View:ProfitLossReport');

    Livewire::test(ProfitLossReport::class)
        ->callAction('export_pdf')
        ->assertFileDownloaded();
});

it('downloads the Financial Dashboard PDF via its Livewire action', function () {
    grantAndActAs('View:FinancialDashboard');

    Livewire::test(FinancialDashboard::class)
        ->callAction('export_pdf')
        ->assertFileDownloaded();
})->skip(
    fn () => config('database.default') === 'sqlite',
    'AccountingService::getMonthlyProfitTrend() uses raw MySQL DATE_FORMAT() '
    .'SQL, unsupported by the sqlite test DB — unrelated to the PDF-download '
    .'fix under test here. Production runs MySQL, where this works.'
);

it('downloads the Supplier Price Comparison PDF via its Livewire action', function () {
    grantAndActAs('View:SupplierPriceComparisonReport');

    $product = Product::factory()->create();
    $supplier = Supplier::factory()->create();
    SupplierProductPrice::factory()->create(['product_id' => $product->id, 'supplier_id' => $supplier->id]);

    Livewire::test(SupplierPriceComparisonReport::class)
        ->callAction('export_pdf', data: [])
        ->assertFileDownloaded();
});
