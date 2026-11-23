<?php

use App\Http\Controllers\Ai\AskController;
use App\Http\Controllers\Ai\ExportAgentTableController;
use App\Http\Controllers\Ai\ScanInvoiceController;
use App\Http\Controllers\Ai\WhatsAppWebhookController;
use App\Http\Controllers\Erp\WhatsAppNumberController;
use Illuminate\Support\Facades\Route;

/*
 * مسارات المساعد الذكي.
 *
 * مش في `routes/ai.php` عن قصد — الملف ده بيحمّله laravel/mcp من غير
 * أي middleware group، وسيرفر الـ MCP بيستخدم توكن Passport مش جلسة.
 * المساعد هنا بيشتغل جوه الواجهة بجلسة المستخدم العادية، فمحله الـ
 * web stack.
 *
 * كل المسارات ورا `auth` وورا حد استخدام لكل مستخدم.
 */
Route::middleware(['auth', 'verified'])->prefix('ai')->name('ai.')->group(function () {
    // «اسأل السيستم»
    Route::middleware('throttle:ai-chat')->group(function () {
        Route::get('chat', [AskController::class, 'index'])->name('chat.index');
        Route::post('chat', [AskController::class, 'store'])->name('chat.store');
        Route::delete('chat', [AskController::class, 'destroy'])->name('chat.destroy');
    });

    // تحميل جدول من رد وكيل — PDF أو إكسيل
    Route::post('export-table', ExportAgentTableController::class)->name('export-table');

    // «مسح فاتورة» — بيقرا الصورة ويعمل مسودة
    Route::post('scan-invoice', ScanInvoiceController::class)
        ->middleware('throttle:ai-scan')
        ->name('scan-invoice');
});

/*
 * ويبهوك واتساب — بره `auth` لأن Meta هي اللي بتنادي، مش متصفّح.
 *
 * الحماية مش جلسة: الرقم لازم يكون مربوط بحساب مستخدم ومتأكّد منه،
 * وده بيتفحص جوه الكنترولر. أي رقم تاني مابياخدش رد خالص.
 */
Route::prefix('ai/whatsapp')->name('ai.whatsapp.')->group(function () {
    Route::get('webhook', [WhatsAppWebhookController::class, 'verify'])->name('verify');
    Route::post('webhook', [WhatsAppWebhookController::class, 'handle'])->name('webhook');
});

/* ربط رقم واتساب بالحساب — ده جوه النظام فمحتاج تسجيل دخول */
Route::middleware(['auth', 'verified'])->prefix('settings/whatsapp')->name('whatsapp.')->group(function () {
    Route::post('/', [WhatsAppNumberController::class, 'store'])->name('store');
    Route::post('verify', [WhatsAppNumberController::class, 'verify'])->name('verify');
    Route::delete('/', [WhatsAppNumberController::class, 'destroy'])->name('destroy');
});
