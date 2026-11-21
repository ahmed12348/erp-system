<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\Warehouse;
use App\Services\AliasService;
use Database\Seeders\ClientProductsSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سيدر أصناف العميل.
 *
 * بيتشغّل على السيرفر الحقيقي فوق بيانات شغّالة، فأهم حاجة فيه إنه
 * آمن: يتشغّل أكتر من مرة من غير ما يضرّ، وما يلمسش حاجة غير أصنافه.
 */
class ClientProductsSeederTest extends TestCase
{
    use RefreshDatabase;

    private const CATEGORY = 'إكسسوارات ستائر';

    public function test_it_creates_the_five_products_in_one_category(): void
    {
        $this->seed(ClientProductsSeeder::class);

        $this->assertSame(5, Product::count());

        $category = Category::where('name', self::CATEGORY)->sole();

        $this->assertSame(5, Product::where('category_id', $category->id)->count());

        $this->assertDatabaseHas('products', [
            'sku' => 'KB-25',
            'name' => 'كبولي مجوز سيجوستة 25 سم بدون دعم',
            'unit' => 'جوز',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'BL-777',
            'name' => 'بلاوزم 777 A مقاس 33×42',
            'unit' => 'كرتونة',
        ]);
    }

    public function test_new_products_start_with_zero_prices(): void
    {
        $this->seed(ClientProductsSeeder::class);

        $product = Product::where('sku', 'FR-01')->sole();

        $this->assertSame(0.0, (float) $product->purchase_price);
        $this->assertSame(0.0, (float) $product->sale_price);
    }

    public function test_it_can_run_twice_without_duplicating(): void
    {
        $this->seed(ClientProductsSeeder::class);
        $this->seed(ClientProductsSeeder::class);

        $this->assertSame(5, Product::count());
        $this->assertSame(1, Category::where('name', self::CATEGORY)->count());
    }

    /**
     * العميل بيحط الأسعار من الشاشة. تشغيل السيدر تاني بعد كده ما
     * ينفعش يرجّعها صفر ويمسح شغله.
     */
    public function test_re_running_keeps_prices_the_user_set(): void
    {
        $this->seed(ClientProductsSeeder::class);

        Product::where('sku', 'FR-01')->update([
            'purchase_price' => 120,
            'sale_price' => 180,
            'min_stock' => 10,
        ]);

        $this->seed(ClientProductsSeeder::class);

        $product = Product::where('sku', 'FR-01')->sole();

        $this->assertSame(120.0, (float) $product->purchase_price);
        $this->assertSame(180.0, (float) $product->sale_price);
        $this->assertSame(10.0, (float) $product->min_stock);
    }

    public function test_it_leaves_other_data_alone(): void
    {
        $other = Product::create([
            'sku' => 'X-1',
            'name' => 'صنف تاني',
            'unit' => 'قطعة',
            'purchase_price' => 50,
            'sale_price' => 70,
            'min_stock' => 1,
        ]);

        $warehouse = Warehouse::create(['name' => 'مخزن قديم']);
        $customer = Customer::create(['name' => 'تاجر قديم', 'price_tier' => 'sale']);

        $this->seed(ClientProductsSeeder::class);

        $this->assertDatabaseHas('products', ['id' => $other->id, 'sale_price' => 70]);
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertSame(6, Product::count());
    }

    public function test_it_reuses_an_existing_category(): void
    {
        $existing = Category::create(['name' => self::CATEGORY]);

        $this->seed(ClientProductsSeeder::class);

        $this->assertSame(1, Category::count());
        $this->assertSame(
            5,
            Product::where('category_id', $existing->id)->count(),
        );
    }

    public function test_it_seeds_the_scanned_spellings_as_aliases(): void
    {
        $this->seed(ClientProductsSeeder::class);

        $product = Product::where('sku', 'FR-01')->sole();

        $this->assertDatabaseHas('product_aliases', [
            'product_id' => $product->id,
            'alias' => 'الفاروق فضة',
        ]);

        $this->assertDatabaseHas('product_aliases', [
            'product_id' => $product->id,
            'alias' => 'الفاقور فضة',
        ]);

        /*
         * «الفاروق فضي» هو اسم الصنف بالظبط، فمالوش لازمة كاسم بديل —
         * المطابقة بالاسم بتشيله قبل ما توصل للأسماء البديلة.
         */
        $this->assertDatabaseMissing('product_aliases', [
            'alias' => 'الفاروق فضي',
        ]);
    }

    /** الاسم اللي المسح طلّعه لازم يوصل للصنف الصح من أول مرة */
    public function test_a_seeded_alias_matches_on_the_next_scan(): void
    {
        $this->seed(ClientProductsSeeder::class);

        $matched = app(AliasService::class)
            ->matchProduct('الفاقور فضة');

        $this->assertNotNull($matched);
        $this->assertSame('FR-01', $matched->sku);
    }

    public function test_re_running_does_not_duplicate_aliases(): void
    {
        $this->seed(ClientProductsSeeder::class);
        $this->seed(ClientProductsSeeder::class);

        $this->assertSame(
            2,
            ProductAlias::where('alias', 'like', 'الف%')->count(),
        );
    }

    /** السيدر الافتراضي ما يعرفش عنه حاجة — بيتشغّل لوحده بس */
    public function test_it_is_not_part_of_the_default_seed(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Product::count());
        $this->assertDatabaseMissing('categories', ['name' => self::CATEGORY]);
    }
}
