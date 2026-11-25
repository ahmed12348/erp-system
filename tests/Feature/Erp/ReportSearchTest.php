<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\SalesRep;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ExportBuilder;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * البحث برقم الفاتورة في التقارير، وتقارير «بالفاتورة».
 *
 * البحث جزئي عن قصد: اللي بيدوّر ماسك ورقة وبيكتب آخر كام رقم منها، مش
 * بيكتب `S-2026-00001` كامل.
 */
class ReportSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Warehouse $warehouse;

    private Customer $customer;

    private Product $product;

    private Product $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->admin();
        $this->warehouse = Warehouse::create(['name' => 'الرئيسي']);

        $category = Category::create(['name' => 'بقالة']);
        $rep = SalesRep::create(['name' => 'سامي المندوب']);

        $this->customer = Customer::create([
            'name' => 'وليد عبد العظيم',
            'price_tier' => 'sale',
            'sales_rep_id' => $rep->id,
        ]);

        $this->product = Product::create([
            'sku' => 'KB-25', 'name' => 'كبولي مجوز', 'category_id' => $category->id,
            'unit' => 'جوز', 'purchase_price' => 100, 'sale_price' => 150, 'min_stock' => 0,
        ]);

        $this->other = Product::create([
            'sku' => 'FR-01', 'name' => 'الفاروق فضي', 'category_id' => $category->id,
            'unit' => 'قطعة', 'purchase_price' => 50, 'sale_price' => 80, 'min_stock' => 0,
        ]);

        foreach ([$this->product, $this->other] as $product) {
            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouse->id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => 100,
                'unit_cost' => $product->purchase_price,
                'date' => Carbon::today()->subDays(30)->toDateString(),
            ]);
        }

        // فاتورتين في يومين مختلفين، كل واحدة بصنف مختلف
        $this->invoice('S-2026-00001', Carbon::today()->subDays(5), $this->product, 10, 150, $rep->id);
        $this->invoice('S-2026-00002', Carbon::today()->subDays(2), $this->other, 5, 80, $rep->id);
    }

    // ── البحث برقم الفاتورة ───────────────────────────────────────

    public function test_the_period_report_narrows_to_the_matching_invoice(): void
    {
        $report = app(ReportService::class)->salesByPeriod(
            Carbon::today()->subDays(30)->toDateString(),
            Carbon::today()->toDateString(),
            'day',
            'S-2026-00001',
        );

        $this->assertCount(1, $report['rows']);
        $this->assertSame(1_500.0, $report['totals']['total']);
    }

    /** مطابقة جزئية — محدش بيكتب الرقم كامل وهو بيدوّر */
    public function test_a_partial_number_matches(): void
    {
        $report = app(ReportService::class)->salesByPeriod(
            Carbon::today()->subDays(30)->toDateString(),
            Carbon::today()->toDateString(),
            'day',
            '00002',
        );

        $this->assertCount(1, $report['rows']);
        $this->assertSame(400.0, $report['totals']['total']);
    }

    public function test_the_product_report_narrows_to_the_matching_invoice(): void
    {
        $rows = app(ReportService::class)->salesByProduct(
            Carbon::today()->subDays(30)->toDateString(),
            Carbon::today()->toDateString(),
            100,
            'S-2026-00002',
        );

        // الفاتورة التانية فيها «الفاروق فضي» بس
        $this->assertCount(1, $rows);
        $this->assertSame('FR-01', $rows->first()->sku);
    }

    public function test_the_customer_report_narrows_to_the_matching_invoice(): void
    {
        $rows = app(ReportService::class)->salesByCustomer(
            Carbon::today()->subDays(30)->toDateString(),
            Carbon::today()->toDateString(),
            'S-2026-00001',
        );

        $this->assertCount(1, $rows);
        $this->assertSame(1_500.0, (float) $rows->first()->total);
    }

    /**
     * تقييم المخزون لقطة مالهاش فترة، فالبحث فيه معناه «ورّيني أصناف
     * الفاتورة دي» — ده الفلتر الوحيد اللي ليه معنى هناك.
     */
    public function test_the_inventory_report_narrows_to_the_invoices_products(): void
    {
        $valuation = app(ReportService::class)->inventoryValuation('S-2026-00001');

        $this->assertCount(1, $valuation['rows']);
        $this->assertSame('KB-25', $valuation['rows']->first()['sku']);
    }

    public function test_a_search_that_matches_nothing_empties_the_report(): void
    {
        $report = app(ReportService::class)->salesByPeriod(
            Carbon::today()->subDays(30)->toDateString(),
            Carbon::today()->toDateString(),
            'day',
            'S-2026-09999',
        );

        $this->assertSame([], $report['rows']);
        $this->assertSame(0.0, $report['totals']['total']);
    }

    /** البحث بيفضل في الرابط عشان الصفحة تتبعت وترجع بنفس النتيجة */
    public function test_the_search_is_kept_in_the_url_and_sent_back_to_the_screen(): void
    {
        $this->actingAs($this->user)
            ->get('/reports?report=period&search=00001')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.search', '00001')
                ->has('data.rows', 1)
            );
    }

    public function test_every_report_accepts_the_search_on_screen(): void
    {
        foreach (array_keys(ExportBuilder::REPORT_TITLES) as $report) {
            $this->actingAs($this->user)
                ->get("/reports?report={$report}&search=00001")
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->where('filters.search', '00001')
                );
        }
    }

    // ── تقارير «بالفاتورة» ────────────────────────────────────────

    public function test_the_sale_invoice_report_has_one_row_per_invoice(): void
    {
        $this->actingAs($this->user)
            ->get('/reports?report=sale_invoices')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('data.rows', 2)
                // الأحدث الأول
                ->where('data.rows.0.number', 'S-2026-00002')
                ->where('data.rows.0.party', 'وليد عبد العظيم')
                ->where('data.rows.0.rep', 'سامي المندوب')
                ->where('data.rows.0.statusLabel', 'مؤكّدة')
                // الرقم بيفتح الفاتورة
                ->where('data.rows.0.link', fn (string $link) => str_starts_with($link, '/sales/'))
            );
    }

    public function test_the_sale_invoice_report_totals_the_columns(): void
    {
        $data = app(ReportService::class)->invoiceRows(
            Invoice::TYPE_SALE,
            Carbon::today()->subDays(30)->toDateString(),
            Carbon::today()->toDateString(),
        );

        $this->assertSame(2.0, $data['totals']['count']);
        $this->assertSame(1_900.0, $data['totals']['net']);
        $this->assertSame(1_900.0, $data['totals']['total']);
        $this->assertSame(500.0, $data['totals']['paid']);
        $this->assertSame(1_400.0, $data['totals']['remaining']);
    }

    /**
     * الملغي بيبان في الجدول لكن ما بيتجمعش.
     *
     * لو دخل الإجمالي، تقرير الفواتير ما كانش هيطابق أي تقرير تاني —
     * وكلهم بيعدّوا المؤكّد بس.
     */
    public function test_a_cancelled_invoice_shows_but_is_not_totalled(): void
    {
        Invoice::query()->where('number', 'S-2026-00002')
            ->update(['status' => Invoice::STATUS_CANCELLED]);

        $data = app(ReportService::class)->invoiceRows(
            Invoice::TYPE_SALE,
            Carbon::today()->subDays(30)->toDateString(),
            Carbon::today()->toDateString(),
        );

        $this->assertCount(2, $data['rows']);
        $this->assertSame(1.0, $data['totals']['count']);
        $this->assertSame(1_500.0, $data['totals']['total']);

        $cancelled = collect($data['rows'])->firstWhere('number', 'S-2026-00002');

        $this->assertSame('ملغية', $cancelled['statusLabel']);
    }

    public function test_the_purchase_invoice_report_lists_suppliers(): void
    {
        $supplier = Supplier::create(['name' => 'شركة النور', 'currency' => 'USD']);

        Invoice::create([
            'type' => Invoice::TYPE_PURCHASE,
            'number' => 'P-2026-00001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'date' => Carbon::today()->subDay()->toDateString(),
            'status' => Invoice::STATUS_CONFIRMED,
            'subtotal' => 900, 'total' => 900, 'paid' => 0,
        ]);

        $this->actingAs($this->user)
            ->get('/reports?report=purchase_invoices')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('data.rows', 1)
                ->where('data.rows.0.number', 'P-2026-00001')
                ->where('data.rows.0.party', 'شركة النور')
                ->where('data.rows.0.link', fn (string $link) => str_starts_with($link, '/purchases/'))
            );
    }

    public function test_the_invoice_report_can_be_searched(): void
    {
        $data = app(ReportService::class)->invoiceRows(
            Invoice::TYPE_SALE,
            Carbon::today()->subDays(30)->toDateString(),
            Carbon::today()->toDateString(),
            '00001',
        );

        $this->assertCount(1, $data['rows']);
        $this->assertSame('S-2026-00001', $data['rows'][0]['number']);
    }

    // ── فتح اليوم في تقرير الفترة ─────────────────────────────────

    /**
     * كل سطر يوم شايل فواتيره معاه.
     *
     * الفتح على الشاشة مابيعملش أي نداء — الأرقام جاية مع التقرير أصلاً.
     */
    public function test_each_period_row_carries_its_invoice_numbers(): void
    {
        $report = app(ReportService::class)->salesByPeriod(
            Carbon::today()->subDays(30)->toDateString(),
            Carbon::today()->toDateString(),
        );

        $day = collect($report['rows'])
            ->firstWhere('period', Carbon::today()->subDays(5)->toDateString());

        $this->assertCount(1, $day['invoices']);
        $this->assertSame('S-2026-00001', $day['invoices'][0]['number']);
        $this->assertSame(1_500.0, $day['invoices'][0]['total']);
        $this->assertArrayHasKey('id', $day['invoices'][0]);
    }

    public function test_a_month_row_carries_every_invoice_in_the_month(): void
    {
        $report = app(ReportService::class)->salesByPeriod(
            Carbon::today()->subDays(30)->toDateString(),
            Carbon::today()->toDateString(),
            'month',
        );

        $numbers = collect($report['rows'])
            ->flatMap(fn (array $row) => array_column($row['invoices'], 'number'))
            ->all();

        $this->assertContains('S-2026-00001', $numbers);
        $this->assertContains('S-2026-00002', $numbers);
    }

    // ── مساعدات ───────────────────────────────────────────────────

    private function invoice(
        string $number,
        Carbon $date,
        Product $product,
        float $quantity,
        float $price,
        int $repId,
    ): Invoice {
        $total = $quantity * $price;

        $invoice = Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => $number,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'sales_rep_id' => $repId,
            'date' => $date->toDateString(),
            'status' => Invoice::STATUS_CONFIRMED,
            'subtotal' => $total,
            'tax' => 0,
            'total' => $total,
            'paid' => $number === 'S-2026-00001' ? 500 : 0,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $price,
            'total' => $total,
        ]);

        return $invoice;
    }
}
