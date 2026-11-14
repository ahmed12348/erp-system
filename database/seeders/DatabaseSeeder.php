<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * الوضع الافتراضي — نظام نضيف جاهز لتسليم العميل.
 *
 * المستخدمين الثلاثة بأدوارهم، اسم الشركة، مخزن افتراضي، ومثال واحد من كل
 * نوع (تصنيف، صنف، تاجر، مورد). مفيش فواتير ولا سندات ولا حركات مخزون.
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
            BaselineSeeder::class,
        ]);
    }
}
