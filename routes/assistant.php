<?php

use App\Http\Controllers\Ai\AskController;
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
});
