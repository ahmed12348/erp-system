<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Exceptions\PrismRequestTooLargeException;
use Prism\Prism\Exceptions\PrismStructuredDecodingException;
use Throwable;

/**
 * أي عطل في المساعد الذكي بيوصل للمستخدم من هنا برسالة عربية واحدة.
 *
 * المستخدم مايهموش إن المزوّد رجّع 429 ولا 503 — يهمه يعرف لو ده عطل
 * مؤقت يجرّب بعده تاني، ولا حاجة محتاجة المدير. فبنترجم كل أنواع
 * الأعطال لرسالة واحدة واضحة، والتفاصيل الفنية بتروح للّوج.
 */
class AiUnavailableException extends Exception
{
    private function __construct(
        public readonly string $arabicMessage,
        public readonly string $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct($arabicMessage, 0, $previous);
    }

    /**
     * بيصنّف استثناءات Prism لرسالة عربية مناسبة.
     */
    public static function from(Throwable $e): self
    {
        return match (true) {
            $e instanceof PrismRateLimitedException => new self(
                'تجاوزنا حد الاستخدام المسموح للمساعد دلوقتي. استنى شوية وجرّب تاني.',
                'rate_limited',
                $e,
            ),

            $e instanceof PrismProviderOverloadedException => new self(
                'المساعد مزحوم شوية دلوقتي. جرّب تاني بعد لحظات.',
                'overloaded',
                $e,
            ),

            $e instanceof PrismRequestTooLargeException => new self(
                'الملف كبير على المساعد. جرّب صورة أصغر أو أوضح.',
                'too_large',
                $e,
            ),

            $e instanceof PrismStructuredDecodingException => new self(
                'مقدرتش أقرا الفاتورة دي. جرّب صورة أوضح، أو اكتب البيانات يدوي.',
                'bad_json',
                $e,
            ),

            self::looksLikeTimeout($e) => new self(
                'المساعد أخد وقت أطول من اللازم. جرّب تاني، أو اسأل سؤال أبسط شوية.',
                'timeout',
                $e,
            ),

            self::looksLikeMissingModel($e) => new self(
                'موديل المساعد مش متاح على المفتاح ده. كلّم المدير يراجع ERP_AI_CHAT_MODEL.',
                'model_not_found',
                $e,
            ),

            self::looksLikeQuota($e) => new self(
                'خلصت الحصة المجانية للمساعد. كلّم المدير يراجع إعدادات الـ API.',
                'quota',
                $e,
            ),

            self::looksLikeAuth($e) => new self(
                'مفتاح المساعد مش مظبوط. كلّم المدير يراجع إعدادات الـ API.',
                'auth',
                $e,
            ),

            default => new self(
                'المساعد مش راضي يرد دلوقتي. جرّب تاني بعد شوية.',
                'unknown',
                $e,
            ),
        };
    }

    /**
     * الميزانية خلصت قبل ما الرد يكمّل.
     *
     * بنرميها بنفسنا قبل ما PHP يقطع التنفيذ، عشان المستخدم يشوف جملة
     * مفهومة بدل صفحة 500 بيضا من السيرفر.
     */
    public static function timedOut(): self
    {
        return new self(
            'المساعد أخد وقت أطول من اللازم. جرّب تاني، أو اسأل سؤال أبسط شوية.',
            'timeout',
        );
    }

    /** المساعد مقفول من .env */
    public static function disabled(): self
    {
        return new self('المساعد الذكي مقفول حاليًا.', 'disabled');
    }

    /** مفيش مفتاح API متظبّط أصلًا */
    public static function notConfigured(): self
    {
        return new self(
            'المساعد الذكي لسه مش متظبّط. كلّم المدير يضيف مفتاح الـ API.',
            'not_configured',
        );
    }

    /** انقطاع الاتصال بسبب الوقت — cURL بيقولها بصيغ مختلفة */
    private static function looksLikeTimeout(Throwable $e): bool
    {
        return $e instanceof ConnectionException
            || (bool) preg_grep(
                '/timed? ?out|timeout|cURL error 28|deadline/i',
                [$e->getMessage()],
            );
    }

    /** الموديل مش موجود على المفتاح ده — 404 من المزوّد */
    private static function looksLikeMissingModel(Throwable $e): bool
    {
        return (bool) preg_grep(
            '/404|NOT_FOUND|is not found|not supported|does not exist/i',
            [$e->getMessage()],
        );
    }

    /**
     * المزوّدين بيرجّعوا تجاوز الحصة بأشكال مختلفة، وساعات جوه رسالة
     * خطأ عامة — فبندوّر على الكلمات المميّزة.
     */
    private static function looksLikeQuota(Throwable $e): bool
    {
        return (bool) preg_grep(
            '/quota|billing|insufficient_quota|exceeded your current/i',
            [$e->getMessage()],
        );
    }

    private static function looksLikeAuth(Throwable $e): bool
    {
        return (bool) preg_grep(
            '/api key|unauthorized|authentication|permission denied|invalid_api_key|401|403/i',
            [$e->getMessage()],
        );
    }
}
