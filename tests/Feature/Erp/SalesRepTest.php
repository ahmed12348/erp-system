<?php

namespace Tests\Feature\Erp;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SalesRep;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesRepService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SalesRepTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Warehouse $warehouse;

    private SalesRep $rep;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::create(['name' => 'مخزن']);
        $this->rep = SalesRep::create([
            'name' => 'مندوب الاختبار',
            'region' => 'القاهرة',
            'commission_rate' => 2.5,
        ]);
    }

    /** العمولة على المحصّل مش على المبيعات */
    public function test_commission_is_calculated_on_collections_not_sales(): void
    {
        $customer = $this->customer();

        $this->sale($customer, 100_000);
        $this->receipt($customer, 40_000);

        $summary = app(SalesRepService::class)->summary($this->rep);

        $this->assertSame(100_000.0, $summary['netSales']);
        $this->assertSame(40_000.0, $summary['collected']);
        $this->assertSame(1_000.0, $summary['commission']);   // 2.5% × 40,000
        $this->assertSame(40.0, $summary['collectionRate']);
    }

    public function test_returns_reduce_net_sales(): void
    {
        $customer = $this->customer();

        $this->sale($customer, 50_000);
        $this->sale($customer, 8_000, Invoice::TYPE_SALE_RETURN);

        $summary = app(SalesRepService::class)->summary($this->rep);

        $this->assertSame(50_000.0, $summary['sales']);
        $this->assertSame(8_000.0, $summary['returns']);
        $this->assertSame(42_000.0, $summary['netSales']);
    }

    public function test_outstanding_covers_every_trader_of_the_rep(): void
    {
        $a = $this->customer('تاجر أ');
        $b = $this->customer('تاجر ب');

        $this->sale($a, 10_000);
        $this->sale($b, 25_000);
        $this->receipt($a, 4_000);

        $summary = app(SalesRepService::class)->summary($this->rep);

        $this->assertSame(31_000.0, $summary['outstanding']);
        $this->assertSame(2, $summary['customers']);
    }

    /** مجموع المستحق على المندوبين لازم يساوي إجمالي المديونيات */
    public function test_rep_outstanding_sums_to_total_receivables(): void
    {
        $other = SalesRep::create(['name' => 'مندوب تاني', 'commission_rate' => 2]);

        $mine = $this->customer('تاجري');
        $theirs = Customer::create(['name' => 'تاجرهم', 'city' => 'طنطا', 'sales_rep_id' => $other->id]);

        $this->sale($mine, 10_000);
        $this->sale($theirs, 7_000, Invoice::TYPE_SALE, $other);

        $total = Customer::all()->sum(fn (Customer $c) => $c->balance());
        $repTotal = app(SalesRepService::class)->performance()->sum('outstanding');

        $this->assertSame(17_000.0, round($total, 2));
        $this->assertSame(round($total, 2), round($repTotal, 2));
    }

    public function test_the_date_filter_narrows_sales_and_collections(): void
    {
        $customer = $this->customer();

        $this->sale($customer, 10_000, Invoice::TYPE_SALE, null, Carbon::today()->subDays(40));
        $this->sale($customer, 5_000, Invoice::TYPE_SALE, null, Carbon::today());

        $recent = app(SalesRepService::class)->summary($this->rep, [
            'from' => Carbon::today()->subDays(7)->toDateString(),
            'to' => Carbon::today()->toDateString(),
        ]);

        $this->assertSame(5_000.0, $recent['netSales']);
        // المستحق رصيد قائم فمابيتأثرش بفلتر الفترة
        $this->assertSame(15_000.0, $recent['outstanding']);
    }

    public function test_the_screens_render(): void
    {
        $customer = $this->customer();
        $this->sale($customer, 1_000);

        $this->actingAs($this->user)->get('/reps')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('reps/Index')
                ->has('reps', 1)
            );

        $this->actingAs($this->user)->get("/reps/{$this->rep->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('reps/Show')
                ->has('customers', 1)
                ->has('invoices', 1)
            );
    }

    // ——— مساعدات ———

    private function customer(string $name = 'تاجر'): Customer
    {
        return Customer::create([
            'name' => $name,
            'city' => 'القاهرة',
            'sales_rep_id' => $this->rep->id,
        ]);
    }

    private function sale(Customer $customer, float $total, string $type = Invoice::TYPE_SALE, ?SalesRep $rep = null, ?Carbon $date = null): Invoice
    {
        static $sequence = 0;

        return Invoice::create([
            'type' => $type,
            'number' => 'X-'.(++$sequence),
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'sales_rep_id' => ($rep ?? $this->rep)->id,
            'date' => ($date ?? Carbon::today())->toDateString(),
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'subtotal' => $total,
            'total' => $total,
            'status' => Invoice::STATUS_CONFIRMED,
        ]);
    }

    private function receipt(Customer $customer, float $amount): Payment
    {
        return Payment::create([
            'type' => Payment::TYPE_RECEIPT,
            'customer_id' => $customer->id,
            'sales_rep_id' => $this->rep->id,
            'amount' => $amount,
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'method' => 'cash',
            'date' => Carbon::today()->toDateString(),
            'user_id' => $this->user->id,
        ]);
    }
}
