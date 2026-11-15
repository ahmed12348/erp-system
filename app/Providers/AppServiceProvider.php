<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
        Schema::defaultStringLength(191);
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
