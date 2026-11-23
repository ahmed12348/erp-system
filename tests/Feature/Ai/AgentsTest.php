<?php

namespace Tests\Feature\Ai;

use App\Ai\AgentRegistry;
use App\Mcp\Tools\CompanyOverview;
use App\Mcp\Tools\ForecastNeeds;
use App\Mcp\Tools\RepsComparison;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesRep;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Mcp\Request;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Tests\Support\RoutesQuestions;
use Tests\TestCase;

/**
 * الوكلاء: المجالات، الصلاحيات، والأدوات التحليلية.
 *
 * المجالات مابقتش تبويبات على الشاشة — بقت تقسيم جوه الكود بيختار منه
 * `AgentRouter` قبل كل إجابة. اللي بيتأكد منه الملف ده إن التقسيم
 * نفسه سليم: مين بيوصل لإيه، وكل مجال بيبعت أدواته هو.
 *
 * البيانات هنا مزروعة بإيد (مفيش factory) عشان الأرقام تبقى متوقّعة
 * بالظبط — الأداة بتحسب في PHP، والاختبار بيتأكد من الحساب نفسه مش
 * من كلام الموديل.
 */
class AgentsTest extends TestCase
{
    use RefreshDatabase, RoutesQuestions;

    private Warehouse $warehouse;

    private Customer $customer;

    private Product $fast;

    private Product $slow;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('prism.providers.gemini.api_key', 'test-key');
        config()->set('ai.enabled', true);
        config()->set('ai.provider', 'gemini');

        RateLimiter::clear('ai-chat');

        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $category = Category::create(['name' => 'بقالة']);

        $this->customer = Customer::create([
            'name' => 'وليد عبد العظيم',
            'price_tier' => 'sale',
        ]);

        $this->fast = Product::create([
            'sku' => 'FA-1', 'name' => 'صنف سريع', 'category_id' => $category->id,
            'unit' => 'كرتونة', 'purchase_price' => 100, 'sale_price' => 150, 'min_stock' => 5,
        ]);

        $this->slow = Product::create([
            'sku' => 'SL-1', 'name' => 'صنف راكد', 'category_id' => $category->id,
            'unit' => 'كرتونة', 'purchase_price' => 200, 'sale_price' => 300, 'min_stock' => 2,
        ]);

