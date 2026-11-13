<?php

use App\Http\Controllers\Erp\ComingSoonController;
use Illuminate\Support\Facades\Route;

/*
 * مسارات النظام الأساسية — مش متداخلة مع {current_team} عشان الروابط تفضل
 * نضيفة (/sales بدل /team-slug/sales). كل قسم دلوقتي بيرجع شاشة "قريبًا"
 * ولما شاشته تتبني بيتبدّل الـ controller بتاعه بس.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', ComingSoonController::class)->name('erp.dashboard');
    Route::get('sales', ComingSoonController::class)->name('sales.index');
    Route::get('purchases', ComingSoonController::class)->name('purchases.index');
    Route::get('inventory', ComingSoonController::class)->name('inventory.index');
    Route::get('customers', ComingSoonController::class)->name('customers.index');
    Route::get('suppliers', ComingSoonController::class)->name('suppliers.index');
    Route::get('treasury', ComingSoonController::class)->name('treasury.index');
    Route::get('reports', ComingSoonController::class)->name('reports.index');
});
