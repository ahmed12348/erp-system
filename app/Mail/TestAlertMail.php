<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

/**
 * إيميل تجريبي — بيتبعت من شاشة إعدادات التنبيهات.
 *
 * بيستعمل نفس قالب التنبيهات العربي عن قصد: الزرار ده بيتأكد إن SMTP
 * شغّال **وإن القالب بيوصل بشكله الصح** — تجربة بنص عادي كانت هتعدّي
 * حتى لو القالب نفسه بايظ عند العميل.
 */
class TestAlertMail extends Mailable
{
    public function build(): self
    {
        return $this->subject('إيميل تجريبي من '.config('app.name'))
            ->view('mail.notification', [
                'greeting' => 'أهلاً',
                'title' => 'الإيميل شغّال',
                'lines' => [
                    'الرسالة دي اتبعتت من شاشة إعدادات التنبيهات عشان تتأكد إن إعدادات SMTP مظبوطة.',
                    'لو وصلتك، يبقى تنبيهات المخزون (نقص، خلص، والملخّص اليومي) هتوصل على نفس الإيميل.',
                    'اتبعتت في: '.now()->toDateTimeString(),
                ],
                'actionUrl' => url('/inventory'),
                'actionText' => 'افتح المخزون',
            ]);
    }
}
