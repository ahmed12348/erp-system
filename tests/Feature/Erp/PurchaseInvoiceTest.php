<?php

namespace Tests\Feature\Erp;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\SupplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PurchaseInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Warehouse $warehouse;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $this->supplier = Supplier::create([
            'name' => 'مورد صيني',
            'country' => 'الصين',
            'currency' => 'USD',
        ]);
    }

    public function test_a_purchase_adds_positive_stock_and_stores_the_currency(): void
    {
        $product = $this->product();

        $this->actingAs($this->user)
            ->post('/purchases', $this->payload([
                ['product_id' => $product->id, 'quantity' => 100, 'unit_price' => 10],
            ], ['exchange_rate' => 50]))
            ->assertRedirect();

        $invoice = Invoice::where('type', Invoice::TYPE_PURCHASE)->firstOrFail();

        $this->assertSame('USD', $invoice->currency);
        $this->assertSame(50.0, (float) $invoice->exchange_rate);
        $this->assertSame(1_000.0, (float) $invoice->total);

        $movement = StockMovement::where('reference_id', $invoice->id)->firstOrFail();
        $this->assertSame(100.0, (float) $movement->quantity);
        $this->assertSame(StockMovement::TYPE_IN, $movement->type);
        $this->assertSame(100.0, app(InventoryService::class)->stockFor($product->id, $this->warehouse->id));
    }

    /**
     * التوزيع لازم يكون بالتناسب مع قيمة البند، مش بالتساوي على البنود.
     * بند قيمته 3 أضعاف التاني لازم ياخد 3 أضعاف المصاريف.
     */
    public function test_landed_costs_are_split_in_proportion_to_line_value(): void
    {
        $big = $this->product('صنف كبير');
        $small = $this->product('صنف صغير');

        // 750 USD و 250 USD => نسبة 3:1، مصاريف 4,000 جنيه => 3,000 و 1,000
        $this->actingAs($this->user)->post('/purchases', $this->payload([
            ['product_id' => $big->id, 'quantity' => 10, 'unit_price' => 75],
            ['product_id' => $small->id, 'quantity' => 10, 'unit_price' => 25],
        ], [
            'exchange_rate' => 50,
            'shipping_cost' => 2_500,
            'customs_cost' => 1_000,
            'clearance_cost' => 500,
        ]));

        $invoice = Invoice::where('type', Invoice::TYPE_PURCHASE)->firstOrFail();
        $costs = StockMovement::where('reference_id', $invoice->id)
            ->pluck('unit_cost', 'product_id');

        // الكبير: 75×50 = 3,750 + (3,000 ÷ 10) = 4,050
        $this->assertSame(4_050.0, (float) $costs[$big->id]);
        // الصغير: 25×50 = 1,250 + (1,000 ÷ 10) = 1,350
        $this->assertSame(1_350.0, (float) $costs[$small->id]);
    }

    /**
     * المصاريف الموزّعة لازم ترجع لنفس الإجمالي المدخل.
     *
     * التوزيع نفسه مظبوط للقرش (آخر بند بياخد الباقي)، لكن اللي بيتخزّن هو
     * تكلفة الوحدة بخانتين عشريتين — فإعادة الضرب في الكمية بتسيب فرق
     * أقصاه نص قرش لكل وحدة. فبنتحقق من الإجمالي بهامش متناسب مع عدد
     * الوحدات، وده كفاية إنه يكشف أي خطأ حقيقي في التوزيع.
     */
    public function test_the_allocated_landed_costs_add_up(): void
    {
        $a = $this->product('أ');
        $b = $this->product('ب');
        $c = $this->product('ج');

        // أرقام بتدي كسور عشرية في القسمة
        $this->actingAs($this->user)->post('/purchases', $this->payload([
            ['product_id' => $a->id, 'quantity' => 3, 'unit_price' => 33.33],
            ['product_id' => $b->id, 'quantity' => 7, 'unit_price' => 11.11],
            ['product_id' => $c->id, 'quantity' => 11, 'unit_price' => 7.77],
        ], ['exchange_rate' => 1, 'shipping_cost' => 1_000, 'customs_cost' => 0.01]));

        $invoice = Invoice::where('type', Invoice::TYPE_PURCHASE)->firstOrFail();

        $allocated = 0.0;
        $units = 0.0;

        foreach (StockMovement::where('reference_id', $invoice->id)->get() as $movement) {
            $item = $invoice->items()->where('product_id', $movement->product_id)->firstOrFail();
            $allocated += ((float) $movement->unit_cost - (float) $item->unit_price) * (float) $item->quantity;
            $units += (float) $item->quantity;
        }

        // 21 وحدة × نص قرش = هامش أقصاه ~0.11
        $this->assertEqualsWithDelta(1_000.01, $allocated, $units * 0.005);
        $this->assertSame(1_000.0, (float) $invoice->shipping_cost);
        $this->assertSame(0.01, (float) $invoice->customs_cost);
    }

    public function test_the_product_cost_is_updated_to_the_landed_cost(): void
    {
        $product = $this->product(cost: 1);

        $this->actingAs($this->user)->post('/purchases', $this->payload([
            ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 20],
        ], ['exchange_rate' => 50, 'shipping_cost' => 1_000]));

        // 20×50 = 1,000 + (1,000 ÷ 10) = 1,100
        $this->assertSame(1_100.0, (float) $product->fresh()->purchase_price);
    }

    public function test_a_zero_exchange_rate_is_rejected(): void
    {
        $product = $this->product();

        $this->actingAs($this->user)
            ->post('/purchases', $this->payload([
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10],
            ], ['exchange_rate' => 0]))
            ->assertSessionHasErrors('exchange_rate');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_an_unknown_currency_is_rejected(): void
    {
        $product = $this->product();

        $this->actingAs($this->user)
            ->post('/purchases', $this->payload([
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10],
            ], ['currency' => 'XYZ']))
            ->assertSessionHasErrors('currency');
    }

    public function test_supplier_payments_record_their_currency_and_rate(): void
    {
        $this->actingAs($this->user)
            ->post("/suppliers/{$this->supplier->id}/payments", [
                'amount' => 5_000,
                'method' => 'bank',
                'date' => Carbon::today()->toDateString(),
                'exchange_rate' => 49.5,
                'reference' => 'SWIFT-1',
            ])
            ->assertRedirect();

        $payment = Payment::firstOrFail();

        $this->assertSame(Payment::TYPE_DISBURSEMENT, $payment->type);
        $this->assertSame('USD', $payment->currency);
        $this->assertSame(49.5, (float) $payment->exchange_rate);
        $this->assertSame(247_500.0, (float) $payment->amount * (float) $payment->exchange_rate);
    }

    /** رصيد المورد بعملته: مشتريات − مدفوعات */
    public function test_the_supplier_balance_is_kept_in_the_supplier_currency(): void
    {
        $product = $this->product();

        $this->actingAs($this->user)->post('/purchases', $this->payload([
            ['product_id' => $product->id, 'quantity' => 100, 'unit_price' => 10],
        ], ['exchange_rate' => 50]));

        $this->actingAs($this->user)->post("/suppliers/{$this->supplier->id}/payments", [
            'amount' => 400,
            'method' => 'bank',
            'date' => Carbon::today()->toDateString(),
            'exchange_rate' => 50,
        ]);

        $summary = app(SupplierService::class)->summary($this->supplier->fresh());

        $this->assertSame(1_000.0, $summary['purchases']);
        $this->assertSame(400.0, $summary['paid']);
        $this->assertSame(600.0, $summary['balance']);
        $this->assertSame(50_000.0, $summary['purchasesEgp']);
    }

    public function test_the_statement_running_balance_ends_at_the_summary_balance(): void
    {
        $product = $this->product();

        $this->actingAs($this->user)->post('/purchases', $this->payload([
            ['product_id' => $product->id, 'quantity' => 100, 'unit_price' => 10],
        ], ['exchange_rate' => 50]));

        $this->actingAs($this->user)->post("/suppliers/{$this->supplier->id}/payments", [
            'amount' => 300,
            'method' => 'cash',
            'date' => Carbon::today()->toDateString(),
            'exchange_rate' => 50,
        ]);

        $service = app(SupplierService::class);
        $supplier = $this->supplier->fresh();

        $this->assertSame(
            $service->summary($supplier)['balance'],
            $service->statement($supplier)['closing'],
        );
    }

    public function test_the_screens_render(): void
    {
        $product = $this->product();
        $this->actingAs($this->user)->post('/purchases', $this->payload([
            ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10],
        ]));

        $invoice = Invoice::firstOrFail();

        $this->actingAs($this->user)->get('/purchases')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchases/Index')
                ->has('invoices.data', 1)
            );

        $this->actingAs($this->user)->get('/purchases/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('purchases/Create'));

        $this->actingAs($this->user)->get("/purchases/{$invoice->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchases/Show')
                ->has('invoice.items', 1)
            );

        $this->actingAs($this->user)->get('/suppliers')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('suppliers/Index'));

        $this->actingAs($this->user)->get("/suppliers/{$this->supplier->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('suppliers/Show')
                ->has('statement.rows')
            );
    }

    /** كشف المورد بيتدوّر فيه برقم فاتورة الشراء — زي كشف التاجر بالظبط */
    public function test_the_supplier_statement_can_be_searched_by_invoice_number(): void
    {
        $wanted = $this->confirmedPurchase();
        $other = $this->confirmedPurchase();

        $this->actingAs($this->user)
            ->get("/suppliers/{$this->supplier->id}?search={$wanted->number}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('suppliers/Show')
                ->has('statement.rows', 1)
                ->where('statement.rows.0.reference', $wanted->number)
                ->where('filters.search', $wanted->number)
            );

        $this->assertNotSame($wanted->number, $other->number);
    }

    /** فاتورة البيع ماينفعش تتفتح من مسار المشتريات والعكس */
    public function test_the_purchase_route_rejects_a_sale_invoice(): void
    {
        $sale = Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => 'S-X-1',
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'date' => Carbon::today()->toDateString(),
            'total' => 10,
            'status' => Invoice::STATUS_CONFIRMED,
        ]);

        $this->actingAs($this->user)->get("/purchases/{$sale->id}")->assertNotFound();
    }

    // ——— مساعدات ———

    /** فاتورة شراء مؤكّدة بترقيم النظام */
    private function confirmedPurchase(): Invoice
    {
        $product = $this->product();

        $this->actingAs($this->user)
            ->post('/purchases', $this->payload([
                ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 20],
            ]))
            ->assertRedirect();

        return Invoice::query()->purchases()->latest('id')->firstOrFail();
    }

    private function product(string $name = 'صنف مستورد', float $cost = 100): Product
    {
        return Product::create([
            'sku' => 'P-'.fake()->unique()->numerify('#####'),
            'name' => $name,
            'unit' => 'قطعة',
            'purchase_price' => $cost,
            'sale_price' => $cost * 1.4,
            'min_stock' => 0,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function payload(array $items, array $extra = []): array
    {
        return array_merge([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->toDateString(),
            'currency' => 'USD',
            'exchange_rate' => 49,
            'items' => $items,
        ], $extra);
    }
}
