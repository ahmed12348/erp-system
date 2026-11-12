<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * موردين الاستيراد: ٣ من الصين و ٢ من تركيا، كل مورد بعملة التعامل الخاصة به.
 */
class SupplierSeeder extends Seeder
{
    private const SUPPLIERS = [
        [
            'name' => 'جوانزو هونج فا للتجارة',
            'phone' => '+86 20 8755 4120',
            'country' => 'الصين',
            'currency' => 'USD',
            'opening_balance' => 18500,
            'notes' => 'أجهزة منزلية صغيرة — الشحن من ميناء نانشا، مدة التوريد ٤٥ يوم.',
        ],
        [
            'name' => 'نينجبو صن رايز للأجهزة الكهربائية',
            'phone' => '+86 574 8816 9034',
            'country' => 'الصين',
            'currency' => 'USD',
            'opening_balance' => 0,
            'notes' => 'عدد وأدوات كهربائية — الدفع ٣٠٪ مقدم والباقي عند الشحن.',
        ],
        [
            'name' => 'شنزن برايت واي للإضاءة',
            'phone' => '+86 755 2309 7788',
            'country' => 'الصين',
            'currency' => 'USD',
            'opening_balance' => 7200,
            'notes' => 'لمبات وكشافات ليد — ضمان سنتين على الكشافات.',
        ],
        [
            'name' => 'إسطنبول الأناضول للمنسوجات',
            'phone' => '+90 212 544 7719',
            'country' => 'تركيا',
            'currency' => 'EUR',
            'opening_balance' => 0,
            'notes' => 'مفروشات ومنسوجات — اتفاقية المنشأ تخفّض الجمارك.',
        ],
        [
            'name' => 'إزمير للأدوات المنزلية',
            'phone' => '+90 232 461 2250',
            'country' => 'تركيا',
            'currency' => 'TRY',
            'opening_balance' => 95000,
            'notes' => 'أدوات مطبخ وبلاستيك — التعامل بالليرة التركية.',
        ],
    ];

    public function run(): void
    {
        foreach (self::SUPPLIERS as $supplier) {
            Supplier::create($supplier);
        }
    }
}
