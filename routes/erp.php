<?php

use App\Http\Controllers\Erp\AttachmentController;
use App\Http\Controllers\Erp\CategoryController;
use App\Http\Controllers\Erp\CustomerController;
use App\Http\Controllers\Erp\CustomerPaymentController;
use App\Http\Controllers\Erp\DashboardController;
use App\Http\Controllers\Erp\EtaController;
use App\Http\Controllers\Erp\InventoryController;
use App\Http\Controllers\Erp\ProductController;
use App\Http\Controllers\Erp\PurchaseInvoiceController;
use App\Http\Controllers\Erp\ReportController;
use App\Http\Controllers\Erp\SaleInvoiceController;
use App\Http\Controllers\Erp\SalesRepController;
use App\Http\Controllers\Erp\SupplierController;
use App\Http\Controllers\Erp\TreasuryController;
use App\Http\Controllers\Erp\UserController;
use App\Http\Controllers\Erp\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
 * مسارات النظام الأساسية — مش متداخلة مع {current_team} عشان الروابط تفضل
 * نضيفة (/sales بدل /team-slug/sales).
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('erp.dashboard');

    // التجار
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
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
    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::post('suppliers/{supplier}/payments', [SupplierController::class, 'storePayment'])->name('suppliers.payments.store');

    // المخزون
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/{product}', [InventoryController::class, 'show'])->name('inventory.show');

    // الأصناف — كتالوج البضاعة نفسه (الأسعار والوحدات وحد الطلب)
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    // إضافة سريعة من جوه فورم الفاتورة — صلاحية أوسع شوية من الكتالوج
    Route::post('products/quick', [ProductController::class, 'quickStore'])->name('products.quick-store');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // التصنيفات
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // المخازن — أول حاجة بتتسجّل في نظام فاضي
    Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

    // المرفقات (صور وPDF على الفواتير والسندات)
    Route::post('invoices/{invoice}/attachments', [AttachmentController::class, 'storeForInvoice'])->name('invoices.attachments.store');
    Route::post('payments/{payment}/attachments', [AttachmentController::class, 'storeForPayment'])->name('payments.attachments.store');
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // المندوبين
    Route::get('reps', [SalesRepController::class, 'index'])->name('reps.index');
    Route::post('reps', [SalesRepController::class, 'store'])->name('reps.store');
    Route::put('reps/{rep}', [SalesRepController::class, 'update'])->name('reps.update');
    Route::delete('reps/{rep}', [SalesRepController::class, 'destroy'])->name('reps.destroy');
    Route::get('reps/{rep}', [SalesRepController::class, 'show'])->name('reps.show');

    // المستخدمين والأدوار — المدير لوحده
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // الخزينة
    Route::get('treasury', [TreasuryController::class, 'index'])->name('treasury.index');

    // التقارير
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/print', [ReportController::class, 'print'])->name('reports.print');
});
