<?php

use App\Models\TeamInvitation;
use App\Services\StockAlertService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * كل المهام هنا بتشتغل **جوّه نفس العملية**.
 *
 * `Schedule::command()` بيفتح عملية PHP جديدة عن طريق `proc_open`،
 * و`proc_open` و`exec` مقفولين على الاستضافة المشتركة — يعني أي مهمة
 * بـ `command()` مش هتشتغل أصلاً على السيرفر، وهتفشل في صمت.
 *
 * فكل حاجة هنا `->call()` أو `->job()`: إما نداء مباشر للخدمة، أو
 * `Artisan::call()` اللي بينفّذ الأمر في نفس العملية. في اختبار
 * (`ScheduleTest`) بيتأكّد إن مفيش ولا مهمة بتفتح عملية.
 */

Schedule::call(function (): void {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('حذف الدعوات المنتهية');

/*
 * ملخّص النواقص اليومي — بيروح على الجرس.
 *
 * نداء مباشر للخدمة أوفر من `Artisan::call` لنفس الشغل.
 */
Schedule::call(fn () => app(StockAlertService::class)->sendDigest())
    ->dailyAt(config('notifications.digest_at', '09:00'))
    ->description('ملخّص النواقص اليومي');

/*
 * تفريغ الطابور.
 *
 * تنبيهات الجرس مابتعدّيش من هنا — بتتبعت فورًا وقت الحركة. الطابور
 * ده للقنوات الخارجية (إيميل/واتساب/push) ولو مفاتيحها مش متظبّطة
 * بيبقى فاضي ومايكلّفش حاجة.
 *
 * `--stop-when-empty` مهم: من غيره النداء مابيرجعش والكرون بيعلّق.
 * `--max-time` بيحط سقف لو فضل شغل كتير.
 */
Schedule::call(fn () => Artisan::call('queue:work', [
    '--stop-when-empty' => true,
    '--tries' => 3,
    '--max-time' => 50,
]))
    ->everyMinute()
    // الاسم لازم ييجي الأول — `withoutOverlapping` بيبني القفل منه
    ->name('تفريغ الطابور')
    ->withoutOverlapping();

/* تقليم سجل النشاط — أول كل شهر */
Schedule::call(fn () => Artisan::call('erp:prune-activity'))
    ->monthlyOn(1, '03:00')
    ->description('تقليم سجل النشاط');
