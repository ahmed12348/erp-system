<?php

namespace Tests\Feature\Erp;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\ReportService;
use App\Services\TreasuryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class InventoryTreasuryReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Warehouse $main;

    private Warehouse $alt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->main = Warehouse::create(['name' => 'الرئيسي']);
        $this->alt = Warehouse::create(['name' => 'الإسكندرية']);
    }

    // ——— المخزون ———

    public function test_stock_is_reported_per_warehouse_and_in_total(): void
    {
        $product = Product::create([
            'sku' => 'A-1', 'name' => 'صنف', 'purchase_price' => 100,
            'sale_price' => 150, 'min_stock' => 0,
        ]);

        $this->move($product, $this->main, 30);
        $this->move($product, $this->alt, 12);
        $this->move($product, $this->main, -5);

        $row = app(InventoryService::class)->stockRows()->firstOrFail();

        $this->assertSame(37.0, $row['total']);
        $this->assertSame(25.0, collect($row['warehouses'])->firstWhere('id', $this->main->id)['qty']);
        $this->assertSame(12.0, collect($row['warehouses'])->firstWhere('id', $this->alt->id)['qty']);
        $this->assertSame(3_700.0, $row['value']);
    }

    public function test_the_low_stock_filter_only_returns_products_under_their_minimum(): void
    {
        $low = Product::create(['sku' => 'L-1', 'name' => 'ناقص', 'purchase_price' => 10, 'sale_price' => 15, 'min_stock' => 50]);
        $fine = Product::create(['sku' => 'F-1', 'name' => 'كويس', 'purchase_price' => 10, 'sale_price' => 15, 'min_stock' => 5]);

        $this->move($low, $this->main, 10);
        $this->move($fine, $this->main, 80);

        $rows = app(InventoryService::class)->stockRows(['low' => '1']);

        $this->assertCount(1, $rows);
        $this->assertSame('ناقص', $rows->first()['name']);
    }

    public function test_the_movement_history_carries_a_running_balance(): void
    {
        $product = Product::create(['sku' => 'M-1', 'name' => 'حركة', 'purchase_price' => 10, 'sale_price' => 15, 'min_stock' => 0]);

        $this->move($product, $this->main, 100, Carbon::today()->subDays(3));
        $this->move($product, $this->main, -30, Carbon::today()->subDays(2));
        $this->move($product, $this->main, -20, Carbon::today()->subDay());

        $history = app(InventoryService::class)->movementHistory($product);

        $this->assertSame(50.0, $history['product']['balance']);
        // الأحدث أول — الرصيد الجاري بيتحسب من الأقدم للأحدث
        $this->assertSame(50.0, $history['rows'][0]['balance']);
        $this->assertSame(100.0, $history['rows'][2]['balance']);
    }

    public function test_movement_history_can_be_scoped_to_one_warehouse(): void
    {
        $product = Product::create(['sku' => 'W-1', 'name' => 'مخزنين', 'purchase_price' => 10, 'sale_price' => 15, 'min_stock' => 0]);

        $this->move($product, $this->main, 40);
        $this->move($product, $this->alt, 60);

        $scoped = app(InventoryService::class)->movementHistory($product, $this->main->id);

        $this->assertCount(1, $scoped['rows']);
        $this->assertSame(40.0, $scoped['product']['balance']);
    }

    // ——— الخزينة ———

    /** المدفوعات بعملات مختلفة لازم تتجمع بالجنيه مش بالقيمة الخام */
    public function test_treasury_totals_convert_foreign_payments_to_egp(): void
    {
        $customer = Customer::create(['name' => 'تاجر', 'city' => 'القاهرة']);

        Payment::create([
            'type' => Payment::TYPE_RECEIPT, 'customer_id' => $customer->id,
            'amount' => 10_000, 'currency' => 'EGP', 'exchange_rate' => 1,
            'method' => 'cash', 'date' => Carbon::today()->toDateString(), 'user_id' => $this->user->id,
        ]);

        Payment::create([
            'type' => Payment::TYPE_DISBURSEMENT, 'supplier_id' => null,
            'amount' => 100, 'currency' => 'USD', 'exchange_rate' => 50,
            'method' => 'bank', 'date' => Carbon::today()->toDateString(), 'user_id' => $this->user->id,
        ]);

        $totals = app(TreasuryService::class)->totals();

        $this->assertSame(10_000.0, $totals['in']);
        $this->assertSame(5_000.0, $totals['out']);   // 100 × 50
        $this->assertSame(5_000.0, $totals['net']);
    }

    public function test_the_treasury_page_renders_with_rows(): void
    {
        $customer = Customer::create(['name' => 'تاجر', 'city' => 'القاهرة']);

        Payment::create([
            'type' => Payment::TYPE_RECEIPT, 'customer_id' => $customer->id,
            'amount' => 500, 'currency' => 'EGP', 'exchange_rate' => 1,
            'method' => 'cash', 'date' => Carbon::today()->toDateString(), 'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)->get('/treasury')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('treasury/Index')
                ->has('rows', 1)
                ->where('totals.in', 500)
                ->has('byMethod')
            );
    }

    // ——— التقارير ———

    public function test_sales_by_period_groups_by_day_and_month(): void
    {
        $customer = Customer::create(['name' => 'تاجر', 'city' => 'القاهرة']);

        $this->sale($customer, 1_000, Carbon::parse('2026-10-05'));
        $this->sale($customer, 2_000, Carbon::parse('2026-10-05'));
        $this->sale($customer, 3_000, Carbon::parse('2026-10-20'));

        $service = app(ReportService::class);

        $daily = $service->salesByPeriod('2026-10-01', '2026-10-31', 'day');
        $this->assertCount(2, $daily['rows']);
        $this->assertSame(6_000.0, $daily['totals']['total']);

        $monthly = $service->salesByPeriod('2026-10-01', '2026-10-31', 'month');
        $this->assertCount(1, $monthly['rows']);
        $this->assertSame('2026-10', $monthly['rows'][0]['period']);
        $this->assertSame(6_000.0, $monthly['totals']['total']);
    }

    public function test_draft_invoices_are_excluded_from_reports(): void
    {
        $customer = Customer::create(['name' => 'تاجر', 'city' => 'القاهرة']);

        $this->sale($customer, 1_000, Carbon::today());
        $this->sale($customer, 9_000, Carbon::today(), Invoice::STATUS_DRAFT);

        $report = app(ReportService::class)->salesByPeriod(
            Carbon::today()->toDateString(),
            Carbon::today()->toDateString(),
        );

        $this->assertSame(1_000.0, $report['totals']['total']);
    }

    public function test_profit_per_product_subtracts_cost(): void
    {
        $customer = Customer::create(['name' => 'تاجر', 'city' => 'القاهرة']);
        $product = Product::create(['sku' => 'P-9', 'name' => 'صنف', 'purchase_price' => 60, 'sale_price' => 100, 'min_stock' => 0]);

        $invoice = $this->sale($customer, 1_000, Carbon::today());
        $invoice->items()->create([
            'product_id' => $product->id, 'quantity' => 10,
            'unit_price' => 100, 'total' => 1_000,
        ]);

        $row = app(ReportService::class)->salesByProduct(
            Carbon::today()->toDateString(),
            Carbon::today()->toDateString(),
        )->firstOrFail();

        $this->assertSame(1_000.0, (float) $row->revenue);
        $this->assertSame(600.0, (float) $row->cost);
        $this->assertSame(400.0, $row->profit);
        $this->assertSame(40.0, $row->margin);
    }

    public function test_inventory_valuation_prices_at_cost_and_retail(): void
    {
        $product = Product::create(['sku' => 'V-1', 'name' => 'صنف', 'purchase_price' => 100, 'sale_price' => 150, 'min_stock' => 0]);
        $this->move($product, $this->main, 10);

        $valuation = app(ReportService::class)->inventoryValuation();

        $this->assertSame(1_000.0, $valuation['totals']['cost']);
        $this->assertSame(1_500.0, $valuation['totals']['retail']);
        $this->assertSame(1, $valuation['totals']['items']);
    }

    public function test_every_report_and_its_print_view_render(): void
    {
        foreach (['period', 'product', 'customer', 'inventory'] as $report) {
            $this->actingAs($this->user)->get("/reports?report={$report}")
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('reports/Index')
                    ->where('report', $report)
                );

            $this->actingAs($this->user)->get("/reports/print?report={$report}")
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page->component('print/Report'));
        }
    }

    public function test_an_unknown_report_falls_back_to_the_period_report(): void
    {
        $this->actingAs($this->user)->get('/reports?report=hack')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('report', 'period'));
    }

    public function test_the_inventory_screens_render(): void
    {
        $product = Product::create(['sku' => 'S-1', 'name' => 'صنف', 'purchase_price' => 10, 'sale_price' => 15, 'min_stock' => 0]);
        $this->move($product, $this->main, 5);

        $this->actingAs($this->user)->get('/inventory')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('inventory/Index')
                ->has('rows', 1)
                ->where('summary.value', 50)
            );

        $this->actingAs($this->user)->get("/inventory/{$product->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('inventory/Show')
                ->has('rows', 1)
            );
    }

    // ——— مساعدات ———

    private function move(Product $product, Warehouse $warehouse, float $qty, ?Carbon $date = null): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => $qty >= 0 ? StockMovement::TYPE_IN : StockMovement::TYPE_OUT,
            'quantity' => $qty,
            'unit_cost' => $product->purchase_price,
            'date' => ($date ?? Carbon::today())->toDateString(),
        ]);
    }

    private function sale(Customer $customer, float $total, Carbon $date, string $status = Invoice::STATUS_CONFIRMED): Invoice
    {
        static $sequence = 0;

        return Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => 'R-'.(++$sequence),
            'customer_id' => $customer->id,
            'warehouse_id' => $this->main->id,
            'user_id' => $this->user->id,
            'date' => $date->toDateString(),
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'subtotal' => $total,
            'tax' => 0,
            'total' => $total,
            'paid' => 0,
            'status' => $status,
        ]);
    }
}
