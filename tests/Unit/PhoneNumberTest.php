<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * تطبيع أرقام التليفون لواتساب.
 *
 * الأرقام في النظام مكتوبة بكل الأشكال — بمسافات وشرط و`+20` و`0020`
 * وأحياناً بأرقام عربية. واتساب عايزها شكل واحد: دولي بالأرقام بس.
 */
class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function numbers(): array
    {
        return [
            // ── محلي مصري ───────────────────────────────────────
            'موبايل مصري' => ['01012345678', '201012345678'],
            'فودافون' => ['01098765432', '201098765432'],
            'بمسافات' => ['0101 234 5678', '201012345678'],
            'بشرط' => ['010-1234-5678', '201012345678'],
            'بأرقام عربية' => ['٠١٠١٢٣٤٥٦٧٨', '201012345678'],
            'من غير صفر' => ['1012345678', '201012345678'],

            // ── مكتوب دولي ──────────────────────────────────────
            'بعلامة زائد' => ['+201012345678', '201012345678'],
            'بزائد ومسافات' => ['+20 101 234 5678', '201012345678'],
            'بصفرين' => ['00201012345678', '201012345678'],
            'دولي من غير زائد' => ['201012345678', '201012345678'],
            // مورد تركي بيفضل تركي — مصر مش بتتفرض على الكل
            'رقم تركي' => ['+90 532 123 4567', '905321234567'],
            'رقم سعودي' => ['00966501234567', '966501234567'],

            // ── مش صالح ────────────────────────────────────────
            'فاضي' => ['', null],
            'مسافات بس' => ['   ', null],
            'نص' => ['مفيش', null],
            'قصير' => ['0101234', null],
            'أرضي مصري' => ['0223456789', null],
            'طويل أوي' => ['0123456789012345678', null],
            'كود صنف' => ['HA-9001', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('numbers')]
    public function test_it_normalises(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::forWhatsApp($input));
    }

    public function test_has_whats_app_mirrors_the_normaliser(): void
    {
        $this->assertTrue(PhoneNumber::hasWhatsApp('01012345678'));
        $this->assertFalse(PhoneNumber::hasWhatsApp('0101234'));
        $this->assertFalse(PhoneNumber::hasWhatsApp(null));
    }

    /**
     * رقم اتطبّع مرة مابيتغيّرش لو عدّى تاني.
     *
     * الرقم بيعدّي على الدالة دي في الواجهة وعلى السيرفر، وساعات
     * الاتنين — فلازم تبقى ثابتة.
     */
    public function test_normalising_twice_changes_nothing(): void
    {
        $once = PhoneNumber::forWhatsApp('0101 234 5678');

        $this->assertSame($once, PhoneNumber::forWhatsApp($once));
    }
}
