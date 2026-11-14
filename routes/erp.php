<?php

use App\Http\Controllers\Erp\ComingSoonController;
use App\Http\Controllers\Erp\CustomerController;
use App\Http\Controllers\Erp\CustomerPaymentController;
use App\Http\Controllers\Erp\DashboardController;
use App\Http\Controllers\Erp\EtaController;
use App\Http\Controllers\Erp\SaleInvoiceController;
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

    // أقسام لسه تحت التنفيذ
    Route::get('purchases', ComingSoonController::class)->name('purchases.index');
    Route::get('inventory', ComingSoonController::class)->name('inventory.index');
    Route::get('suppliers', ComingSoonController::class)->name('suppliers.index');
    Route::get('treasury', ComingSoonController::class)->name('treasury.index');
    Route::get('reports', ComingSoonController::class)->name('reports.index');
});
