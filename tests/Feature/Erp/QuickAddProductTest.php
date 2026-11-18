<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * إضافة صنف من جوه فورم فاتورة الشراء.
 *
 * ده السيناريو الحقيقي لأول فاتورة على نظام فاضي: البضاعة لسه داخلة لأول
 * مرة، فالأصناف بتتعرّف والمستخدم بيكتب الفاتورة — من غير ما يسيبها ويروح
 * لصفحة الأصناف ويرجع تاني.
 */
class QuickAddProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->admin();
    }

    /** الفورم بيبعت اللي الـ dialog محتاجه: تصنيفات ووحدات وكود مقترح */
    public function test_the_purchase_form_carries_what_the_quick_add_dialog_needs(): void
    {
        Warehouse::create(['name' => 'مخزن']);
        Supplier::create(['name' => 'مورد', 'currency' => 'USD']);
        Category::create(['name' => 'زيوت']);

        $this->actingAs($this->user)
            ->get('/purchases/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('categories', 1)
                ->has('units')
                ->where('suggestedSku', 'P-0001')
            );
    }

    public function test_a_product_is_created_and_the_user_stays_on_the_invoice(): void
    {
        $this->actingAs($this->user)
            ->from('/purchases/create')
            ->post('/products/quick', [
                'name' => 'زيت عباد شمس 1 لتر',
                'sku' => 'P-0001',
                'unit' => 'قطعة',
                'sale_price' => 95,
                'purchase_price' => 70,
            ])
            // الرجوع لنفس الصفحة هو اللي بيخلي الفاتورة اللي لسه بتتكتب ما تضيعش
            ->assertRedirect('/purchases/create')
            ->assertSessionHasNoErrors();

        $product = Product::sole();

        $this->assertSame('زيت عباد شمس 1 لتر', $product->name);
        $this->assertSame('قطعة', $product->unit);
        $this->assertSame('95.00', $product->sale_price);
        $this->assertTrue($product->is_active);
    }

    /** الصنف الجديد بيظهر في قايمة الفاتورة على طول */
    public function test_the_new_product_shows_up_in_the_form_immediately(): void
    {
        Warehouse::create(['name' => 'مخزن']);
        Supplier::create(['name' => 'مورد', 'currency' => 'USD']);

        $this->actingAs($this->user)->get('/purchases/create')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('products', 0));

        $this->actingAs($this->user)->from('/purchases/create')
            ->post('/products/quick', [
                'name' => 'صنف جديد', 'sku' => 'P-0001',
                'unit' => 'قطعة', 'sale_price' => 50,
            ]);

        $this->actingAs($this->user)->get('/purchases/create')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('products', 1)
                ->where('products.0.name', 'صنف جديد')
                ->where('products.0.sku', 'P-0001')
            );
    }

    public function test_the_quick_add_can_attach_a_category(): void
    {
        $category = Category::create(['name' => 'زيوت']);

        $this->actingAs($this->user)->from('/purchases/create')
            ->post('/products/quick', [
                'name' => 'صنف', 'sku' => 'P-1',
                'unit' => 'قطعة', 'sale_price' => 50,
                'category_id' => $category->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($category->id, Product::sole()->category_id);
    }

    public function test_the_quick_add_validates_in_arabic(): void
    {
        $this->actingAs($this->user)->from('/purchases/create')
            ->post('/products/quick', ['name' => '', 'sku' => '', 'unit' => '', 'sale_price' => ''])
            ->assertSessionHasErrors([
                'name' => 'اكتب اسم الصنف.',
                'sku' => 'اكتب كود الصنف.',
                'sale_price' => 'اكتب سعر البيع.',
            ]);

        $this->assertSame(0, Product::count());
    }

    public function test_the_quick_add_rejects_a_duplicate_code(): void
    {
        Product::create([
            'sku' => 'P-1', 'name' => 'صنف قديم', 'unit' => 'قطعة',
            'purchase_price' => 0, 'sale_price' => 10, 'min_stock' => 0,
        ]);

        $this->actingAs($this->user)->from('/purchases/create')
            ->post('/products/quick', [
                'name' => 'صنف جديد', 'sku' => 'P-1',
                'unit' => 'قطعة', 'sale_price' => 20,
            ])
            ->assertSessionHasErrors([
                'sku' => 'الكود ده متسجّل على صنف تاني — اختار كود غيره.',
            ]);

        $this->assertSame(1, Product::count());
    }

    /**
     * على نظام فيه مخزن ومورد بس من غير أصناف، الفورم لازم يفتح —
     * مش يقول «لسه بدري» ويقفل الطريق على المستخدم.
     */
    public function test_the_form_opens_without_products_so_they_can_be_added_inline(): void
    {
        Warehouse::create(['name' => 'مخزن']);
        Supplier::create(['name' => 'مورد', 'currency' => 'USD']);

        $this->actingAs($this->user)
            ->get('/purchases/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchases/Create')
                ->has('warehouses', 1)
                ->has('suppliers', 1)
                ->has('products', 0)
            );
    }
}
