<?php

namespace Tests\Feature\Erp;

use App\Mcp\Servers\ErpServer;
use App\Mcp\Tools\InvoiceLookup;
use App\Mcp\Tools\LowStockProducts;
use App\Mcp\Tools\SalesSummary;
use App\Mcp\Tools\StockLookup;
use App\Mcp\Tools\TopDebtors;
use App\Mcp\Tools\TopProducts;
use App\Mcp\Tools\TraderBalance;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Passport;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Product $product;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $category = Category::create(['name' => 'أجهزة منزلية']);

        $this->product = Product::create([
            'sku' => 'HA-9001',
            'name' => 'خلاط كهربائي للاختبار',
            'category_id' => $category->id,
            'unit' => 'قطعة',
            'purchase_price' => 100,
            'sale_price' => 180,
            'min_stock' => 50,
        ]);

        // رصيد 10 قطع بس — يعني تحت حد الطلب (50)
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'unit_cost' => 100,
            'type' => 'purchase',
            'date' => now()->toDateString(),
        ]);

        $this->customer = Customer::create([
            'name' => 'محلات النصر للتجارة',
            'city' => 'القاهرة',
            'phone' => '01000000001',
            'price_tier' => 'wholesale',
            'credit_limit' => 1000,
        ]);

        $this->invoice = Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => 'S-2026-00001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'subtotal' => 1800,
            'tax' => 252,
            'total' => 2052,
            'paid' => 500,
            'status' => Invoice::STATUS_CONFIRMED,
        ]);

        InvoiceItem::create([
            'invoice_id' => $this->invoice->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 180,
            'total' => 1800,
        ]);

        Payment::create([
            'type' => Payment::TYPE_RECEIPT,
            'customer_id' => $this->customer->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 500,
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'method' => 'cash',
            'date' => now()->toDateString(),
            'user_id' => $user->id,
        ]);
    }

    public function test_the_server_exposes_the_read_only_tools(): void
    {
        $names = collect($this->listTools())->pluck('name')->all();

        $this->assertEqualsCanonicalizing([
            // المخزون
            'low-stock-products',
            'stock-lookup',
            'slow-moving-products',
            'fast-moving-products',
            'forecast-needs',
            // المبيعات
            'sales-summary',
            'sales-breakdown',
            'sales-decline',
            'top-products',
            'best-customers',
            'reps-comparison',
            'trader-balance',
            'top-debtors',
            // الفواتير
            'invoice-lookup',
            // سجل النشاط — محكومة بصلاحية المستخدم جواها
            'recent-activity',
            'last-login',
            'who-did',
        ], $names);
    }

    /** مفيش أداة بتكتب — كل الأدوات readOnlyHint */
    public function test_every_tool_is_marked_read_only(): void
    {
        foreach ($this->listTools() as $tool) {
            $this->assertTrue(
                $tool['annotations']['readOnlyHint'] ?? false,
                "الأداة [{$tool['name']}] مش متعلّمة للقراءة فقط.",
            );
        }
    }

    public function test_low_stock_products_reports_the_shortfall(): void
    {
        ErpServer::tool(LowStockProducts::class)
            ->assertOk()
            ->assertSee('HA-9001')
            ->assertSee('خلاط كهربائي للاختبار')
            ->assertSee('40 قطعة'); // 50 حد الطلب − 10 رصيد
    }

    public function test_trader_balance_shows_the_credit_limit_breach(): void
    {
        // 2,052 فاتورة − 500 محصّل = 1,552 على حد ائتمان 1,000
        ErpServer::tool(TraderBalance::class, ['name' => 'النصر'])
            ->assertOk()
            ->assertSee('محلات النصر للتجارة')
            ->assertSee('1,552.00 ج.م')
            ->assertSee('تعدّى حد الائتمان');
    }

    public function test_top_debtors_lists_the_outstanding_balance(): void
    {
        ErpServer::tool(TopDebtors::class)
            ->assertOk()
            ->assertSee('محلات النصر للتجارة')
            ->assertSee('1,552.00 ج.م');
    }

    public function test_sales_summary_totals_match_the_invoice(): void
    {
        ErpServer::tool(SalesSummary::class, [
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ])
            ->assertOk()
            ->assertSee('عدد الفواتير: 1')
            ->assertSee('2,052.00 ج.م')   // الإجمالي
            ->assertSee('1,552.00 ج.م');  // المتبقي
    }

    public function test_top_products_reports_revenue_and_margin(): void
    {
        ErpServer::tool(TopProducts::class, [
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ])
            ->assertOk()
            ->assertSee('HA-9001')
            ->assertSee('1,800.00 ج.م')  // المبيعات
            ->assertSee('800.00 ج.م');   // الربح = 1,800 − (10 × 100)
    }

    public function test_stock_lookup_breaks_the_balance_down_by_warehouse(): void
    {
        ErpServer::tool(StockLookup::class, ['search' => 'HA-9001'])
            ->assertOk()
            ->assertSee('المخزن الرئيسي: 10')
            ->assertSee('تحت حد الطلب');
    }

    public function test_invoice_lookup_returns_the_lines_and_totals(): void
    {
        ErpServer::tool(InvoiceLookup::class, ['number' => 'S-2026-00001'])
            ->assertOk()
            ->assertSee('فاتورة بيع رقم S-2026-00001')
            ->assertSee('محلات النصر للتجارة')
            ->assertSee('خلاط كهربائي للاختبار')
            ->assertSee('2,052.00 ج.م');
    }

    public function test_a_missing_record_answers_in_arabic_instead_of_failing(): void
    {
        ErpServer::tool(InvoiceLookup::class, ['number' => 'S-9999-99999'])
            ->assertOk()
            ->assertSee('مفيش فاتورة برقم');

        ErpServer::tool(TraderBalance::class, ['name' => 'اسم مش موجود خالص'])
            ->assertOk()
            ->assertSee('مفيش تاجر');
    }

    /**
     * السيرفر على الويب محمي — من غير توكن 401، ومعاه هيدر WWW-Authenticate
     * اللي بيوجّه العميل لمسار اكتشاف الـ OAuth. ده اللي بيخلي claude.ai
     * يبدأ تسجيل الدخول لوحده بدل ما يفشل.
     */
    public function test_the_web_endpoint_requires_a_token(): void
    {
        $response = $this->postJson('/mcp/erp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ])->assertUnauthorized();

        $this->assertStringContainsString(
            '/.well-known/oauth-protected-resource/mcp/erp',
            (string) $response->headers->get('WWW-Authenticate'),
        );
    }

    public function test_a_valid_token_reaches_the_server(): void
    {
        Passport::actingAs(User::first(), [Registrar::OAUTH_SCOPE]);

        $this->withHeaders(['Accept' => 'application/json, text/event-stream'])
            ->postJson('/mcp/erp', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/list',
            ])->assertOk()->assertJsonPath('result.tools.0.name', 'low-stock-products');
    }

    /**
     * @return list<array<string, mixed>>
     */
    /**
     * كل الأدوات — بتتبع الصفحات.
     *
     * `tools/list` بيرجّع صفحة ومعاها cursor، وإحنا بقينا أكتر من صفحة
     * واحدة. العميل الحقيقي بيلف على الصفحات، فالاختبار بيعمل نفس
     * الحاجة بدل ما يفحص أول صفحة ويفتكرها الكل.
     */
    private function listTools(): array
    {
        Passport::actingAs(User::first(), [Registrar::OAUTH_SCOPE]);

        $tools = [];
        $cursor = null;

        do {
            $payload = $this->withHeaders(['Accept' => 'application/json, text/event-stream'])
                ->postJson('/mcp/erp', array_filter([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'tools/list',
                    'params' => $cursor ? ['cursor' => $cursor] : null,
                ]))->assertOk()->json('result');

            $tools = [...$tools, ...($payload['tools'] ?? [])];
            $cursor = $payload['nextCursor'] ?? null;
        } while ($cursor !== null);

        return $tools;
    }
}
