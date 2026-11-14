<?php

use App\Http\Controllers\Erp\CustomerController;
use App\Http\Controllers\Erp\CustomerPaymentController;
use App\Http\Controllers\Erp\DashboardController;
use App\Http\Controllers\Erp\EtaController;
use App\Http\Controllers\Erp\InventoryController;
use App\Http\Controllers\Erp\PurchaseInvoiceController;
use App\Http\Controllers\Erp\ReportController;
use App\Http\Controllers\Erp\SaleInvoiceController;
use App\Http\Controllers\Erp\SupplierController;
use App\Http\Controllers\Erp\TreasuryController;
use Illuminate\Support\Facades\Route;

/*
 * مسارات النظام الأساسية — مش متداخلة مع {current_team} عشان الروابط تفضل
 * نضيفة (/sales بدل /team-slug/sales).
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('erp.dashboard');

    // التجار
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');
    Route::post('customers/{customer}/payments', [CustomerPaymentController::class, 'store'])->name('customers.payments.store');

    // المبيعات
    Route::get('sales', [SaleInvoiceController::class, 'index'])->name('sales.index');
    Route::get('sales/create', [SaleInvoiceController::class, 'create'])->name('sales.create');
    Route::post('sales', [SaleInvoiceController::class, 'store'])->name('sales.store');
    Route::get('sales/{sale}', [SaleInvoiceController::class, 'show'])->name('sales.show');
    Route::get('sales/{sale}/print', [SaleInvoiceController::class, 'print'])->name('sales.print');
    Route::post('sales/{sale}/eta', [EtaController::class, 'store'])->name('sales.eta.store');

    // المشتريات
    Route::get('purchases', [PurchaseInvoiceController::class, 'index'])->name('purchases.index');
    Route::get('purchases/create', [PurchaseInvoiceController::class, 'create'])->name('purchases.create');
    Route::post('purchases', [PurchaseInvoiceController::class, 'store'])->name('purchases.store');
    Route::get('purchases/{purchase}', [PurchaseInvoiceController::class, 'show'])->name('purchases.show');

    // الموردون
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::post('suppliers/{supplier}/payments', [SupplierController::class, 'storePayment'])->name('suppliers.payments.store');

    // المخزون
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/{product}', [InventoryController::class, 'show'])->name('inventory.show');

    // الخزينة
    Route::get('treasury', [TreasuryController::class, 'index'])->name('treasury.index');

    // التقارير
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/print', [ReportController::class, 'print'])->name('reports.print');
});
