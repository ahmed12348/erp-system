<?php

use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

/*
 * ملخّص النواقص اليومي.
 *
 * التنبيهات في طابور، والاستضافة المشتركة مافيهاش عامل بيفضل شغّال.
 * فالجدولة بتشغّل الطابور لحد ما يفضى وتسيبه — كرون واحد كل دقيقة
 * بيغطّي الاتنين (`schedule:run` في الكرون، وهو بينادي ده).
 */
Schedule::command('erp:stock-digest')
    ->dailyAt(config('notifications.digest_at', '09:00'))
    ->description('ملخّص النواقص اليومي');

/*
 * تفريغ طابور التنبيهات.
 *
 * `--stop-when-empty` مهم: من غيره العملية بتفضل عايشة وبتاكل
 * موارد الاستضافة المشتركة. `withoutOverlapping` بيمنع إن دقيقتين
 * ورا بعض يشغّلوا عاملين في نفس الوقت.
 */
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->description('تشغيل طابور التنبيهات');

/* تقليم سجل النشاط — أول كل شهر */
Schedule::command('erp:prune-activity')
    ->monthlyOn(1, '03:00')
    ->description('تقليم سجل النشاط');
