<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

// مفيش صفحة هبوط عامة — النظام داخلي، فالجذر بيودّي على لوحة التحكم
// (والضيف بيتحوّل تلقائياً لتسجيل الدخول عن طريق middleware auth)
Route::redirect('/', '/dashboard')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/erp.php';
require __DIR__.'/assistant.php';
require __DIR__.'/settings.php';
