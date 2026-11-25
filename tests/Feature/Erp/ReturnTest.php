<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesRep;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CustomerService;
use App\Services\InventoryService;
use App\Services\ReturnService;
use App\Services\SalesRepService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * المرتجعات — بيع وشراء.
 *
 * الحتة الحسّاسة هنا إن المرتجع بيلمس أربع حاجات مع بعض: المخزون،
 * ورصيد الطرف، وعمولة المندوب، والخزينة. لو واحدة فيهم اتظبطت والتانية
 * لأ، الأرقام بتفرق عن بعض والفرق بيظهر بعد شهر لما حد يراجع.
 */
class ReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Supplier $supplier;

    private Warehouse $warehouse;

    private Product $product;

    private SalesRep $rep;

    private Invoice $sale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);

        $category = Category::create(['name' => 'بقالة']);
        $this->rep = SalesRep::create(['name' => 'سامي المندوب', 'commission_rate' => 10]);

        $this->customer = Customer::create([
            'name' => 'وليد عبد العظيم',
            'price_tier' => 'sale',
            'sales_rep_id' => $this->rep->id,
        ]);

        $this->supplier = Supplier::create(['name' => 'شركة النور', 'currency' => 'EGP']);

        $this->product = Product::create([
            'sku' => 'KB-25', 'name' => 'كبولي مجوز', 'category_id' => $category->id,
            'unit' => 'جوز', 'purchase_price' => 100, 'sale_price' => 150, 'min_stock' => 0,
        ]);

        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 100,
            'unit_cost' => 100,
            'date' => Carbon::today()->subDays(20)->toDateString(),
        ]);

        // فاتورة بيع 10 × 150 = 1,500 + ضريبة
        $this->sale = $this->makeSale(10, 150);
    }

    // ── مرتجع البيع ───────────────────────────────────────────────

    public function test_a_sale_return_brings_stock_back(): void
    {
        $before = app(InventoryService::class)->stockFor($this->product->id, $this->warehouse->id);

        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 4],
        ])->assertRedirect();

        $after = app(InventoryService::class)->stockFor($this->product->id, $this->warehouse->id);

        $this->assertSame($before + 4, $after);
    }

    /**
     * البضاعة بترجع **بتكلفة الفاتورة الأصلية** مش بالتكلفة الحالية.
     *
     * لو رجّعناها بتكلفة النهارده، قيمة المخزون بتتغيّر من غير ما يكون
     * فيه بيع ولا شرا، والفرق بيظهر في تقييم المخزون كربح وهمي.
     */
    public function test_the_return_uses_the_cost_from_the_original_invoice(): void
    {
        // التكلفة اتغيّرت بعد البيع
        $this->product->update(['purchase_price' => 180]);

        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 4],
        ])->assertRedirect();

        $return = Invoice::query()->where('type', Invoice::TYPE_SALE_RETURN)->sole();

        $movement = StockMovement::query()
            ->where('reference_type', Invoice::class)
            ->where('reference_id', $return->id)
            ->sole();

        // 100 زي الفاتورة الأصلية، مش 180
        $this->assertSame(100.0, (float) $movement->unit_cost);
    }

    public function test_a_sale_return_lowers_the_trader_balance(): void
    {
        $before = app(CustomerService::class)->balanceFor($this->customer);

        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ])->assertRedirect();

        $return = Invoice::query()->where('type', Invoice::TYPE_SALE_RETURN)->sole();
        $after = app(CustomerService::class)->balanceFor($this->customer->fresh());

        $this->assertSame(round($before - (float) $return->total, 2), round($after, 2));
    }

    /** السقف = المباع ناقص المرتجع قبل كده */
    public function test_the_returnable_quantity_drops_after_a_partial_return(): void
    {
        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 4],
        ])->assertRedirect();

        $lines = app(ReturnService::class)->returnableLines($this->sale->fresh());

        $this->assertSame(10.0, $lines[0]['sold']);
        $this->assertSame(4.0, $lines[0]['returned']);
        $this->assertSame(6.0, $lines[0]['returnable']);
    }

    /**
     * ماينفعش ترجّع نفس الكرتونة مرتين.
     *
     * من غير الطرح ده، المخزون بيطلع أكتر من اللي اتشحن أصلاً.
     */
    public function test_returning_more_than_was_sold_is_refused(): void
    {
        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 7],
        ])->assertRedirect();

        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 7],
        ])->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(1, Invoice::query()->where('type', Invoice::TYPE_SALE_RETURN)->count());
    }

    public function test_a_product_not_on_the_invoice_is_refused(): void
    {
        $other = Product::create([
            'sku' => 'FR-01', 'name' => 'الفاروق فضي', 'unit' => 'قطعة',
            'purchase_price' => 50, 'sale_price' => 80, 'min_stock' => 0,
        ]);

        $this->postReturn($this->sale, [
            ['product_id' => $other->id, 'quantity' => 1],
        ])->assertSessionHasErrors('items.0.product_id');
    }

    public function test_a_return_cannot_be_made_on_a_return(): void
    {
        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 2],
        ])->assertRedirect();

        $return = Invoice::query()->where('type', Invoice::TYPE_SALE_RETURN)->sole();

        $this->actingAs($this->admin)
            ->get("/invoices/{$return->id}/return")
            ->assertNotFound();
    }

    // ── الرد النقدي والخزينة والعمولة ─────────────────────────────

    /** الرد النقدي بيطلّع فلوس من الخزينة */
    public function test_a_cash_refund_is_recorded_in_the_treasury(): void
    {
        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ], ['refund' => ['amount' => 500, 'method' => 'cash']])->assertRedirect();

        $payment = Payment::query()->where('type', Payment::TYPE_DISBURSEMENT)->sole();

        $this->assertSame(500.0, (float) $payment->amount);
        $this->assertSame($this->customer->id, $payment->customer_id);
        // منسوب لنفس المندوب عشان العمولة ترجع عليه
        $this->assertSame($this->rep->id, $payment->sales_rep_id);
    }

    public function test_a_refund_bigger_than_the_return_is_refused(): void
    {
        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 1],
        ], ['refund' => ['amount' => 99_999, 'method' => 'cash']])
            ->assertSessionHasErrors('refund.amount');

        $this->assertSame(0, Invoice::query()->where('type', Invoice::TYPE_SALE_RETURN)->count());
        // ولا حركة مخزون — المعاملة رجعت كلها
        $this->assertSame(2, StockMovement::count());
    }

    /**
     * العمولة بتتحسب على المحصّل، فالرد النقدي بيقلّلها.
     *
     * التاجر دفع 1,000 والمرتجع رجّعله 400 → المحصّل الصافي 600،
     * والعمولة 10% منهم = 60 بدل 100.
     */
    public function test_the_refund_reverses_the_reps_commission(): void
    {
        Payment::create([
            'type' => Payment::TYPE_RECEIPT,
            'customer_id' => $this->customer->id,
            'invoice_id' => $this->sale->id,
            'amount' => 1_000,
            'method' => 'cash',
            'date' => Carbon::today()->toDateString(),
            'user_id' => $this->admin->id,
            'sales_rep_id' => $this->rep->id,
        ]);

        $before = app(SalesRepService::class)->summary($this->rep);

        $this->assertSame(100.0, $before['commission']);

        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 3],
        ], ['refund' => ['amount' => 400, 'method' => 'cash']])->assertRedirect();

        $after = app(SalesRepService::class)->summary($this->rep->fresh());

        $this->assertSame(60.0, $after['commission']);
    }

    /** والمرتجع من غير فلوس بيقلّل صافي المبيعات */
    public function test_a_return_lowers_the_reps_net_sales(): void
    {
        $before = app(SalesRepService::class)->summary($this->rep);

        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ])->assertRedirect();

        $after = app(SalesRepService::class)->summary($this->rep->fresh());

        $this->assertGreaterThan(0, $before['netSales']);
        $this->assertSame(0.0, $after['netSales']);
    }

    // ── مرتجع الشراء ──────────────────────────────────────────────

    public function test_a_purchase_return_takes_stock_out(): void
    {
        $purchase = $this->makePurchase(20, 100);

        $before = app(InventoryService::class)->stockFor($this->product->id, $this->warehouse->id);

        $this->postReturn($purchase, [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ])->assertRedirect();

        $after = app(InventoryService::class)->stockFor($this->product->id, $this->warehouse->id);

        $this->assertSame($before - 5, $after);
    }

    /** المورد بيرد فلوس — فالرد بيدخل الخزينة مش بيطلع منها */
    public function test_a_purchase_return_refund_comes_into_the_treasury(): void
    {
        $purchase = $this->makePurchase(20, 100);

        $this->postReturn($purchase, [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ], ['refund' => ['amount' => 500, 'method' => 'bank']])->assertRedirect();

        $payment = Payment::query()->where('supplier_id', $this->supplier->id)->sole();

        $this->assertSame(Payment::TYPE_RECEIPT, $payment->type);
        $this->assertSame(500.0, (float) $payment->amount);
    }

    /** مرتجع شراء أكبر من الرصيد بيترفض — المخزن ماينفعش يبقى بالسالب */
    public function test_a_purchase_return_cannot_push_stock_negative(): void
    {
        $purchase = $this->makePurchase(20, 100);

        // الرصيد 110 (100 وارد + 20 شرا − 10 بيع)؛ مرتجع 200 هيوديه بالسالب
        Invoice::query()->where('id', $purchase->id)->first()->items()->update(['quantity' => 200]);

        $this->postReturn($purchase->fresh(), [
            ['product_id' => $this->product->id, 'quantity' => 200],
        ])->assertSessionHasErrors();

        $this->assertSame(0, Invoice::query()->where('type', Invoice::TYPE_PURCHASE_RETURN)->count());
    }

    // ── المرتجع من غير فاتورة ─────────────────────────────────────

    public function test_an_admin_can_record_a_return_without_an_invoice(): void
    {
        $this->actingAs($this->admin)
            ->post('/returns/orphan', [
                'type' => Invoice::TYPE_SALE_RETURN,
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'date' => Carbon::today()->toDateString(),
                'return_reason' => 'damaged',
                'items' => [[
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'unit_price' => 150,
                ]],
            ])
            ->assertRedirect();

        $return = Invoice::query()->where('type', Invoice::TYPE_SALE_RETURN)->sole();

        $this->assertTrue($return->is_orphan_return);
        $this->assertNull($return->original_invoice_id);
        $this->assertSame(450.0, (float) $return->subtotal);
    }

    public function test_a_non_admin_cannot_record_an_orphan_return(): void
    {
        foreach ([$this->accountant(), $this->salesUser()] as $user) {
            $this->actingAs($user)
                ->get('/returns/orphan')
                ->assertForbidden();
        }
    }

    // ── العرض والقوايم ────────────────────────────────────────────

    public function test_the_original_invoice_shows_its_returns(): void
    {
        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 2],
        ])->assertRedirect();

        $return = Invoice::query()->where('type', Invoice::TYPE_SALE_RETURN)->sole();

        $this->actingAs($this->admin)
            ->get("/sales/{$this->sale->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('invoice.returns', 1)
                ->where('invoice.returns.0.number', $return->number)
            );
    }

    public function test_the_return_screens_render(): void
    {
        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 2],
        ])->assertRedirect();

        $return = Invoice::query()->where('type', Invoice::TYPE_SALE_RETURN)->sole();

        $this->actingAs($this->admin)->get('/returns')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('returns/Index')
                ->has('returns.data', 1)
            );

        $this->actingAs($this->admin)->get("/returns/{$return->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('returns/Show'));

        $this->actingAs($this->admin)->get("/returns/{$return->id}/print")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('print/Return'));
    }

    public function test_the_return_list_downloads(): void
    {
        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 2],
        ])->assertRedirect();

        $this->actingAs($this->admin)
            ->get('/exports/returns')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->admin)
            ->get('/exports/returns?format=excel')
            ->assertOk();
    }

    // ── إشعار الخصم ───────────────────────────────────────────────

    /** المرتجع بيتبعت للمنظومة كإشعار خصم مش كفاتورة */
    public function test_a_sale_return_is_sent_as_a_credit_note(): void
    {
        $this->actingAs($this->admin)->post("/sales/{$this->sale->id}/eta");

        $this->postReturn($this->sale->fresh(), [
            ['product_id' => $this->product->id, 'quantity' => 2],
        ])->assertRedirect();

        $return = Invoice::query()->where('type', Invoice::TYPE_SALE_RETURN)->sole();

        $this->actingAs($this->admin)
            ->post("/returns/{$return->id}/eta")
            ->assertRedirect();

        $this->assertSame(Invoice::ETA_SENT, $return->fresh()->eta_status);

        $activity = Activity::query()->where('event', 'eta_sent')->latest('id')->firstOrFail();

        $this->assertSame('إشعار خصم', $activity->properties['document']);
    }

    /** إشعار الخصم محتاج الفاتورة الأصلية تكون متبعتة الأول */
    public function test_a_credit_note_needs_the_original_sent_first(): void
    {
        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 2],
        ])->assertRedirect();

        $return = Invoice::query()->where('type', Invoice::TYPE_SALE_RETURN)->sole();

        $this->actingAs($this->admin)
            ->post("/returns/{$return->id}/eta")
            ->assertSessionHasErrors('eta');
    }

    // ── سجل النشاط ────────────────────────────────────────────────

    public function test_the_return_is_written_to_the_activity_log(): void
    {
        $this->postReturn($this->sale, [
            ['product_id' => $this->product->id, 'quantity' => 2],
        ], ['refund' => ['amount' => 100, 'method' => 'cash']])->assertRedirect();

        $activity = Activity::query()
            ->where('log_name', 'invoice')
            ->where('event', 'returned')
            ->sole();

        $this->assertSame($this->sale->number, $activity->properties['original']);
        $this->assertSame('بضاعة تالفة', $activity->properties['reason']);
        $this->assertSame(100.0, (float) $activity->properties['refund']);
    }

    // ── مساعدات ───────────────────────────────────────────────────

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $extra
     */
    private function postReturn(Invoice $invoice, array $items, array $extra = [])
    {
        return $this->actingAs($this->admin)->post("/invoices/{$invoice->id}/return", array_merge([
            'date' => Carbon::today()->toDateString(),
            'return_reason' => 'damaged',
            'items' => $items,
        ], $extra));
    }

    private function makeSale(float $quantity, float $price): Invoice
    {
        $this->actingAs($this->admin)->post('/sales', [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->subDays(2)->toDateString(),
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => $quantity,
                'unit_price' => $price,
            ]],
        ])->assertSessionHasNoErrors();

        return Invoice::query()->sales()->latest('id')->firstOrFail();
    }

    private function makePurchase(float $quantity, float $price): Invoice
    {
        $this->actingAs($this->admin)->post('/purchases', [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->subDay()->toDateString(),
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => $quantity,
                'unit_price' => $price,
            ]],
        ])->assertSessionHasNoErrors();

        return Invoice::query()->purchases()->latest('id')->firstOrFail();
    }
}