        // رصيد للاتنين
        foreach ([[$this->fast, 100], [$this->slow, 20]] as [$product, $qty]) {
            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouse->id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => $qty,
                'unit_cost' => $product->purchase_price,
                'date' => now()->subDays(120)->toDateString(),
            ]);
        }
    }

    // ── المجالات والصلاحيات ───────────────────────────────────────

    public function test_an_admin_reaches_all_four_domains(): void
    {
        $keys = $this->keysFor($this->admin());

        $this->assertSame(['inventory', 'sales', 'purchasing', 'admin'], $keys);
    }

    public function test_an_accountant_reaches_everything_except_admin(): void
    {
        $keys = $this->keysFor($this->accountant());

        $this->assertSame(['inventory', 'sales', 'purchasing'], $keys);
    }

    public function test_a_sales_user_reaches_only_inventory_and_sales(): void
    {
        $keys = $this->keysFor($this->salesUser());

        $this->assertSame(['inventory', 'sales'], $keys);
    }

    /**
     * التقسيم بيوصل للأدوات اللي بتتبعت.
     *
     * المستخدم بيشوف شات واحد، بس مجال المخازن مابياخدش أداة مندوبين
     * ومجال المبيعات مابياخدش أداة توقّع — ودي كل حكاية التوجيه.
     */
    public function test_each_domain_sends_only_its_own_tools(): void
    {
        $fake = Prism::fake([
            $this->routedTo('inventory'),
            TextResponseFake::make()->withText('رد المخازن.'),
            $this->routedTo('sales'),
            TextResponseFake::make()->withText('رد المبيعات.'),
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/ai/chat', ['message' => 'إيه الناقص؟']);
        $this->actingAs($admin)->postJson('/ai/chat', ['message' => 'المندوبين عاملين إيه؟']);

        $fake->assertRequest(function (array $requests): void {
            $inventoryTools = array_map(fn ($t) => $t->name(), $requests[1]->tools());
            $salesTools = array_map(fn ($t) => $t->name(), $requests[3]->tools());

            $this->assertContains('forecast-needs', $inventoryTools);
            $this->assertNotContains('reps-comparison', $inventoryTools);

            $this->assertContains('reps-comparison', $salesTools);
            $this->assertNotContains('forecast-needs', $salesTools);
        });
    }

    /** محادثة واحدة مهما اتنقّل السؤال بين المجالات */
    public function test_one_conversation_carries_every_domain(): void
    {
        Prism::fake([
            $this->routedTo('inventory'),
            TextResponseFake::make()->withText('رد المخازن.'),
            $this->routedTo('sales'),
            TextResponseFake::make()->withText('رد المبيعات.'),
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/ai/chat', ['message' => 'سؤال مخازن']);
        $this->actingAs($admin)->postJson('/ai/chat', ['message' => 'سؤال مبيعات']);

        $this->actingAs($admin)
            ->getJson('/ai/chat')
            ->assertJsonCount(4, 'messages')
            ->assertJsonPath('messages.0.content', 'سؤال مخازن')
            ->assertJsonPath('messages.2.content', 'سؤال مبيعات');
    }

    /**
     * الأسئلة المقترحة مخلوطة من كل المجالات اللي الدور بيوصلها.
     *
     * الشات واحد، فأول نظرة لازم تقول للمستخدم إنه يسأل أي حاجة — مش
     * ستة أسئلة كلها عن المخزن.
     */
    public function test_the_starters_are_mixed_from_every_domain(): void
    {
        $starters = app(AgentRegistry::class)->startersFor($this->admin());

        $this->assertCount(6, $starters);

        // أول أربعة: سؤال من كل مجال بالترتيب
        $this->assertSame('إيه الأصناف اللي قربت تخلص؟', $starters[0]);
        $this->assertSame('مبيعات الشهر ده عاملة إيه عن اللي فات؟', $starters[1]);
        $this->assertSame('أطلب إيه الشهر الجاي؟', $starters[2]);
        $this->assertSame('مين آخر مرة دخل النظام؟', $starters[3]);
    }

    public function test_a_sales_user_never_sees_an_admin_starter(): void
    {
        $starters = app(AgentRegistry::class)->startersFor($this->salesUser());

        $this->assertCount(6, $starters);
        $this->assertNotContains('مين آخر مرة دخل النظام؟', $starters);
    }

    public function test_the_golden_rule_is_in_every_domains_prompt(): void
    {
        $fake = Prism::fake([
            $this->routedTo('inventory'),
            TextResponseFake::make()->withText('أ'),
            $this->routedTo('admin'),
            TextResponseFake::make()->withText('ب'),
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/ai/chat', ['message' => 'سؤال مخازن']);
        $this->actingAs($admin)->postJson('/ai/chat', ['message' => 'مين عمل إيه؟']);

        $fake->assertRequest(function (array $requests): void {
            foreach ([$requests[1], $requests[3]] as $request) {
                $system = collect($request->systemPrompts())
                    ->map(fn ($prompt) => $prompt->content)
                    ->implode("\n");

                $this->assertStringContainsString('القاعدة الذهبية', $system);
                $this->assertStringContainsString('ماتجمعش', $system);
            }
        });
    }

    // ── الأدوات التحليلية: الأرقام بتتحسب في PHP ──────────────────

    public function test_slow_moving_finds_idle_stock_and_its_value(): void
    {
        $this->sell($this->fast, 10, now()->subDays(5));

        $result = app(AnalyticsService::class)->slowMoving(60);

        $labels = array_column($result['rows'], 'sku');

        $this->assertContains('SL-1', $labels);
        $this->assertNotContains('FA-1', $labels);

        // 20 كرتونة × 200 ج تكلفة
        $this->assertSame(4000.0, $result['tied']);
    }

    public function test_fast_moving_ranks_by_units_and_turnover(): void
    {
        $this->sell($this->fast, 30, now()->subDays(3));
        $this->sell($this->slow, 2, now()->subDays(3));

        $rows = app(AnalyticsService::class)->fastMoving(
            now()->subDays(30)->toDateString(),
            now()->toDateString(),
            'units',
        );

        $this->assertSame('FA-1', $rows[0]['sku']);
        $this->assertSame(30.0, $rows[0]['units']);
        // 30 مباع ÷ 70 رصيد فاضل
        $this->assertSame(0.43, $rows[0]['turnover']);
    }

    public function test_forecast_subtracts_stock_from_expected_demand(): void
    {
        // 90 كرتونة في 90 يوم = 1 في اليوم
        for ($i = 1; $i <= 9; $i++) {
            $this->sell($this->fast, 10, now()->subDays($i * 10));
        }

        $result = app(AnalyticsService::class)->forecastNeeds(30, 90);

        $this->assertTrue($result['reliable']);

        $row = collect($result['rows'])->firstWhere('sku', 'FA-1');

        // الرصيد 100 - 90 مباع = 10 فاضل، والمتوقّع 30 → ناقص 20
        $this->assertNotNull($row);
        $this->assertSame(1.0, $row['perDay']);
        $this->assertSame(20.0, $row['shortfall']);
    }

    /** توقّع على تاريخ قصير لازم يتعلّم إنه مش موثوق */
    public function test_a_short_history_is_flagged_as_unreliable(): void
    {
        $this->sell($this->fast, 10, now()->subDays(3));

        $result = app(AnalyticsService::class)->forecastNeeds(30, 90);

        $this->assertFalse($result['reliable']);
        $this->assertLessThan(30, $result['historyDays']);
    }

    public function test_the_forecast_tool_says_so_when_the_history_is_short(): void
    {
        $this->sell($this->fast, 50, now()->subDays(2));

        $output = (string) app(ForecastNeeds::class)
            ->handle(new Request([]))
            ->content();

        $this->assertStringContainsString('أقل من شهر', $output);
        $this->assertStringContainsString('تقريبي', $output);
    }

    public function test_sales_breakdown_compares_with_the_previous_period(): void
    {
        // الفترة السابقة: 1000
        $this->sell($this->fast, 10, now()->subDays(40), 100);
        // الفترة الحالية: 1500
        $this->sell($this->fast, 10, now()->subDays(5), 150);

        $result = app(AnalyticsService::class)->salesBreakdown(
            now()->subDays(30)->toDateString(),
            now()->toDateString(),
        );

        $this->assertSame(1500.0, $result['total']);
        $this->assertSame(1000.0, $result['previousTotal']);
        $this->assertSame(50.0, $result['change']);
    }

    public function test_sales_decline_names_the_biggest_drops(): void
    {
        $this->sell($this->fast, 20, now()->subDays(40), 100);  // قبل: 2000
        $this->sell($this->fast, 5, now()->subDays(5), 100);    // بعد: 500

        $result = app(AnalyticsService::class)->salesDecline(
            now()->subDays(30)->toDateString(),
            now()->toDateString(),
            now()->subDays(60)->toDateString(),
            now()->subDays(31)->toDateString(),
        );

        $this->assertSame(-75.0, $result['change']);

        $product = $result['byProduct'][0];

        $this->assertSame('صنف سريع', $product['label']);
        $this->assertSame(-1500.0, $product['delta']);
    }

    public function test_best_customers_can_rank_by_payment_speed(): void
    {
        $invoice = $this->sell($this->fast, 10, now()->subDays(20), 100);

        Payment::create([
            'type' => Payment::TYPE_RECEIPT,
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 1000,
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'method' => 'cash',
            'date' => now()->subDays(15)->toDateString(),
            'user_id' => $this->admin()->id,
        ]);

        $rows = app(AnalyticsService::class)->bestCustomers(
            now()->subDays(30)->toDateString(),
            now()->toDateString(),
            'speed',
        );

        // 5 أيام بين الفاتورة والتحصيل
        $this->assertSame(5, $rows[0]['paymentDays']);
        $this->assertSame(1000.0, $rows[0]['collected']);
    }

    /**
     * اللقطة العامة — الأداة اللي المجموعة الافتراضية بتعتمد عليها.
     *
     * الأرقام كلها متحسوبة في PHP زي أي أداة تانية، والسؤال العام
     * («الشركة عاملة إيه؟») بياخد رد فيه أرقام حقيقية بدل كلام.
     */
    public function test_the_company_overview_reports_real_numbers(): void
    {
        $this->sell($this->fast, 10, now(), 150);

        $output = (string) app(CompanyOverview::class)
            ->handle(new Request([]))
            ->content();

        // 10 × 150 مبيعات الشهر
        $this->assertStringContainsString('1,500.00', $output);
        $this->assertStringContainsString('المستحق على التجار', $output);
        $this->assertStringContainsString('قيمة المخزون بالتكلفة', $output);
        $this->assertStringContainsString('أصناف تحت حد الطلب', $output);
    }

    public function test_reps_comparison_uses_the_existing_commission_rule(): void
    {
        $rep = SalesRep::create(['name' => 'كريم', 'commission_rate' => 5]);
        $this->customer->update(['sales_rep_id' => $rep->id]);

        $invoice = $this->sell($this->fast, 10, now()->subDays(5), 100);
        $invoice->update(['sales_rep_id' => $rep->id]);

        Payment::create([
            'type' => Payment::TYPE_RECEIPT,
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 400,
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'method' => 'cash',
            'date' => now()->subDays(2)->toDateString(),
            'user_id' => $this->admin()->id,
            'sales_rep_id' => $rep->id,
        ]);

        $output = (string) app(RepsComparison::class)
            ->handle(new Request([]))
            ->content();

        $this->assertStringContainsString('كريم', $output);
        // العمولة على المحصّل: 400 × 5% = 20
        $this->assertStringContainsString('20', $output);
        $this->assertStringContainsString('العمولة بتتحسب على المحصّل', $output);
    }

    /**
     * @return array<int, string>
     */
    private function keysFor(User $user): array
    {
        return array_map(
            fn ($agent) => $agent->key(),
            app(AgentRegistry::class)->for($user),
        );
    }

    /** فاتورة بيع مؤكّدة بسطر واحد */
    private function sell(Product $product, float $qty, mixed $date, float $price = 150): Invoice
    {
        $total = $qty * $price;

        $invoice = Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => 'S-'.uniqid(),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin()->id,
            'date' => $date->toDateString(),
            'status' => Invoice::STATUS_CONFIRMED,
            'subtotal' => $total,
            'total' => $total,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'unit_price' => $price,
            'total' => $total,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => StockMovement::TYPE_OUT,
            'quantity' => -$qty,
            'unit_cost' => $product->purchase_price,
            'date' => $date->toDateString(),
            'reference_type' => $invoice->getMorphClass(),
            'reference_id' => $invoice->id,
        ]);

        return $invoice;
    }
}
