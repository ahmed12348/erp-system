<?php

namespace App\Support;

/**
 * تطبيع أرقام التليفون لصيغة دولية بالأرقام بس.
 *
 * واتساب بيطلب الرقم دولي من غير `+` ولا مسافات (`201012345678`).
 * والأرقام في النظام بتتكتب بكل الأشكال: `01012345678`، `0101 234
 * 5678`، `+20 101 234 5678`، `0020101...`، وأحياناً بأرقام عربية.
 * الشكل ده بيتصلّح هنا مرة واحدة بدل ما كل شاشة تحاول لوحدها.
 *
 * **مصر هي الافتراضي**: الرقم اللي بيبدأ بصفر بيتفهم على إنه محلي
 * مصري. أي رقم دولي مكتوب بكوده (`+90...` تركي مثلاً) بيعدّي زي ما
 * هو — فمورد تركي رقمه بيفضل تركي.
 */
class PhoneNumber
{
    /** كود مصر */
    private const EGYPT = '20';

    /** أطوال الموبايل المصري بعد الكود: 1 + 9 أرقام */
    private const EGYPT_MOBILE_LENGTH = 10;

    /**
     * الرقم جاهز لواتساب — أو null لو مش صالح.
     *
     * بنرجّع null مش نص فاضي عشان الواجهة تقدر تفرّق بين «مفيش رقم»
     * و«رقم مكتوب غلط»، والاتنين بيفتحوا نفس النافذة بس برسالة مختلفة.
     */
    public static function forWhatsApp(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim($value);

        /*
         * `+` و`00` الاتنين معناهم «الرقم ده دولي أصلاً». بنفتكر ده
         * قبل ما نشيل الرموز، عشان بعدها مفيش فرق بين `+201…` و`201…`
         * و`0201…` وكلهم بيبقوا أرقام.
         */
        $international = str_starts_with($raw, '+') || str_starts_with($raw, '00');

        $digits = ArabicText::digits($raw);

        if ($digits === '') {
            return null;
        }

        if ($international) {
            $digits = ltrim($digits, '0');

            return self::plausible($digits) ? $digits : null;
        }

        // رقم محلي مصري: `01012345678` → `201012345678`
        if (str_starts_with($digits, '0')) {
            $local = ltrim($digits, '0');

            return strlen($local) === self::EGYPT_MOBILE_LENGTH
                ? self::EGYPT.$local
                : null;
        }

        /*
         * مكتوب من غير صفر ولا `+`. لو طوله طول موبايل مصري من غير
         * الصفر (`1012345678`) بنعتبره مصري، وإلا بناخده زي ما هو
         * على إنه دولي.
         */
        if (strlen($digits) === self::EGYPT_MOBILE_LENGTH && str_starts_with($digits, '1')) {
            return self::EGYPT.$digits;
        }

        return self::plausible($digits) ? $digits : null;
    }

    /** فيه رقم صالح نبعت عليه؟ */
    public static function hasWhatsApp(?string $value): bool
    {
        return self::forWhatsApp($value) !== null;
    }

    /**
     * طول معقول لرقم دولي.
     *
     * E.164 بيقول من 8 لـ 15 رقم. مش تحقّق حقيقي — بس بيمنع إن رقم
     * قطعة أو كود مخزن يتفتح بيه واتساب على الفاضي.
     */
    private static function plausible(string $digits): bool
    {
        $length = strlen($digits);

        return $length >= 8 && $length <= 15;
    }
}
