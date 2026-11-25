<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PreparationOrder;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * طلبات التحضير — الحجز، التحضير، الإلغاء، والتحويل لفاتورة.
 *
 * الحتة الحسّاسة هنا هي الحجز: الطلب المفتوح بيخصم من «المتاح» في
 * النظام كله من غير ما يعمل حركة مخزون. لو الحجز اتسرّب أو ماتفكّش في
 * وقته، البضاعة بتتباع مرتين أو بتفضل مقفولة على الفاضي.
 */
class PreparationOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Warehouse $warehouse;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);

        $category = Category::create(['name' => 'بقالة']);

        $this->customer = Customer::create([
            'name' => 'وليد عبد العظيم',
            'price_tier' => 'sale',
        ]);

        $this->product = Product::create([
            'sku' => 'KB-25', 'name' => 'كبولي مجوز', 'category_id' => $category->id,
            'unit' => 'جوز', 'purchase_price' => 100, 'sale_price' => 150,
            'wholesale_price' => 130, 'min_stock' => 5,
        ]);

        $this->receive($this->product, 100);
    }

    // ── الفتح والحجز ──────────────────────────────────────────────

    public function test_opening_an_order_reserves_the_quantities(): void
    {
        $this->actingAs($this->admin)
            ->post('/preparation-orders', $this->payload([
                ['product_id' => $this->product->id, 'quantity' => 30],
            ]))
            ->assertRedirect();

        $order = PreparationOrder::sole();

        $this->assertSame(PreparationOrder::STATUS_OPEN, $order->status);
        $this->assertSame('PR-'.Carbon::today()->year.'-00001', $order->number);

        $inventory = app(InventoryService::class);

        // الرصيد زي ما هو — مفيش حركة مخزون، البضاعة لسه على الرف
        $this->assertSame(100.0, $inventory->stockFor($this->product->id, $this->warehouse->id));

        // بس المتاح نزل
        $this->assertSame(30.0, $inventory->reservedFor($this->product->id, $this->warehouse->id));
        $this->assertSame(70.0, $inventory->availableFor($this->product->id, $this->warehouse->id));
    }

    public function test_an_order_cannot_reserve_more_than_is_available(): void
    {
        $this->actingAs($this->admin)
            ->post('/preparation-orders', $this->payload([
                ['product_id' => $this->product->id, 'quantity' => 101],
            ]))
            ->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(0, PreparationOrder::count());
    }

    /** تاجرين ماينفعش يتحجزلهم نفس الكرتونة */
    public function test_a_second_order_sees_the_first_orders_reservation(): void
    {
        $this->actingAs($this->admin)
            ->post('/preparation-orders', $this->payload([
                ['product_id' => $this->product->id, 'quantity' => 80],
            ]))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post('/preparation-orders', $this->payload([
                ['product_id' => $this->product->id, 'quantity' => 30],
            ]))
            ->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(1, PreparationOrder::count());
    }

    /**
     * نفس الصنف في أكتر من سطر بيتجمّع قبل فحص المتاح.
     *
     * من غير التجميع، سطرين كل واحد جوه المتاح لكن مجموعهم أكبر منه
     * كانوا هيعدّوا.
     */
    public function test_repeated_lines_are_summed_before_the_availability_check(): void
    {
        $this->actingAs($this->admin)
            ->post('/preparation-orders', $this->payload([
                ['product_id' => $this->product->id, 'quantity' => 60],
                ['product_id' => $this->product->id, 'quantity' => 60],
            ]))
            ->assertSessionHasErrors('items.0.quantity');
    }

    // ── المتاح في باقي النظام ─────────────────────────────────────

    /** منتقي الأصناف في فاتورة البيع لازم يشوف نفس المتاح */
    public function test_the_sale_invoice_picker_shows_availability_after_reservations(): void
    {
        $this->reserve(40);

        $this->actingAs($this->admin)
            ->get('/sales/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('products.0.stock.'.$this->warehouse->id, $this->number(60))
                ->where('products.0.reserved.'.$this->warehouse->id, $this->number(40))
            );
    }

    public function test_the_inventory_page_shows_reserved_and_available(): void
    {
        $this->reserve(40);

        $this->actingAs($this->admin)
            ->get('/inventory')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('rows.0.total', $this->number(100))
                ->where('rows.0.reserved', $this->number(40))
                ->where('rows.0.available', $this->number(60))
            );
    }

    /** الفاتورة بترفض لو المتاح مش كافي، حتى لو الرصيد كافي */
    public function test_a_sale_cannot_consume_reserved_stock(): void
    {
        $this->reserve(80);

        $this->actingAs($this->admin)
            ->post('/sales', [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'date' => Carbon::today()->toDateString(),
                'items' => [[
                    'product_id' => $this->product->id,
                    'quantity' => 30,
                    'unit_price' => 150,
                ]],
            ])
            ->assertSessionHasErrors('items.0.quantity');

        // والرسالة بتقول السبب — وإلا المستخدم بيبص على المخزن ويلاقي البضاعة
        $errors = session('errors')->get('items.0.quantity');

        $this->assertStringContainsString('محجوزة بطلبات تحضير', $errors[0]);
    }

    // ── التحضير ───────────────────────────────────────────────────

    public function test_the_warehouse_can_adjust_prepared_quantities(): void
    {
        $order = $this->reserve(30);

        $this->actingAs($this->admin)
            ->post("/preparation-orders/{$order->id}/prepare", [
                'items' => [[
                    'id' => $order->items->first()->id,
                    'prepared_quantity' => 25,
                ]],
            ])
            ->assertRedirect();

        $order->refresh();
        $item = $order->items->first();

        $this->assertSame(PreparationOrder::STATUS_PREPARED, $order->status);
        $this->assertNotNull($order->prepared_at);

        // المطلوب الأصلي بيفضل مكتوب — ده أول سؤال لما حد يختلف على كمية
        $this->assertSame(30.0, (float) $item->quantity);
        $this->assertSame(25.0, (float) $item->prepared_quantity);

        // والحجز بقى على المحضّر
        $this->assertSame(
            25.0,
            app(InventoryService::class)->reservedFor($this->product->id, $this->warehouse->id),
        );
    }

    /** المحضّر لسه بيحجز — التحضير مش تسليم */
    public function test_a_prepared_order_still_reserves(): void
    {
        $order = $this->reserve(30);

        $this->actingAs($this->admin)
            ->post("/preparation-orders/{$order->id}/prepare", [
                'items' => [['id' => $order->items->first()->id, 'prepared_quantity' => 30]],
            ])
            ->assertRedirect();

        $this->assertSame(
            70.0,
            app(InventoryService::class)->availableFor($this->product->id, $this->warehouse->id),
        );
    }

    public function test_the_warehouse_cannot_prepare_more_than_is_available(): void
    {
        $order = $this->reserve(30);

        // 100 في المخزن، 30 محجوزة للطلب ده — فأقصى حاجة ينفع يحضّرها 100
        $this->actingAs($this->admin)
            ->post("/preparation-orders/{$order->id}/prepare", [
                'items' => [['id' => $order->items->first()->id, 'prepared_quantity' => 120]],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(
            PreparationOrder::STATUS_OPEN,
            $order->fresh()->status,
        );
    }

    // ── الإلغاء ───────────────────────────────────────────────────

    public function test_cancelling_releases_the_reservation_without_touching_stock(): void
    {
        $order = $this->reserve(40);

        $this->actingAs($this->admin)
            ->post("/preparation-orders/{$order->id}/cancel")
            ->assertRedirect();

        $inventory = app(InventoryService::class);

        $this->assertSame(PreparationOrder::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(0.0, $inventory->reservedFor($this->product->id, $this->warehouse->id));
        $this->assertSame(100.0, $inventory->availableFor($this->product->id, $this->warehouse->id));

        // ولا حركة مخزون — الإلغاء مابيحركش بضاعة
        $this->assertSame(1, StockMovement::count());
    }

    public function test_a_cancelled_order_cannot_be_prepared_again(): void
    {
        $order = $this->reserve(40);

        $this->actingAs($this->admin)->post("/preparation-orders/{$order->id}/cancel");

        $this->actingAs($this->admin)
            ->post("/preparation-orders/{$order->id}/prepare", [
                'items' => [['id' => $order->items->first()->id, 'prepared_quantity' => 40]],
            ])
            ->assertSessionHasErrors('status');
    }

    // ── التحويل لفاتورة ───────────────────────────────────────────

    /** الفورم بيتملّي من الطلب، والأسعار من شريحة التاجر */
    public function test_the_convert_screen_prefills_the_sale_form(): void
    {
        $order = $this->reserve(20);

        $this->actingAs($this->admin)
            ->get("/preparation-orders/{$order->id}/convert")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                // نفس شاشة فاتورة البيع — مش نسخة تانية منها
                ->component('sales/Create')
                ->where('prefill.customerId', $this->customer->id)
                ->where('prefill.warehouseId', $this->warehouse->id)
                ->where('prefill.items.0.product_id', $this->product->id)
                ->where('prefill.items.0.quantity', $this->number(20))
                // شريحة التاجر تجزئة، فالسعر سعر البيع
                ->where('prefill.items.0.unit_price', $this->number(150))
            );
    }

    public function test_a_wholesale_trader_gets_the_wholesale_price(): void
    {
        $this->customer->update(['price_tier' => 'wholesale']);

        $order = $this->reserve(20);

        $this->actingAs($this->admin)
            ->get("/preparation-orders/{$order->id}/convert")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('prefill.items.0.unit_price', $this->number(130))
            );
    }

    public function test_saving_the_invoice_converts_the_order_and_releases_the_reservation(): void
    {
        $order = $this->reserve(20);

        $this->actingAs($this->admin)
            ->post('/sales', [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'date' => Carbon::today()->toDateString(),
                'preparation_order_id' => $order->id,
                'items' => [[
                    'product_id' => $this->product->id,
                    'quantity' => 20,
                    'unit_price' => 150,
                ]],
            ])
            ->assertRedirect();

        $order->refresh();
        $invoice = Invoice::query()->sales()->sole();

        $this->assertSame(PreparationOrder::STATUS_CONVERTED, $order->status);
        $this->assertSame($invoice->id, $order->invoice_id);

        $inventory = app(InventoryService::class);

        // الحجز اتفكّ، والبضاعة اتخصمت فعلاً دلوقتي
        $this->assertSame(0.0, $inventory->reservedFor($this->product->id, $this->warehouse->id));
        $this->assertSame(80.0, $inventory->stockFor($this->product->id, $this->warehouse->id));
        $this->assertSame(80.0, $inventory->availableFor($this->product->id, $this->warehouse->id));
    }

    /**
     * الفاتورة مالهاش تتمنع بحجز الطلب اللي هي نفسها بتنفّذه.
     *
     * طلب حاجز كل الرصيد لازم يتحوّل لفاتورة عادي — لو الفحص ماستثناش
     * حجز الطلب ده، المتاح كان هيبان صفر والتحويل ما كانش هيعدّي أبدًا.
     */
    public function test_an_order_reserving_everything_can_still_be_invoiced(): void
    {
        $order = $this->reserve(100);

        $this->actingAs($this->admin)
            ->post('/sales', [
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'date' => Carbon::today()->toDateString(),
                'preparation_order_id' => $order->id,
                'items' => [[
                    'product_id' => $this->product->id,
                    'quantity' => 100,
                    'unit_price' => 150,
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(PreparationOrder::STATUS_CONVERTED, $order->fresh()->status);
    }

    public function test_an_order_cannot_be_converted_twice(): void
    {
        $order = $this->reserve(20);

        $payload = [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->toDateString(),
            'preparation_order_id' => $order->id,
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 20,
                'unit_price' => 150,
            ]],
        ];

        $this->actingAs($this->admin)->post('/sales', $payload)->assertRedirect();

        $this->actingAs($this->admin)
            ->post('/sales', $payload)
            ->assertSessionHasErrors('preparation_order_id');

        $this->assertSame(1, Invoice::query()->sales()->count());
    }

    /** الطلب المحوّل مابقاش ينفع يتفتح على شاشة التحويل */
    public function test_a_converted_order_cannot_be_converted_again_from_the_screen(): void
    {
        $order = $this->reserve(20);
        $order->update(['status' => PreparationOrder::STATUS_CONVERTED]);

        $this->actingAs($this->admin)
            ->get("/preparation-orders/{$order->id}/convert")
            ->assertNotFound();
    }

    // ── الطباعة والـ PDF والواتساب ────────────────────────────────

    public function test_the_picking_sheet_renders_without_prices(): void
    {
        $order = $this->reserve(20);

        $this->actingAs($this->admin)
            ->get("/preparation-orders/{$order->id}/print")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('print/PreparationOrder')
                ->has('order.items', 1)
                // الورقة بتروح للمخزن — مفيش سعر فيها
                ->missing('order.items.0.unitPrice')
                ->missing('order.items.0.total')
            );
    }

    public function test_the_pdf_renders_as_a_real_pdf(): void
    {
        $order = $this->reserve(20);

        $response = $this->actingAs($this->admin)
            ->get(URL::signedRoute('preparation-orders.pdf', ['preparationOrder' => $order->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());

        // الرقم العربي بيوصل لاسم الملف
        $this->assertStringContainsString(
            rawurlencode('طلب تحضير '.$order->number),
            $response->headers->get('content-disposition'),
        );
    }

    /**
     * اللينك الموقّع بيفتح من غير تسجيل دخول — ده اللي بيخلّي مشاركة
     * الواتساب تشتغل من غير أي API.
     */
    public function test_the_signed_pdf_link_opens_without_logging_in(): void
    {
        $order = $this->reserve(20);

        $this->get(URL::signedRoute('preparation-orders.pdf', ['preparationOrder' => $order->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_the_pdf_link_is_refused_without_a_valid_signature(): void
    {
        $order = $this->reserve(20);

        $this->get("/preparation-orders/{$order->id}/pdf")->assertForbidden();

        // وتوقيع مظبوط بس اتعدّل فيه حرف برضه بيترفض
        $tampered = URL::signedRoute('preparation-orders.pdf', ['preparationOrder' => $order->id]).'x';

        $this->get($tampered)->assertForbidden();
    }

    /** الصفحة بتبعت لينك موقّع لزرار الواتساب */
    public function test_the_order_page_carries_a_signed_pdf_link(): void
    {
        $order = $this->reserve(20);

        $this->actingAs($this->admin)
            ->get("/preparation-orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('preparation-orders/Show')
                ->where('pdfUrl', fn (string $url) => str_contains($url, 'signature='))
            );
    }

    // ── مفيش أسعار ────────────────────────────────────────────────

    /**
     * الكتالوج مشترك مع فاتورة البيع وفيه ٣ أعمدة أسعار — فبنشيلها
     * على السيرفر، مش بنعتمد على إن الواجهة مش هتعرضها.
     */
    public function test_the_create_screen_gets_no_prices_at_all(): void
    {
        $this->actingAs($this->admin)
            ->get('/preparation-orders/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('preparation-orders/Create')
                ->has('products.0')
                ->missing('products.0.salePrice')
                ->missing('products.0.wholesalePrice')
                ->missing('products.0.purchasePrice')
            );
    }

    public function test_the_order_page_shows_no_prices(): void
    {
        $order = $this->reserve(20);

        $this->actingAs($this->admin)
            ->get("/preparation-orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->missing('order.items.0.unitPrice')
                ->missing('order.total')
            );
    }

    // ── الصلاحيات ─────────────────────────────────────────────────

    /** المبيعات بيفتحوا طلبات تحضير — الطلب بيتفتح وهو واقف مع التاجر */
    public function test_a_sales_user_can_create_an_order(): void
    {
        $this->actingAs($this->salesUser())
            ->post('/preparation-orders', $this->payload([
                ['product_id' => $this->product->id, 'quantity' => 10],
            ]))
            ->assertRedirect();

        $this->assertSame(1, PreparationOrder::count());
    }

    public function test_a_sales_user_sees_the_list(): void
    {
        $this->actingAs($this->salesUser())
            ->get('/preparation-orders')
            ->assertOk();
    }

    public function test_an_accountant_can_create_an_order(): void
    {
        $this->actingAs($this->accountant())
            ->post('/preparation-orders', $this->payload([
                ['product_id' => $this->product->id, 'quantity' => 10],
            ]))
            ->assertRedirect();
    }

    public function test_a_user_without_a_role_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/preparation-orders')
            ->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/preparation-orders')->assertRedirect('/login');
    }

    // ── سجل النشاط ────────────────────────────────────────────────

    public function test_the_lifecycle_is_written_to_the_activity_log(): void
    {
        $order = $this->reserve(20);

        $this->actingAs($this->admin)
            ->post("/preparation-orders/{$order->id}/prepare", [
                'items' => [['id' => $order->items->first()->id, 'prepared_quantity' => 18]],
            ])
            ->assertRedirect();

        $events = Activity::query()
            ->where('log_name', 'preparation_order')
            ->pluck('event')
            ->all();

        $this->assertContains('created', $events);
        $this->assertContains('prepared', $events);

        // التعديل نفسه بيتسجّل بالاسم والكمية عشان السجل يبقى مقروء
        $prepared = Activity::query()
            ->where('log_name', 'preparation_order')
            ->where('event', 'prepared')
            ->sole();

        $this->assertSame(18.0, (float) $prepared->properties['adjusted']['كبولي مجوز']);
    }

    public function test_converting_is_logged_with_the_invoice_number(): void
    {
        $order = $this->reserve(20);

        $this->actingAs($this->admin)->post('/sales', [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->toDateString(),
            'preparation_order_id' => $order->id,
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 20,
                'unit_price' => 150,
            ]],
        ]);

        $activity = Activity::query()
            ->where('log_name', 'preparation_order')
            ->where('event', 'converted')
            ->sole();

        $this->assertSame(
            Invoice::query()->sales()->value('number'),
            $activity->properties['invoice'],
        );
    }

    public function test_cancelling_is_logged(): void
    {
        $order = $this->reserve(20);

        $this->actingAs($this->admin)->post("/preparation-orders/{$order->id}/cancel");

        $this->assertSame(1, Activity::query()
            ->where('log_name', 'preparation_order')
            ->where('event', 'cancelled')
            ->count());
    }

    // ── القائمة ───────────────────────────────────────────────────

    public function test_the_list_can_be_filtered_by_status(): void
    {
        $open = $this->reserve(10);
        $cancelled = $this->reserve(10);

        $this->actingAs($this->admin)->post("/preparation-orders/{$cancelled->id}/cancel");

        $this->actingAs($this->admin)
            ->get('/preparation-orders?status=open')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.number', $open->number)
            );
    }

    public function test_the_list_can_be_searched_by_order_number(): void
    {
        $order = $this->reserve(10);
        $this->reserve(10);

        $this->actingAs($this->admin)
            ->get('/preparation-orders?search='.$order->number)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.number', $order->number)
            );
    }

    // ── مساعدات ───────────────────────────────────────────────────

    /**
     * مقارنة رقمية مع خرج Inertia.
     *
     * الـ JSON بيرجّع 60.0 كـ 60 صحيح، و`where` بيقارن بـ `===` —
     * فالمقارنة المباشرة بتقع على فرق نوع مالوش أي معنى هنا.
     */
    private function number(float $expected): \Closure
    {
        return fn (mixed $value): bool => abs((float) $value - $expected) < 1e-6;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function payload(array $items): array
    {
        return [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->toDateString(),
            'items' => $items,
        ];
    }

    /** طلب مفتوح بكمية معيّنة */
    private function reserve(float $quantity): PreparationOrder
    {
        $this->actingAs($this->admin)
            ->post('/preparation-orders', $this->payload([
                ['product_id' => $this->product->id, 'quantity' => $quantity],
            ]))
            ->assertSessionHasNoErrors();

        return PreparationOrder::query()->latest('id')->firstOrFail()->load('items');
    }

    private function receive(Product $product, float $quantity): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => $quantity,
            'unit_cost' => $product->purchase_price,
            'date' => Carbon::today()->subDays(10)->toDateString(),
        ]);
    }
}
