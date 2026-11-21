<?php

namespace App\Support;

/**
 * تطبيع النص العربي عشان المقارنة.
 *
 * الفاتورة المطبوعة مابتكتبش الاسم بنفس شكله في النظام أبدًا: «أحمد» و
 * «احمد»، «شركه» و«شركة»، «الـمنصورة» بتطويل، وأرقام هندية ولاتينية.
 * كل ده نفس الاسم عند اللي بيقرا، فلازم يبقى نفس النص عند المقارنة.
 */
class ArabicText
{
    /** الهمزات بأشكالها → الحرف الأساسي */
    private const LETTERS = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ى' => 'ي', 'ئ' => 'ي',
        'ؤ' => 'و',
        'ة' => 'ه',
    ];

    /** الأرقام الهندية → لاتينية */
    private const DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    /**
     * نص جاهز للمقارنة — من غير تشكيل ولا تطويل ولا علامات ترقيم.
     */
    public static function normalize(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $text = strtr($value, self::DIGITS);
        $text = strtr($text, self::LETTERS);

        // التشكيل والتطويل — مالهمش أي تأثير على المعنى
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0640}]/u', '', $text) ?? $text;

        // علامات الترقيم العربية والإنجليزية → مسافة
        $text = preg_replace('/[^\p{Arabic}\p{L}\p{N}]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($text)) ?? $text);
    }

    /**
     * أرقام الموبايل — بنقارن الأرقام بس.
     *
     * «٠١٠-١٢٣ ٤٥٦٧» و«01012345678» نفس الرقم.
     */
    public static function digits(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return preg_replace('/\D+/', '', strtr($value, self::DIGITS)) ?? '';
    }
}
