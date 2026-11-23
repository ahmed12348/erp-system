<?php

namespace App\Providers;

use App\Channels\WebPushChannel;
use App\Channels\WhatsAppChannel;
use App\Enums\ErpAbility;
use App\Listeners\RecordAuthActivity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureOAuth();
        $this->configureAbilities();
        $this->configureAiRateLimits();
        $this->configureActivityLog();
        $this->configureNotificationChannels();
        Schema::defaultStringLength(191);
    }

    /**
     * قنوات التنبيهات المخصّصة.
     *
     * الاتنين بيسكتوا لو مفاتيحهم مش متظبّطة، فالنوتيفيكيشن ما بيوقعش
     * لو واحدة منهم مش جاهزة.
     */
    protected function configureNotificationChannels(): void
    {
        Notification::extend('whatsapp', fn () => app(WhatsAppChannel::class));
        Notification::extend('push', fn () => app(WebPushChannel::class));
    }

    /**
     * تسجيل الدخول والخروج والمحاولات الفاشلة في سجل النشاط.
     */
    protected function configureActivityLog(): void
    {
        Event::listen(Login::class, [RecordAuthActivity::class, 'handleLogin']);
        Event::listen(Failed::class, [RecordAuthActivity::class, 'handleFailed']);
        Event::listen(Logout::class, [RecordAuthActivity::class, 'handleLogout']);
    }

    /**
     * حدود استخدام المساعد الذكي — لكل مستخدم على حدة.
     *
     * نداء الموديل بيكلّف فلوس وبيستهلك حصة، فمستخدم واحد ماينفعش
     * يستنزفها على الباقيين. الرد عربي عشان الواجهة تعرضه زي ما هو.
     */
    protected function configureAiRateLimits(): void
    {
        $limits = [
            'ai-chat' => ['أسئلة كتير ورا بعض. استنى دقيقة وجرّب تاني.', 'chat'],
            'ai-scan' => ['مسحت فواتير كتير ورا بعض. استنى دقيقة وجرّب تاني.', 'scan'],
        ];

        foreach ($limits as $name => [$message, $key]) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute(
                (int) config("ai.rate_limits.{$key}")
            )
                ->by((string) $request->user()?->id)
                ->response(fn () => response()->json([
                    'message' => $message,
                    'reason' => 'throttled',
                ], 429)));
        }
    }

    /**
     * صلاحيات النظام — كل حالة في `ErpAbility` بتبقى Gate بنفس الاسم، فالتحقق
     * في الكنترولرات بيبقى `Gate::authorize(ErpAbility::ManageMasterData->value)`.
     */
    protected function configureAbilities(): void
    {
        foreach (ErpAbility::cases() as $ability) {
            Gate::define($ability->value, fn (User $user) => $user->allows($ability));
        }
    }

    /**
     * إعدادات Passport المستخدمة في سيرفر الـ MCP.
     *
     * شاشة السماح بالوصول هي الشاشة الوحيدة اللي المستخدم بيشوفها وهو بيربط
     * claude.ai بالنظام، فبتستخدم نسخة عربية RTL من قالب laravel/mcp.
     */
    protected function configureOAuth(): void
    {
        Passport::authorizationView(
            fn (array $parameters) => view('mcp.authorize', $parameters)
        );

        // توكنات الوصول سنة، والتجديد سنتين — السيرفر داخلي ومربوط بمستخدم واحد
        Passport::tokensExpireIn(now()->addYear());
        Passport::refreshTokensExpireIn(now()->addYears(2));
        Passport::personalAccessTokensExpireIn(now()->addYear());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
