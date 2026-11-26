<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

/**
 * شجرة تصنيفات المصروفات الافتراضية.
 *
 * بيشتغل على السيرفر بعد `composer install --no-dev`، فمفيش `factory()`
 * ولا `fake()` — مكتبة Faker في `require-dev` بس ومش هتكون موجودة هناك.
 *
 * آمن لو اتشغّل أكتر من مرة: بيطابق بالكود، فتشغيله تاني ما بيكرّرش
 * ولا بيمسح أي تعديل العميل عمله على الأسماء.
 *
 * **الأكواد مقصودة**: 5100 و5200 مجموعات، و5110 و5120… بنود. الترقيم
 * ده هو اللي هيربط التصنيفات بدليل حسابات كامل بعدين من غير ما نرجع
 * نطابق بالاسم.
 */
class ExpenseCategorySeeder extends Seeder
{
    /** [كود المجموعة, اسمها, [[كود البند, اسمه], …]] */
    private const TREE = [
        ['5100', 'مصروفات ثابتة', [
            ['5110', 'إيجار'],
            ['5120', 'مرتبات'],
            ['5130', 'كهرباء ومياه'],
            ['5140', 'إنترنت'],
        ]],
        ['5200', 'مصروفات متغيرة', [
            ['5210', 'نقل وشحن'],
            ['5220', 'صيانة'],
            ['5230', 'دعاية'],
            ['5240', 'ضيافة'],
            ['5290', 'أخرى'],
        ]],
    ];

    public function run(): void
    {
        $order = 0;

        foreach (self::TREE as [$code, $name, $children]) {
            $group = ExpenseCategory::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'parent_id' => null, 'sort_order' => $order += 10],
            );

            $childOrder = 0;

            foreach ($children as [$childCode, $childName]) {
                ExpenseCategory::firstOrCreate(
                    ['code' => $childCode],
                    [
                        'name' => $childName,
                        'parent_id' => $group->id,
                        'sort_order' => $childOrder += 10,
                    ],
                );
            }
        }
    }
}
