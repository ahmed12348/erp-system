<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Services\AliasService;
use Illuminate\Database\Seeder;

/**
 * أصناف العميل الحقيقية.
 *
 * سيدر مستقل — **مش** مربوط بـ `DatabaseSeeder`، فما بيشتغلش مع
 * `php artisan db:seed` العادي. بيتشغّل لوحده لما نحتاجه:
 *
 *     php artisan db:seed --class=ClientProductsSeeder
 *
 * بيشتغل على السيرفر بعد `composer install --no-dev`، فمفيش `factory()`
 * ولا `fake()` — مكتبة Faker في `require-dev` بس.
 *
 * آمن لو اتشغّل أكتر من مرة، وما بيلمسش أي بيانات تانية: بيحدّث الصنف
 * بكوده، وما بيمسحش ولا بيعدّل مخازن ولا تجار ولا حركات.
 */
class ClientProductsSeeder extends Seeder
{
    private const CATEGORY = 'إكسسوارات ستائر';

    /** [الاسم, الكود, الوحدة] */
    private const PRODUCTS = [
        ['الفاروق فضي', 'FR-01', 'كرتونة'],
        ['بلاوزم 777 A مقاس 33×42', 'BL-777', 'كرتونة'],
        ['كانة 90×10', 'KN-9010', 'كرتونة'],
        ['كبولي مجوز سيجوستة 25 سم بدون دعم', 'KB-25', 'جوز'],
        ['أحمد مجرول', 'AM-01', 'كرتونة'],
    ];

    /**
     * الأسماء البديلة — زي ما المسح طلّعها فعلاً من فواتير العميل.
     *
     * بنزرعها هنا عشان أول مسح جاي يطابق على طول من غير ما المستخدم
     * يصحّح نفس الصنف تاني. الاسم اللي يطلع مطابق لاسم الصنف بالظبط
     * بيتخطّى — المطابقة بالاسم بتشيله أصلاً.
     *
     * @var array<string, list<string>>
     */
    private const ALIASES = [
        'FR-01' => ['الفاروق فضة', 'الفاروق فضي', 'الفاقور فضة'],
        // TODO: باقي القراءات لسه ما وصلتش — القايمة اتقطعت في النص
        'BL-777' => [],
        'KN-9010' => [],
        'KB-25' => [],
        'AM-01' => [],
    ];

    public function __construct(private readonly AliasService $aliases) {}

    public function run(): void
    {
        $category = Category::firstOrCreate(['name' => self::CATEGORY]);

        foreach (self::PRODUCTS as [$name, $code, $unit]) {
            /*
             * الأسعار مش في قايمة التحديث عن قصد.
             *
             * العميل بيحطّها من الشاشة، ولو كانت هنا كان تشغيل السيدر
             * تاني هيرجّعها صفر ويمسح شغله. الصنف الجديد بياخد صفر من
             * الافتراضي بتاع الجدول، والموجود بيحتفظ بأسعاره.
             */
            $product = Product::updateOrCreate(
                ['sku' => $code],
                [
                    'name' => $name,
                    'unit' => $unit,
                    'category_id' => $category->id,
                    'is_active' => true,
                ],
            );

            $this->seedAliases($product, self::ALIASES[$code] ?? []);
        }

        $this->command?->info(
            'أصناف العميل: '.count(self::PRODUCTS).' صنف في تصنيف «'.self::CATEGORY.'».'
        );
    }

    /**
     * @param  list<string>  $aliases
     */
    private function seedAliases(Product $product, array $aliases): void
    {
        foreach ($aliases as $alias) {
            $this->aliases->rememberProduct($product, $alias);
        }
    }
}
