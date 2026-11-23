<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTeamUrlDefaults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        /*
         * ويبهوك واتساب جاي من سيرفرات Meta مش من متصفّح، فمالوش
         * توكن CSRF يبعته. الحماية هنا مش توكن: الرقم لازم يكون
         * مربوط بحساب متأكّد، والتأكيد الأولي بـ verify_token سرّي.
         */
        $middleware->validateCsrfTokens(except: ['ai/whatsapp/webhook']);

        /*
         * الاستضافة المشتركة بتنهي الـ TLS على بروكسي قدّام PHP، فمن غير
         * السطر ده url() و route() بيطلّعوا http:// جوه روابط اكتشاف الـ OAuth
         * و claude.ai بيرفض يكمّل. الثقة في X-Forwarded-* بتظبّط الـ scheme.
         */
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetTeamUrlDefaults::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
