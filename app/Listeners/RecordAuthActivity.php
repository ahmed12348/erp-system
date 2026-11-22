<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

/**
 * تسجيل الدخول والخروج ومحاولات الدخول الفاشلة.
 *
 * المحاولة الفاشلة بتتسجّل بالإيميل اللي اتكتب من غير ما نربطها
 * بمستخدم: الإيميل ممكن يكون غلط أصلاً، ولو كان صح يبقى حد بيحاول
 * يدخل بحساب مش بتاعه — والاتنين يهمّوا المدير.
 *
 * الباسورد عمره ما بيتسجّل، ولا حتى طوله.
 */
class RecordAuthActivity
{
    public function __construct(private readonly Request $request) {}

    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        /*
         * الدخول الواحد بيوقّع الحدث أكتر من مرة (Fortify بينادي
         * الجارد، والجارد بيطلّع الحدث تاني)، فبنعلّم على الطلب نفسه
         * عشان السجل ما يبقاش فيه سطرين لنفس الدخول.
         */
        if ($this->alreadyHandled('login')) {
            return;
        }

        /*
         * التحديث من غير أحداث الموديل عن قصد: `last_login_at` بيتغيّر
         * كل دخول، ولو عدّى على التسجيل العادي كان السجل هيمتلي بسطور
         * «عدّل مستخدم» مالهاش أي معنى.
         */
        User::withoutEvents(fn () => $user->forceFill([
            'last_login_at' => now(),
        ])->saveQuietly());

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->event('login')
            ->withProperties($this->context())
            ->log('login');
    }

    public function handleFailed(Failed $event): void
    {
        if ($this->alreadyHandled('login_failed')) {
            return;
        }

        activity('auth')
            // المستخدم ممكن يكون null لو الإيميل نفسه مش موجود
            ->causedBy($event->user instanceof User ? $event->user : null)
            ->event('login_failed')
            ->withProperties([
                ...$this->context(),
                'email' => $event->credentials['email'] ?? null,
            ])
            ->log('login_failed');
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->event('logout')
            ->withProperties($this->context())
            ->log('logout');
    }

    /**
     * بيمنع تسجيل نفس الحدث مرتين في نفس الطلب.
     *
     * العلامة على الـ Request نفسه، وهو نسخة واحدة للطلب كله، فبتتمسح
     * لوحدها مع نهايته — من غير حالة ساكنة تفضل عايشة بين الطلبات.
     */
    private function alreadyHandled(string $event): bool
    {
        $key = 'activity.handled.'.$event;

        if ($this->request->attributes->get($key) === true) {
            return true;
        }

        $this->request->attributes->set($key, true);

        return false;
    }

    /**
     * الجهاز اللي جه منه الطلب.
     *
     * @return array<string, mixed>
     */
    private function context(): array
    {
        return [
            'ip' => $this->request->ip(),
            // المتصفح كامل — التحليل بيحصل وقت العرض مش وقت التسجيل
            'agent' => str($this->request->userAgent() ?? '')->limit(255)->toString(),
        ];
    }
}
