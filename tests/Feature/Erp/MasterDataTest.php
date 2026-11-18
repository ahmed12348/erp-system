<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesRep;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * كل البيانات الأساسية بتتسجّل من الواجهة.
 *
 * ده الاختبار اللي بيضمن إن عميل جديد يقدر يبدأ من نظام فاضي: لكل نوع
 * بيانات أساسية فيه إضافة وتعديل وإيقاف من الشاشة، ومفيش حاجة محتاجة
 * سيدر ولا تدخّل في قاعدة البيانات.
 */
class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->admin();
    }

    /**
     * كل شاشة بيانات أساسية ومسارها — بنعدّي عليهم واحد واحد.
     *
     * @return array<string, array{string, string, array<string, mixed>, class-string}>
     */
    public static function entities(): array
    {
        return [
            'المخازن' => ['/warehouses', 'warehouses/Index', [
                'name' => 'المخزن الرئيسي',
                'location' => 'العبور',
                'is_active' => true,
            ], Warehouse::class],

            'التصنيفات' => ['/categories', 'categories/Index', [
                'name' => 'زيوت ومنظفات',
                'is_active' => true,
            ], Category::class],

            'الأصناف' => ['/products', 'products/Index', [
                'name' => 'زيت عباد شمس 1 لتر',
                'sku' => 'P-0001',
                'unit' => 'قطعة',
                'sale_price' => 95,
                'wholesale_price' => 90,
                'min_stock' => 24,
                'is_active' => true,
            ], Product::class],

            'الموردون' => ['/suppliers', 'suppliers/Index', [
                'name' => 'شركة الأناضول',
                'country' => 'تركيا',
                'currency' => 'USD',
                'phone' => '+905320000000',
                'is_active' => true,
            ], Supplier::class],

            'التجار' => ['/customers', 'customers/Index', [
                'name' => 'محمد عبد الله للتجارة',
                'city' => 'الدقهلية',
                'phone' => '01012345678',
                'price_tier' => 'wholesale',
                'credit_limit' => 50000,
                'is_active' => true,
            ], Customer::class],

            'المندوبين' => ['/reps', 'reps/Index', [
                'name' => 'كريم سعيد',
                'region' => 'الدلتا',
                'phone' => '01112345678',
                'commission_rate' => 2.5,
                'is_active' => true,
            ], SalesRep::class],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  class-string<Model>  $model
     */
    #[DataProvider('entities')]
    public function test_each_master_data_screen_opens_empty_and_accepts_the_first_record(
        string $url,
        string $component,
        array $payload,
        string $model,
    ): void {
        $this->assertSame(0, $model::count(), "{$url} المفروض يبدأ فاضي");

        $this->actingAs($this->user)
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component($component));

        $this->actingAs($this->user)
            ->from($url)
            ->post($url, $payload)
            ->assertRedirect($url)
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $model::count(), "{$url} المفروض يسجّل السجل الأول");
        $this->assertSame($payload['name'], $model::sole()->name);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  class-string<Model>  $model
     */
    #[DataProvider('entities')]
    public function test_each_master_data_record_can_be_edited_and_switched_off(
        string $url,
        string $component,
        array $payload,
        string $model,
    ): void {
        $this->actingAs($this->user)->from($url)->post($url, $payload)->assertSessionHasNoErrors();

        $record = $model::sole();

        $this->actingAs($this->user)
            ->from($url)
            ->put("{$url}/{$record->id}", [...$payload, 'name' => 'اسم بعد التعديل', 'is_active' => false])
            ->assertRedirect($url)
            ->assertSessionHasNoErrors();

        $record->refresh();

        $this->assertSame('اسم بعد التعديل', $record->name);
        $this->assertFalse((bool) $record->is_active, "{$url} المفروض يقدر يتوقّف");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  class-string<Model>  $model
     */
    #[DataProvider('entities')]
    public function test_a_clean_master_data_record_can_be_deleted(
        string $url,
        string $component,
        array $payload,
        string $model,
    ): void {
        $this->actingAs($this->user)->from($url)->post($url, $payload)->assertSessionHasNoErrors();

        $record = $model::sole();

        $this->actingAs($this->user)
            ->from($url)
            ->delete("{$url}/{$record->id}")
            ->assertRedirect($url)
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $model::count());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('entities')]
    public function test_the_name_is_required_with_an_arabic_message(
        string $url,
        string $component,
        array $payload,
    ): void {
        $this->actingAs($this->user)
            ->from($url)
            ->post($url, [...$payload, 'name' => ''])
            ->assertSessionHasErrors('name');

        $message = session('errors')->first('name');

        $this->assertMatchesRegularExpression(
            '/^[\x{0600}-\x{06FF}\s]/u',
            $message,
            "رسالة الخطأ في {$url} المفروض تكون بالعربي، لقينا: {$message}",
        );
    }

    /** الاسم المكرّر بيترفض برسالة عربية واضحة */
    public function test_duplicate_names_are_rejected_in_arabic(): void
    {
        $this->actingAs($this->user)->from('/categories')
            ->post('/categories', ['name' => 'زيوت']);

        $this->actingAs($this->user)->from('/categories')
            ->post('/categories', ['name' => 'زيوت'])
            ->assertSessionHasErrors(['name' => 'في تصنيف بالاسم ده بالفعل.']);

        $this->assertSame(1, Category::count());
    }

    /** كود الصنف لازم يفضل فريد */
    public function test_product_codes_must_stay_unique(): void
    {
        $payload = [
            'name' => 'صنف أول', 'sku' => 'P-0001',
            'unit' => 'قطعة', 'sale_price' => 10,
        ];

        $this->actingAs($this->user)->from('/products')->post('/products', $payload);

        $this->actingAs($this->user)->from('/products')
            ->post('/products', [...$payload, 'name' => 'صنف تاني'])
            ->assertSessionHasErrors([
                'sku' => 'الكود ده متسجّل على صنف تاني — اختار كود غيره.',
            ]);

        $this->assertSame(1, Product::count());
    }

    /** الصنف اللي عليه حركة ما يتحذفش — الأرصدة التاريخية هتبوظ */
    public function test_a_product_with_movements_cannot_be_deleted(): void
    {
        $warehouse = Warehouse::create(['name' => 'مخزن']);

        $product = Product::create([
            'sku' => 'P-1', 'name' => 'صنف', 'unit' => 'قطعة',
            'purchase_price' => 10, 'sale_price' => 20, 'min_stock' => 0,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'unit_cost' => 10,
            'type' => 'purchase',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)->from('/products')
            ->delete("/products/{$product->id}")
            ->assertSessionHasErrors('product');

        $this->assertStringContainsString('وقّفه بدل ما تحذفه', session('errors')->first('product'));
        $this->assertSame(1, Product::count());
    }

    /** التصنيف اللي عليه أصناف ما يتحذفش */
    public function test_a_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::create(['name' => 'زيوت']);

        Product::create([
            'category_id' => $category->id,
            'sku' => 'P-1', 'name' => 'صنف', 'unit' => 'قطعة',
            'purchase_price' => 10, 'sale_price' => 20, 'min_stock' => 0,
        ]);

        $this->actingAs($this->user)->from('/categories')
            ->delete("/categories/{$category->id}")
            ->assertSessionHasErrors('category');

        $this->assertSame(1, Category::count());
    }

    /** المورد اللي عليه فواتير ما يتحذفش */
    public function test_a_supplier_with_invoices_cannot_be_deleted(): void
    {
        $supplier = Supplier::create(['name' => 'مورد', 'currency' => 'USD']);
        $warehouse = Warehouse::create(['name' => 'مخزن']);

        Invoice::create([
            'type' => Invoice::TYPE_PURCHASE,
            'number' => 'PUR-1',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'currency' => 'USD',
            'exchange_rate' => 49,
            'subtotal' => 100,
            'total' => 100,
            'status' => Invoice::STATUS_CONFIRMED,
        ]);

        $this->actingAs($this->user)->from('/suppliers')
            ->delete("/suppliers/{$supplier->id}")
            ->assertSessionHasErrors('supplier');

        $this->assertSame(1, Supplier::count());
    }

    /** المندوب اللي عليه تجار ما يتحذفش */
    public function test_a_rep_with_customers_cannot_be_deleted(): void
    {
        $rep = SalesRep::create(['name' => 'مندوب', 'commission_rate' => 2]);

        Customer::create(['name' => 'تاجر', 'sales_rep_id' => $rep->id]);

        $this->actingAs($this->user)->from('/reps')
            ->delete("/reps/{$rep->id}")
            ->assertSessionHasErrors('rep');

        $this->assertSame(1, SalesRep::count());
    }

    /** التاجر الموقوف ما يظهرش في فاتورة بيع جديدة */
    public function test_switched_off_records_disappear_from_the_invoice_forms(): void
    {
        Warehouse::create(['name' => 'مخزن']);

        Customer::create(['name' => 'تاجر شغّال', 'is_active' => true]);
        Customer::create(['name' => 'تاجر موقوف', 'is_active' => false]);

        Supplier::create(['name' => 'مورد شغّال', 'currency' => 'USD', 'is_active' => true]);
        Supplier::create(['name' => 'مورد موقوف', 'currency' => 'USD', 'is_active' => false]);

        $this->actingAs($this->user)->get('/sales/create')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('customers', 1)
                ->where('customers.0.name', 'تاجر شغّال')
            );

        $this->actingAs($this->user)->get('/purchases/create')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('suppliers', 1)
                ->where('suppliers.0.name', 'مورد شغّال')
            );
    }

    /** التاجر بيتربط بمندوب من الفورم */
    public function test_a_trader_can_be_linked_to_a_sales_rep(): void
    {
        $rep = SalesRep::create(['name' => 'كريم', 'commission_rate' => 2]);

        $this->actingAs($this->user)->from('/customers')
            ->post('/customers', [
                'name' => 'تاجر',
                'price_tier' => 'sale',
                'sales_rep_id' => $rep->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($rep->id, Customer::sole()->sales_rep_id);
    }

    /** حد المديونية الفاضي معناه «بدون حد» مش صفر */
    public function test_an_empty_credit_limit_means_no_limit(): void
    {
        $this->actingAs($this->user)->from('/customers')
            ->post('/customers', [
                'name' => 'تاجر',
                'price_tier' => 'sale',
                'credit_limit' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull(Customer::sole()->credit_limit);
    }

    /** سعر الجملة الفاضي معناه إن التاجر بياخد سعر البيع العادي */
    public function test_an_empty_wholesale_price_falls_back_to_the_sale_price(): void
    {
        $this->actingAs($this->user)->from('/products')
            ->post('/products', [
                'name' => 'صنف', 'sku' => 'P-1',
                'unit' => 'قطعة', 'sale_price' => 100,
                'wholesale_price' => '',
            ])
            ->assertSessionHasNoErrors();

        $product = Product::sole();
        $customer = Customer::create(['name' => 'تاجر', 'price_tier' => 'wholesale']);

        $this->assertNull($product->wholesale_price);
        $this->assertSame(100.0, $product->priceFor($customer));
    }

    /** الكود المقترح بيعدّ لحد الصنف الجاي */
    public function test_the_suggested_product_code_increments(): void
    {
        $this->actingAs($this->user)->get('/products')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('suggestedSku', 'P-0001'));

        Product::create([
            'sku' => 'P-0001', 'name' => 'صنف', 'unit' => 'قطعة',
            'purchase_price' => 0, 'sale_price' => 1, 'min_stock' => 0,
        ]);

        $this->actingAs($this->user)->get('/products')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('suggestedSku', 'P-0002'));
    }

    /** مفيش حاجة من دي تتعمل من غير تسجيل دخول */
    public function test_guests_cannot_reach_any_master_data_screen(): void
    {
        foreach (['/warehouses', '/categories', '/products', '/suppliers', '/customers', '/reps', '/users'] as $url) {
            $this->get($url)->assertRedirect('/login');
            $this->post($url, [])->assertRedirect('/login');
        }
    }
}
