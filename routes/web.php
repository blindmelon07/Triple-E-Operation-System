<?php

use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\POSController;
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
});

Route::post('/tos/pos/complete-sale', [POSController::class, 'completeSale'])->name('filament.admin.pages.pos.complete-sale');
