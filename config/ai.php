<?php

/*
 * إعدادات المساعد الذكي — «اسأل السيستم» ومسح الفواتير.
 *
 * المزوّد بيتبدّل من .env لوحده: ERP_AI_PROVIDER=gemini أو anthropic.
 * مفاتيح الـ API بتتقرا من إعدادات Prism نفسها (GEMINI_API_KEY /
 * ANTHROPIC_API_KEY) وعمرها ما بتوصل للمتصفح — كل النداءات من السيرفر.
 *
 * الاستضافة مشتركة (exec و proc_open مقفولين، ومفيش Node)، فكل النداءات
 * HTTP عادي من غير streaming.
 */

$provider = env('ERP_AI_PROVIDER', 'gemini');

/*
 * موديل افتراضي لكل مزوّد. Gemini Flash هو الطبقة المجانية، و Haiku هو
 * أرخص موديلات Anthropic — الاتنين بيقروا صور و PDF وبيدعموا استدعاء الأدوات.
 */
$defaults = [
    'gemini' => [
        'chat' => 'gemini-2.5-flash',
        'vision' => 'gemini-2.5-flash',
    ],
    'anthropic' => [
        'chat' => 'claude-haiku-4-5-20251001',
        'vision' => 'claude-haiku-4-5-20251001',
    ],
];

$default = $defaults[$provider] ?? $defaults['gemini'];

return [
    /* قفل المساعد كله — زرار «اسأل السيستم» و«مسح فاتورة» بيختفوا لو false */
    'enabled' => env('ERP_AI_ENABLED', true),

    'provider' => $provider,

    'models' => [
        // المحادثة — محتاج استدعاء أدوات
        'chat' => env('ERP_AI_CHAT_MODEL', $default['chat']),
        // قراءة صور الفواتير و PDF — محتاج رؤية ومخرجات JSON متقيّدة
        'vision' => env('ERP_AI_VISION_MODEL', $default['vision']),
    ],

    'max_tokens' => (int) env('ERP_AI_MAX_TOKENS', 2048),

    /*
     * أقصى عدد جولات أدوات في سؤال واحد. المساعد ساعات بيحتاج أداتين
     * (رصيد تاجر + فواتيره مثلًا) قبل ما يرد.
     */
    'max_steps' => (int) env('ERP_AI_MAX_STEPS', 5),

    /* حد الاستخدام لكل مستخدم في الدقيقة */
    'rate_limits' => [
        'chat' => (int) env('ERP_AI_CHAT_RATE_LIMIT', 20),
        'scan' => (int) env('ERP_AI_SCAN_RATE_LIMIT', 6),
    ],

    /* أقصى عدد رسايل بتتحفظ في الجلسة — بيحدّ تكلفة التوكنز */
    'history_limit' => (int) env('ERP_AI_HISTORY_LIMIT', 12),

    /*
     * الأسئلة المقترحة اللي بتظهر في لوحة المحادثة وهي فاضية.
     */
    'starters' => [
        'إيه الأصناف اللي قربت تخلص؟',
        'مين أكبر 5 مديونيات علينا دلوقتي؟',
        'مبيعات الشهر ده كام؟',
        'إيه أكتر صنف بيعناه الشهر اللي فات؟',
    ],
];
