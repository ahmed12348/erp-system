<?php

/*
 * إعدادات التنبيهات.
 *
 * الأنواع والقنوات معرّفة هنا عشان إضافة تنبيه جديد (حد الائتمان،
 * البيع تحت التكلفة، المتأخرات) تبقى سطر في المصفوفة دي + كلاس
 * Notification — من غير migration ولا تعديل في الواجهة.
 */

return [
    /*
     * الأنواع المتاحة.
     *
     * `roles` = الأدوار اللي بتستقبل النوع ده افتراضيًا، والمستخدم
     * يقدر يقفله من شاشة إعدادات التنبيهات.
     */
    'types' => [
        'low_stock' => [
            'label' => 'صنف نزل تحت حد الطلب',
            'description' => 'أول ما رصيد صنف ينزل تحت الحد — مرة واحدة، مش مع كل بيعة.',
            'roles' => ['admin', 'accountant'],
            'default' => ['database' => true, 'mail' => true, 'push' => false, 'whatsapp' => false],
        ],
        'out_of_stock' => [
            'label' => 'صنف خلص',
            'description' => 'لما الرصيد يوصل صفر.',
            'roles' => ['admin', 'accountant'],
            'default' => ['database' => true, 'mail' => true, 'push' => false, 'whatsapp' => false],
        ],
        'daily_digest' => [
            'label' => 'ملخّص النواقص اليومي',
            'description' => 'كل يوم 9 الصبح — كل الأصناف تحت حد الطلب في رسالة واحدة.',
            'roles' => ['admin', 'accountant'],
            'default' => ['database' => true, 'mail' => true, 'push' => false, 'whatsapp' => false],
        ],
    ],

    /*
     * القنوات.
     *
     * `requires` بيقول إيه اللي لازم يكون متظبّط عشان القناة تشتغل —
     * القناة بتظهر في الإعدادات مقفولة ومكتوب جنبها الناقص بدل ما
     * المستخدم يفعّلها ومايوصلهوش حاجة.
     */
    'channels' => [
        'database' => [
            'label' => 'داخل النظام',
            'description' => 'جرس التنبيهات في الشريط العلوي.',
            'requires' => null,
        ],
        'mail' => [
            'label' => 'إيميل',
            'description' => 'رسالة عربية على إيميل المستخدم.',
            'requires' => 'mail',
        ],
        'push' => [
            'label' => 'إشعار المتصفح',
            'description' => 'بيوصل حتى والنظام مقفول — محتاج إذن من المتصفح.',
            'requires' => 'push',
        ],
        'whatsapp' => [
            'label' => 'واتساب',
            'description' => 'رسالة على رقم الواتساب المسجّل.',
            'requires' => 'whatsapp',
        ],
    ],

    /* ميعاد الملخّص اليومي */
    'digest_at' => env('ERP_DIGEST_AT', '09:00'),

    /* Web Push — مفاتيح VAPID */
    'push' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', env('APP_URL')),
    ],

    /*
     * واتساب — Meta Cloud API الرسمي.
     *
     * مقفول لحد ما المفاتيح تتحط في .env. من غيرها القناة بتختفي من
     * الإعدادات وما بتتحاولش أصلاً.
     */
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        /*
         * قوالب الرسايل. واتساب مابيسمحش ببعت رسالة حرة لمستخدم
         * مابدأش محادثة في آخر 24 ساعة، فالتنبيهات لازم تبقى قوالب
         * معتمدة من Meta.
         */
        'templates' => [
            'low_stock' => env('WHATSAPP_TEMPLATE_LOW_STOCK', 'low_stock_alert'),
            'daily_digest' => env('WHATSAPP_TEMPLATE_DIGEST', 'daily_stock_digest'),
        ],
        'language' => env('WHATSAPP_TEMPLATE_LANG', 'ar'),
    ],
];
