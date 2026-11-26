<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * الوضع الافتراضي — نظام فاضي تمامًا جاهز لتسليم العميل.
 *
 * بيعمل المستخدمين الثلاثة بأدوارهم وفريق الشركة وبس. مفيش مخازن ولا
 * تصنيفات ولا أصناف ولا تجار ولا موردين ولا أي حركة — العميل بيبدأ من
 * صفحة بيضا ويسجّل بياناته بنفسه.
 *
 * بيشتغل على السيرفر بعد `composer install --no-dev` (مفيش factory ولا fake).
 *
 * للبيانات التجريبية الكاملة (3 شهور حركة):
 *   php artisan db:seed --class=DemoSeeder
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            ErpUserSeeder::class,
            /*
             * شجرة تصنيفات المصروفات — دي بيانات نظام مش بيانات عميل.
             * من غيرها شاشة المصروفات بتفتح على قايمة فاضية وأول مصروف
             * محتاج المستخدم يعمل تصنيف الأول.
             */
            ExpenseCategorySeeder::class,
        ]);
    }
}
