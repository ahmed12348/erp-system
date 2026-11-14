<?php

namespace Tests\Feature\Erp;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_renders_with_all_its_panels(): void
    {
        $this->makeSale(total: 5000, date: Carbon::today());

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->has('kpis.sales.value')
                ->has('kpis.receivables.value')
                ->has('kpis.inventoryValue.value')
                ->has('kpis.lowStock.value')
                ->has('series.daily', 90)
                ->has('topProducts')
                ->has('topDebtors')
                ->has('latestInvoices')
            );
    }

    /** اليومي والأسبوعي لازم يدّوا نفس الإجمالي — نفس البيانات بتجميعتين */
    public function test_daily_and_weekly_series_agree_on_the_total(): void
    {
        $this->makeSale(1000, Carbon::today());
        $this->makeSale(2500, Carbon::today()->subDays(9));
        $this->makeSale(700, Carbon::today()->subDays(40));

        $series = app(DashboardService::class)->salesSeries();

        $this->assertCount(90, $series['daily']);
        $this->assertSame(
            round(array_sum(array_column($series['daily'], 'total')), 2),
            round(array_sum(array_column($series['weekly'], 'total')), 2),
        );
        $this->assertSame(4200.0, round(array_sum(array_column($series['daily'], 'total')), 2));
    }

    public function test_sales_outside_the_window_are_excluded(): void
    {
        $this->makeSale(1000, Carbon::today());
        $this->makeSale(9999, Carbon::today()->subDays(120));

        $series = app(DashboardService::class)->salesSeries();

        $this->assertSame(1000.0, round(array_sum(array_column($series['daily'], 'total')), 2));
    }

    /** الكارت بيقارن نفس عدد الأيام من الشهر اللي فات، مش الشهر كله */
    public function test_month_change_compares_the_same_day_range(): void
    {
        Carbon::setTestNow('2026-11-13');

        $this->makeSale(1000, Carbon::parse('2026-11-05'));   // الشهر الحالي
        $this->makeSale(500, Carbon::parse('2026-10-05'));    // نفس الفترة الشهر اللي فات
        $this->makeSale(8000, Carbon::parse('2026-10-28'));   // بعد الفترة — المفروض تتستثنى

        $sales = app(DashboardService::class)->kpis()['sales'];

        $this->assertSame(1000.0, $sales['value']);
        $this->assertSame(500.0, $sales['previous']);
        $this->assertSame(100.0, $sales['change']);
        $this->assertSame(13, $sales['periodDays']);

        Carbon::setTestNow();
    }

    public function test_change_is_null_when_there_is_no_baseline(): void
    {
        Carbon::setTestNow('2026-11-13');

        $this->makeSale(1000, Carbon::parse('2026-11-05'));

        $this->assertNull(app(DashboardService::class)->kpis()['sales']['change']);

        Carbon::setTestNow();
    }

    /** الإجمالي المستحق لازم يطابق Customer::balance() بالظبط */
    public function test_receivables_match_the_model_balances(): void
    {
        $invoice = $this->makeSale(total: 10_000, date: Carbon::today());
        $customer = $invoice->customer;
        $customer->update(['opening_balance' => 2_000]);

        Payment::create([
            'type' => Payment::TYPE_RECEIPT,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 3_000,
            'method' => 'cash',
            'date' => Carbon::today()->toDateString(),
            'user_id' => $invoice->user_id,
        ]);

        $expected = $customer->fresh()->balance();

        $this->assertSame(9_000.0, $expected);
        $this->assertSame(
            round($expected, 2),
            round(app(DashboardService::class)->kpis()['receivables']['value'], 2),
        );
    }

    public function test_draft_invoices_are_left_out_of_the_numbers(): void
    {
        $this->makeSale(5_000, Carbon::today(), Invoice::STATUS_DRAFT);

        $kpis = app(DashboardService::class)->kpis();

        $this->assertSame(0.0, $kpis['sales']['value']);
        $this->assertSame(0.0, $kpis['receivables']['value']);
    }

    public function test_top_products_rank_by_revenue(): void
    {
        $cheap = $this->makeSale(0, Carbon::today());
        $this->addItem($cheap, 'كتير ورخيص', quantity: 500, unitPrice: 10);   // 5,000
        $this->addItem($cheap, 'قليل وغالي', quantity: 5, unitPrice: 4_000);  // 20,000

        $top = app(DashboardService::class)->topProducts();

        $this->assertSame('قليل وغالي', $top->first()->name);
        $this->assertSame(20_000.0, (float) $top->first()->revenue);
        $this->assertSame(5.0, (float) $top->first()->quantity);
    }

    public function test_top_debtors_are_ordered_by_balance(): void
    {
        $small = $this->makeSale(1_000, Carbon::today());
        $big = $this->makeSale(50_000, Carbon::today());

        $debtors = app(DashboardService::class)->topDebtors();

        $this->assertSame($big->customer_id, $debtors->first()->id);
        $this->assertSame(50_000.0, (float) $debtors->first()->balance);
        $this->assertSame($small->customer_id, $debtors->last()->id);
    }

    // ——— مساعدات ———

    private function makeSale(float $total, Carbon $date, string $status = Invoice::STATUS_CONFIRMED): Invoice
    {
        static $sequence = 0;

        return Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => 'S-TEST-'.str_pad((string) ++$sequence, 4, '0', STR_PAD_LEFT),
            'customer_id' => Customer::create(['name' => 'تاجر '.$sequence, 'city' => 'القاهرة'])->id,
            'warehouse_id' => Warehouse::firstOrCreate(['name' => 'مخزن الاختبار'])->id,
            'user_id' => User::factory()->create()->id,
            'date' => $date->toDateString(),
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'subtotal' => $total,
            'total' => $total,
            'status' => $status,
        ]);
    }

    private function addItem(Invoice $invoice, string $name, float $quantity, float $unitPrice): void
    {
        $product = Product::create([
            'sku' => 'T-'.fake()->unique()->numerify('#####'),
            'name' => $name,
            'purchase_price' => $unitPrice * 0.7,
            'sale_price' => $unitPrice,
            'min_stock' => 0,
        ]);

        $invoice->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $invoice->warehouse_id,
            'type' => StockMovement::TYPE_OUT,
            'quantity' => -$quantity,
            'unit_cost' => $unitPrice * 0.7,
            'date' => $invoice->date->toDateString(),
        ]);
    }
}
