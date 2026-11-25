<?php

namespace Tests\Feature\Erp;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_the_list_renders_with_balances(): void
    {
        $this->makeSale($this->customer('مؤسسة النيل', 'القاهرة'), 5000);

        $this->actingAs($this->user)
            ->get('/customers')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('customers/Index')
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'مؤسسة النيل')
                ->has('governorates')
            );
    }

    public function test_search_filters_by_name_and_phone(): void
    {
        $this->customer('مؤسسة النيل', 'القاهرة', '01011112222');
        $this->customer('محلات الشروق', 'الجيزة', '01233334444');

        $this->actingAs($this->user)
            ->get('/customers?search=الشروق')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'محلات الشروق')
            );

        $this->actingAs($this->user)
            ->get('/customers?search=0101111')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'مؤسسة النيل')
            );
    }

    public function test_governorate_filter_narrows_the_list(): void
    {
        $this->customer('تاجر القاهرة', 'القاهرة');
        $this->customer('تاجر طنطا', 'الغربية');

        $this->actingAs($this->user)
            ->get('/customers?city='.urlencode('الغربية'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'تاجر طنطا')
            );
    }

    public function test_over_limit_filter_finds_traders_past_their_credit_limit(): void
    {
        $under = $this->customer('تحت الحد', 'القاهرة', creditLimit: 100_000);
        $over = $this->customer('فوق الحد', 'القاهرة', creditLimit: 1_000);

        $this->makeSale($under, 5_000);
        $this->makeSale($over, 5_000);

        $this->actingAs($this->user)
            ->get('/customers?status=over_limit')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'فوق الحد')
            );
    }

    /** الرصيد الجاري في الكشف لازم ينتهي عند نفس رصيد الملخّص */
    public function test_the_statement_running_balance_ends_at_the_summary_balance(): void
    {
        $customer = $this->customer('تاجر الكشف', 'القاهرة', openingBalance: 2_000);

        $this->makeSale($customer, 10_000, Carbon::today()->subDays(10));
        $this->makeReturn($customer, 1_500, Carbon::today()->subDays(5));
        $this->makePayment($customer, 4_000, Carbon::today()->subDays(2));

        $service = app(CustomerService::class);
        $statement = $service->statement($customer);
        $summary = $service->summary($customer);

        // 2,000 + 10,000 - 1,500 - 4,000
        $this->assertSame(6_500.0, $summary['balance']);
        $this->assertSame(6_500.0, $statement['closing']);
        $this->assertSame(2_000.0, $statement['opening']);
        $this->assertCount(3, $statement['rows']);
        $this->assertSame(10_000.0, $statement['totals']['debit']);
        $this->assertSame(5_500.0, $statement['totals']['credit']);
    }

    /** لو فلترنا على فترة، الرصيد الافتتاحي لازم يشيل كل اللي قبلها */
    public function test_a_date_filter_rolls_earlier_movement_into_the_opening_balance(): void
    {
        $customer = $this->customer('تاجر الفترة', 'القاهرة', openingBalance: 1_000);

        $this->makeSale($customer, 7_000, Carbon::today()->subDays(40));
        $this->makeSale($customer, 3_000, Carbon::today()->subDays(3));

        $statement = app(CustomerService::class)->statement(
            $customer,
            Carbon::today()->subDays(10)->toDateString(),
            Carbon::today()->toDateString(),
        );

        $this->assertSame(8_000.0, $statement['opening']);   // 1,000 + 7,000
        $this->assertCount(1, $statement['rows']);
        $this->assertSame(11_000.0, $statement['closing']);
    }

    /**
     * البحث برقم الفاتورة جوه الكشف.
     *
     * «التاجر بيقول إنه دفع الفاتورة الفلانية» سؤال بيتسأل كل يوم، وقبل
     * كده كان بيتجاوب بالتدوير بالعين في كشف من مية سطر.
     */
    public function test_the_statement_can_be_searched_by_invoice_number(): void
    {
        $customer = $this->customer('تاجر البحث', 'القاهرة');

        $wanted = $this->makeSale($customer, 4_000, Carbon::today()->subDays(6));
        $this->makeSale($customer, 2_000, Carbon::today()->subDays(3));
        $this->makePayment($customer, 1_000, Carbon::today()->subDay());

        $statement = app(CustomerService::class)
            ->statement($customer, null, null, $wanted->number);

        $this->assertCount(1, $statement['rows']);
        $this->assertSame($wanted->number, $statement['rows'][0]['reference']);
    }

    /**
     * الأرقام اللي جنب السطر المفلتر لازم تفضل أرقام الكشف الكامل.
     *
     * لو الفلترة حصلت قبل حساب الرصيد الجاري، عمود الرصيد كان هيطلّع
     * أرقام مالهاش معنى — والكشف المحمّل ما كانش هيقفل.
     */
    public function test_searching_keeps_the_real_running_balance_and_totals(): void
    {
        $customer = $this->customer('تاجر الرصيد', 'القاهرة', openingBalance: 1_000);

        $this->makeSale($customer, 5_000, Carbon::today()->subDays(9));
        $second = $this->makeSale($customer, 3_000, Carbon::today()->subDays(4));

        $statement = app(CustomerService::class)
            ->statement($customer, null, null, $second->number);

        $this->assertCount(1, $statement['rows']);
        // 1,000 + 5,000 + 3,000 — مش 1,000 + 3,000
        $this->assertSame(9_000.0, $statement['rows'][0]['balance']);
        $this->assertSame(9_000.0, $statement['closing']);
        $this->assertSame(8_000.0, $statement['totals']['debit']);
    }

    /** والبحث بيوصل من الشاشة نفسها، مش من الخدمة بس */
    public function test_the_trader_page_passes_the_invoice_number_search_through(): void
    {
        $customer = $this->customer('تاجر الشاشة', 'القاهرة');

        $wanted = $this->makeSale($customer, 4_000, Carbon::today()->subDays(2));
        $this->makeSale($customer, 2_000, Carbon::today()->subDay());

        $this->actingAs($this->user)
            ->get("/customers/{$customer->id}?search={$wanted->number}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('customers/Show')
                ->has('statement.rows', 1)
                ->where('statement.rows.0.reference', $wanted->number)
                ->where('filters.search', $wanted->number)
            );
    }

    public function test_a_statement_search_that_matches_nothing_comes_back_empty(): void
    {
        $customer = $this->customer('تاجر فاضي', 'القاهرة');
        $this->makeSale($customer, 1_000);

        $statement = app(CustomerService::class)
            ->statement($customer, null, null, 'S-9999-99999');

        $this->assertSame([], $statement['rows']);
    }

    /** الحركة اللي في نفس اليوم الأخير لازم تدخل في الكشف */
    public function test_the_last_day_of_the_range_is_included(): void
    {
        $customer = $this->customer('تاجر النهارده', 'القاهرة');
        $this->makeSale($customer, 2_500, Carbon::today());

        $statement = app(CustomerService::class)->statement(
            $customer,
            Carbon::today()->toDateString(),
            Carbon::today()->toDateString(),
        );

        $this->assertCount(1, $statement['rows']);
        $this->assertSame(2_500.0, $statement['closing']);
    }

    public function test_recording_a_payment_lowers_the_balance(): void
    {
        $customer = $this->customer('تاجر الدفع', 'القاهرة');
        $this->makeSale($customer, 10_000);

        $this->actingAs($this->user)
            ->post("/customers/{$customer->id}/payments", [
                'amount' => 2_500,
                'method' => 'bank',
                'date' => Carbon::today()->toDateString(),
                'reference' => 'TRF-99',
            ])
            ->assertRedirect();

        $this->assertSame(7_500.0, app(CustomerService::class)->summary($customer)['balance']);
        $this->assertDatabaseHas('payments', [
            'customer_id' => $customer->id,
            'type' => Payment::TYPE_RECEIPT,
            'method' => 'bank',
            'reference' => 'TRF-99',
        ]);
    }

    public function test_a_payment_against_an_invoice_updates_the_invoice_paid_amount(): void
    {
        $customer = $this->customer('تاجر الفاتورة', 'القاهرة');
        $invoice = $this->makeSale($customer, 10_000);

        $this->actingAs($this->user)
            ->post("/customers/{$customer->id}/payments", [
                'amount' => 4_000,
                'method' => 'cash',
                'date' => Carbon::today()->toDateString(),
                'invoice_id' => $invoice->id,
            ])
            ->assertRedirect();

        $this->assertSame(4_000.0, (float) $invoice->fresh()->paid);
        $this->assertSame(6_000.0, $invoice->fresh()->remaining);
    }

    public function test_a_payment_cannot_be_attached_to_another_traders_invoice(): void
    {
        $mine = $this->customer('تاجر أ', 'القاهرة');
        $theirs = $this->customer('تاجر ب', 'القاهرة');
        $foreign = $this->makeSale($theirs, 5_000);

        $this->actingAs($this->user)
            ->post("/customers/{$mine->id}/payments", [
                'amount' => 1_000,
                'method' => 'cash',
                'date' => Carbon::today()->toDateString(),
                'invoice_id' => $foreign->id,
            ])
            ->assertSessionHasErrors('invoice_id');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_validation_rejects_bad_input_with_arabic_messages(): void
    {
        $customer = $this->customer('تاجر التحقق', 'القاهرة');

        $response = $this->actingAs($this->user)
            ->post("/customers/{$customer->id}/payments", [
                'amount' => 0,
                'method' => 'bitcoin',
                'date' => Carbon::tomorrow()->toDateString(),
            ]);

        $response->assertSessionHasErrors(['amount', 'method', 'date']);

        $errors = session('errors');
        $this->assertSame('المبلغ لازم يكون أكبر من صفر.', $errors->first('amount'));
        $this->assertSame('ماينفعش تسجّل دفعة بتاريخ في المستقبل.', $errors->first('date'));
    }

    public function test_the_printable_statement_renders_without_the_app_shell(): void
    {
        $customer = $this->customer('تاجر الطباعة', 'القاهرة');
        $this->makeSale($customer, 3_000);

        $this->actingAs($this->user)
            ->get("/customers/{$customer->id}/statement")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('print/CustomerStatement')
                // الترويسة بقت بيانات الشركة كاملة مش الاسم لوحده
                ->where('company.name', config('app.name'))
                ->has('statement.rows', 1)
            );
    }

    // ——— مساعدات ———

    private function customer(string $name, string $city, ?string $phone = null, ?float $creditLimit = null, float $openingBalance = 0): Customer
    {
        return Customer::create([
            'name' => $name,
            'city' => $city,
            'phone' => $phone,
            'credit_limit' => $creditLimit,
            'opening_balance' => $openingBalance,
        ]);
    }

    private function makeSale(Customer $customer, float $total, ?Carbon $date = null): Invoice
    {
        return $this->invoice($customer, Invoice::TYPE_SALE, $total, $date);
    }

    private function makeReturn(Customer $customer, float $total, ?Carbon $date = null): Invoice
    {
        return $this->invoice($customer, Invoice::TYPE_SALE_RETURN, $total, $date);
    }

    private function invoice(Customer $customer, string $type, float $total, ?Carbon $date = null): Invoice
    {
        static $sequence = 0;

        return Invoice::create([
            'type' => $type,
            'number' => 'T-'.str_pad((string) ++$sequence, 5, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'warehouse_id' => Warehouse::firstOrCreate(['name' => 'مخزن الاختبار'])->id,
            'user_id' => $this->user->id,
            'date' => ($date ?? Carbon::today())->toDateString(),
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'subtotal' => $total,
            'total' => $total,
            'status' => Invoice::STATUS_CONFIRMED,
        ]);
    }

    private function makePayment(Customer $customer, float $amount, ?Carbon $date = null): Payment
    {
        return Payment::create([
            'type' => Payment::TYPE_RECEIPT,
            'customer_id' => $customer->id,
            'amount' => $amount,
            'method' => 'cash',
            'date' => ($date ?? Carbon::today())->toDateString(),
            'user_id' => $this->user->id,
        ]);
    }
}
