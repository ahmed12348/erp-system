<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * نظام نضيف للعميل: مخزن افتراضي + مثال واحد من كل نوع عشان الشاشات
 * تبقى مفهومة من أول دخول.
 *
 * مفيش أي حركة هنا — لا فواتير ولا سندات ولا حركات مخزون. الرصيد صفر
 * والشاشات بتعرض حالة فاضية بزرار إجراء، مش أصفار.
 *
 * للبيانات التجريبية الكاملة: php artisan db:seed --class=DemoSeeder
 */
class BaselineSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::firstOrCreate(
            ['name' => 'المخزن الرئيسي'],
            ['location' => 'المقر الرئيسي', 'is_active' => true],
        );

        $category = Category::firstOrCreate(['name' => 'تصنيف افتراضي']);

        Product::firstOrCreate(
            ['sku' => 'SAMPLE-001'],
            [
                'category_id' => $category->id,
                'name' => 'صنف تجريبي — غيّر بياناته أو احذفه',
                'unit' => 'قطعة',
                'purchase_price' => 0,
                'sale_price' => 0,
                'min_stock' => 0,
                'is_active' => true,
            ],
        );

        Customer::firstOrCreate(
            ['name' => 'تاجر تجريبي — غيّر بياناته أو احذفه'],
            [
                'city' => 'القاهرة',
                'price_tier' => 'sale',
                'credit_limit' => null,
                'opening_balance' => 0,
            ],
        );

        Supplier::firstOrCreate(
            ['name' => 'مورد تجريبي — غيّر بياناته أو احذفه'],
            [
                'country' => 'مصر',
                'currency' => 'EGP',
                'opening_balance' => 0,
            ],
        );

        $this->command?->info('  تم تجهيز نظام نضيف: 3 مستخدمين، مخزن «'.$warehouse->name.'»، ومثال واحد من كل نوع.');
    }
}
