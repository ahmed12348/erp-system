<?php

namespace Tests\Feature\Erp;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CustomerService;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SaleInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Warehouse $warehouse;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $this->customer = Customer::create([
            'name' => 'تاجر الاختبار',
            'city' => 'القاهرة',
            'price_tier' => 'sale',
            'credit_limit' => 100_000,
        ]);
    }

    public function test_creating_an_invoice_writes_items_and_negative_stock_movements(): void
    {
        $product = $this->product(stock: 100, salePrice: 250, cost: 180);

        $this->actingAs($this->user)
            ->post('/sales', $this->payload([
                ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 250],
            ]))
            ->assertRedirect();

        $invoice = Invoice::where('type', Invoice::TYPE_SALE)->firstOrFail();

        // 10 × 250 = 2,500 + 14% = 2,850
        $this->assertSame(2_500.0, (float) $invoice->subtotal);
        $this->assertSame(350.0, (float) $invoice->tax);
        $this->assertSame(2_850.0, (float) $invoice->total);
        $this->assertCount(1, $invoice->items);

        $movement = StockMovement::where('product_id', $product->id)
            ->where('reference_id', $invoice->id)
            ->firstOrFail();

        $this->assertSame(-10.0, (float) $movement->quantity);
        $this->assertSame(StockMovement::TYPE_OUT, $movement->type);
        $this->assertSame(90.0, app(InventoryService::class)->stockFor($product->id, $this->warehouse->id));
    }

    /** الفاتورة بتزوّد رصيد التاجر بقيمتها بالظبط */
    public function test_the_invoice_raises_the_trader_balance(): void
    {
        $product = $this->product(stock: 100);

        $this->actingAs($this->user)->post('/sales', $this->payload([
            ['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 1_000],
        ]));

        // 4,000 + 14% = 4,560
        $this->assertSame(4_560.0, app(CustomerService::class)->summary($this->customer)['balance']);
    }

    public function test_insufficient_stock_is_rejected_with_an_arabic_message(): void
    {
        $product = $this->product(stock: 5, name: 'خلاط كهربائي');

        $this->actingAs($this->user)
            ->post('/sales', $this->payload([
                ['product_id' => $product->id, 'quantity' => 9, 'unit_price' => 100],
            ]))
            ->assertSessionHasErrors('items.0.quantity');

        $message = session('errors')->first('items.0.quantity');

        $this->assertStringContainsString('الرصيد مش كافي', $message);
        $this->assertStringContainsString('خلاط كهربائي', $message);
        $this->assertStringContainsString('5', $message);
        $this->assertStringContainsString('9', $message);
    }

    /** الأهم: الرفض لازم يرجّع كل حاجة زي ما كانت — مفيش فاتورة ولا حركة مخزون */
    public function test_a_rejected_invoice_leaves_no_trace(): void
    {
        $ok = $this->product(stock: 100);
        $short = $this->product(stock: 1);

        $this->actingAs($this->user)
            ->post('/sales', $this->payload([
                ['product_id' => $ok->id, 'quantity' => 5, 'unit_price' => 100],
                ['product_id' => $short->id, 'quantity' => 50, 'unit_price' => 100],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        // حركة المخزون الوحيدة هي رصيد الافتتاح بتاع كل صنف
        $this->assertSame(2, StockMovement::count());
        $this->assertSame(100.0, app(InventoryService::class)->stockFor($ok->id, $this->warehouse->id));
    }

    /** نفس الصنف في سطرين لازم يتجمّع قبل فحص الرصيد */
    public function test_the_same_product_on_two_lines_is_checked_against_the_combined_quantity(): void
    {
        $product = $this->product(stock: 10);

        $this->actingAs($this->user)
            ->post('/sales', $this->payload([
                ['product_id' => $product->id, 'quantity' => 6, 'unit_price' => 100],
                ['product_id' => $product->id, 'quantity' => 6, 'unit_price' => 100],
            ]))
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_stock_in_another_warehouse_does_not_count(): void
    {
        $other = Warehouse::create(['name' => 'مخزن تاني']);
        $product = $this->product(stock: 0);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $other->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 500,
            'date' => Carbon::today()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->post('/sales', $this->payload([
                ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 100],
            ]))
            ->assertSessionHasErrors('items.0.quantity');
    }

    public function test_invoice_numbers_increment_without_collisions(): void
    {
        $product = $this->product(stock: 1000);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->user)->post('/sales', $this->payload([
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ]));
        }

        $numbers = Invoice::orderBy('id')->pluck('number')->all();
        $year = Carbon::today()->year;

        $this->assertSame([
            "S-{$year}-00001",
            "S-{$year}-00002",
            "S-{$year}-00003",
        ], $numbers);
    }

    public function test_a_discount_bigger_than_the_subtotal_is_rejected(): void
    {
        $product = $this->product(stock: 100);

        $this->actingAs($this->user)
            ->post('/sales', $this->payload(
                [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
                ['discount' => 500],
            ))
            ->assertSessionHasErrors('discount');
    }

    public function test_sending_to_eta_sets_the_status_and_a_uuid(): void
    {
        $product = $this->product(stock: 100);
        $this->actingAs($this->user)->post('/sales', $this->payload([
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
        ]));

        $invoice = Invoice::firstOrFail();
        $this->assertNull($invoice->eta_status);

        $this->actingAs($this->user)
            ->post("/sales/{$invoice->id}/eta")
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame(Invoice::ETA_SENT, $invoice->eta_status);
        $this->assertSame(26, strlen((string) $invoice->eta_uuid));
    }

    public function test_an_invoice_cannot_be_sent_to_eta_twice(): void
    {
        $product = $this->product(stock: 100);
        $this->actingAs($this->user)->post('/sales', $this->payload([
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
        ]));

        $invoice = Invoice::firstOrFail();
        $this->actingAs($this->user)->post("/sales/{$invoice->id}/eta");
        $firstUuid = $invoice->fresh()->eta_uuid;

        $this->actingAs($this->user)
            ->post("/sales/{$invoice->id}/eta")
            ->assertSessionHasErrors('eta');

        $this->assertSame($firstUuid, $invoice->fresh()->eta_uuid);
    }

    public function test_the_list_and_detail_pages_render(): void
    {
        $product = $this->product(stock: 100);
        $this->actingAs($this->user)->post('/sales', $this->payload([
            ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 500],
        ]));

        $invoice = Invoice::firstOrFail();

        $this->actingAs($this->user)->get('/sales')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('sales/Index')
                ->has('invoices.data', 1)
                ->where('totals.count', 1)
            );

        $this->actingAs($this->user)->get("/sales/{$invoice->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('sales/Show')
                ->where('invoice.number', $invoice->number)
                ->has('invoice.items', 1)
            );

        $this->actingAs($this->user)->get("/sales/{$invoice->id}/print")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('print/SaleInvoice'));
    }

    public function test_the_create_screen_exposes_stock_per_warehouse(): void
    {
        $product = $this->product(stock: 42);

        $this->actingAs($this->user)->get('/sales/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('sales/Create')
                ->has('products', 1)
                ->where('products.0.stock.'.$this->warehouse->id, 42)
                ->where('vatRate', InvoiceService::VAT_RATE)
            );
    }

    public function test_wholesale_traders_get_the_wholesale_price_offered(): void
    {
        $this->customer->update(['price_tier' => 'wholesale']);
        $this->product(stock: 10, salePrice: 100);

        $this->actingAs($this->user)->get('/sales/create')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('customers.0.priceTier', 'wholesale')
                ->where('products.0.wholesalePrice', 90)
            );
    }

    public function test_an_empty_invoice_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post('/sales', $this->payload([]))
            ->assertSessionHasErrors('items');
    }

    public function test_the_service_throws_for_an_unknown_product(): void
    {
        $this->expectException(ValidationException::class);

        app(InvoiceService::class)->createSale([
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->toDateString(),
            'items' => [['product_id' => 99_999, 'quantity' => 1, 'unit_price' => 10]],
        ], $this->user);
    }

    // ——— مساعدات ———

    private function product(float $stock, string $name = 'صنف اختبار', float $salePrice = 100, float $cost = 70): Product
    {
        $product = Product::create([
            'sku' => 'T-'.fake()->unique()->numerify('#####'),
            'name' => $name,
            'unit' => 'قطعة',
            'purchase_price' => $cost,
            'sale_price' => $salePrice,
            'wholesale_price' => $salePrice * 0.9,
            'min_stock' => 0,
        ]);

        if ($stock > 0) {
            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouse->id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => $stock,
                'unit_cost' => $cost,
                'date' => Carbon::today()->subDay()->toDateString(),
            ]);
        }

        return $product;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function payload(array $items, array $extra = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->toDateString(),
            'items' => $items,
        ], $extra);
    }
}
