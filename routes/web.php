<?php

use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\InvoiceSettlementController;
use App\Http\Controllers\POSController;
use App\Services\ReportExportService;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware(['auth'])->group(function () {
    Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
    Route::post('/pos/complete-sale', [POSController::class, 'completeSale'])->name('pos.complete-sale');
    Route::post('/pos/customer', [POSController::class, 'storeCustomer'])->name('pos.store-customer');
    Route::post('/pos/quotation', [POSController::class, 'createQuotation'])->name('pos.create-quotation');
    Route::get('/pos/quotation/{quotation}/print', [POSController::class, 'printQuotation'])->name('pos.print-quotation');
    Route::get('/pos/print-receipt/{sale}', [POSController::class, 'printReceipt'])->name('pos.print-receipt');
    Route::get('/pos/recent-sales', [POSController::class, 'getRecentSales'])->name('pos.recent-sales');
    Route::post('/pos/void-request/{sale}', [POSController::class, 'requestVoid'])->name('pos.void-request');

    // Void Request approval flow
    Route::get('/pos/void-requests/pending', [\App\Http\Controllers\VoidRequestController::class, 'pending'])->name('pos.void-requests.pending');
    Route::get('/pos/void-requests/pending-count', [\App\Http\Controllers\VoidRequestController::class, 'pendingCount'])->name('pos.void-requests.pending-count');
    Route::get('/pos/void-requests/{voidRequest}/status', [\App\Http\Controllers\VoidRequestController::class, 'status'])->name('pos.void-requests.status');
    Route::post('/pos/void-requests/{voidRequest}/approve', [\App\Http\Controllers\VoidRequestController::class, 'approve'])->name('pos.void-requests.approve');
    Route::post('/pos/void-requests/{voidRequest}/reject', [\App\Http\Controllers\VoidRequestController::class, 'reject'])->name('pos.void-requests.reject');
    Route::post('/pos/void-requests/{voidRequest}/cancel', [\App\Http\Controllers\VoidRequestController::class, 'cancel'])->name('pos.void-requests.cancel');

    // Settle Outstanding Invoice
    Route::get('/pos/customers/{customer}/outstanding-invoices', [InvoiceSettlementController::class, 'outstandingInvoices'])->name('pos.outstanding-invoices');
    Route::post('/pos/settle-invoices', [InvoiceSettlementController::class, 'settle'])->name('pos.settle-invoices');
    Route::get('/pos/print-payment-receipt/{payment}', [InvoiceSettlementController::class, 'printPaymentReceipt'])->name('pos.print-payment-receipt');

    // Cash Register
    Route::post('/pos/register/open', [POSController::class, 'openRegister'])->name('pos.register.open');
    Route::post('/pos/register/close', [POSController::class, 'closeRegister'])->name('pos.register.close');
    Route::get('/pos/register/status', [POSController::class, 'registerStatus'])->name('pos.register.status');
    Route::get('/pos/register/{session}/sales-report', [POSController::class, 'registerSalesReport'])->name('pos.register.sales-report');
    Route::get('/pos/register/{session}/daily-report', [POSController::class, 'dailyTransactionReport'])->name('pos.register.daily-report');
    Route::get('/pos/reports/period', [POSController::class, 'periodTransactionReport'])->name('pos.reports.period');

    // Delivery Receipt
    Route::get('/delivery/{delivery}/print', [DeliveryController::class, 'printReceipt'])->name('delivery.print-receipt');

    // CSRF token refresh for long-running POS sessions
    Route::get('/pos/csrf-token', fn () => response()->json(['token' => csrf_token()]))->name('pos.csrf-token');

    // Aging Report exports
    Route::get('/reports/aging/export-excel', fn (\Illuminate\Http\Request $request) => (new ReportExportService)->exportAgingExcel(
        $request->integer('customer_id') ?: null,
        $request->integer('supplier_id') ?: null,
    ))->name('aging-report.export-excel');
    Route::get('/reports/aging/export-pdf', fn (\Illuminate\Http\Request $request) => (new ReportExportService)->exportAgingPdf(
        $request->integer('customer_id') ?: null,
        $request->integer('supplier_id') ?: null,
    ))->name('aging-report.export-pdf');

    // Customer Statement of Account
    Route::get('/reports/customers/{customer}/statement', function (\App\Models\Customer $customer, \Illuminate\Http\Request $request) {
        abort_unless(auth()->user()->can('view', $customer), 403);

        return (new ReportExportService)->exportCustomerStatementPdf(
            $customer,
            $request->query('date_from'),
            $request->query('date_to'),
        );
    })->name('customer-statement.export-pdf');
});

Route::middleware(['auth'])->post('/tos/pos/complete-sale', [POSController::class, 'completeSale'])->name('filament.admin.pages.pos.complete-sale');
