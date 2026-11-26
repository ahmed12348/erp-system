<?php

namespace Tests\Feature\Erp;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\SalesRep;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ExpenseService;
use App\Services\SalesRepService;
use App\Services\TreasuryService;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * المصروفات وشجرة تصنيفاتها.
 *
 * الحتة المهمة: المصروف بيطلع من الخزينة فعلاً، وبيدخل في صافي الربح —
 * مش جدول منفصل بيتعرض لوحده.
 */
class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $this->seed(ExpenseCategorySeeder::class);
    }

    // ── الشجرة المزروعة ───────────────────────────────────────────

    public function test_the_seeded_tree_has_both_groups_with_their_items(): void
    {
        $groups = ExpenseCategory::query()->roots()->orderBy('sort_order')->get();

        $this->assertSame(['مصروفات ثابتة', 'مصروفات متغيرة'], $groups->pluck('name')->all());

        $fixed = $groups->first()->children()->pluck('name')->all();

        $this->assertSame(['إيجار', 'مرتبات', 'كهرباء ومياه', 'إنترنت'], $fixed);

        $variable = $groups->last()->children()->pluck('name')->all();

        $this->assertSame(
            ['نقل وشحن', 'صيانة', 'دعاية', 'ضيافة', 'أخرى'],
            $variable,
        );
    }

    /**
     * الأكواد مقصودة — دي اللي هتربط التصنيفات بدليل حسابات بعدين.
     */
    public function test_the_seeded_tree_carries_account_codes(): void
    {
        $this->assertSame('5100', ExpenseCategory::where('name', 'مصروفات ثابتة')->value('code'));
        $this->assertSame('5110', ExpenseCategory::where('name', 'إيجار')->value('code'));
        $this->assertSame('5200', ExpenseCategory::where('name', 'مصروفات متغيرة')->value('code'));
        $this->assertSame('5290', ExpenseCategory::where('name', 'أخرى')->value('code'));
    }

    /** السيدر بيتشغّل على السيرفر، فلازم يبقى آمن لو اتكرر */
    public function test_the_seeder_is_safe_to_run_twice(): void
    {
        $before = ExpenseCategory::count();

        $this->seed(ExpenseCategorySeeder::class);

        $this->assertSame($before, ExpenseCategory::count());
    }

    /** وما بيلغيش أي تعديل العميل عمله على الأسماء */
    public function test_the_seeder_keeps_renamed_categories(): void
    {
        ExpenseCategory::where('code', '5110')->update(['name' => 'إيجار المخزن']);

        $this->seed(ExpenseCategorySeeder::class);

        $this->assertSame('إيجار المخزن', ExpenseCategory::where('code', '5110')->value('name'));
    }

    // ── تسجيل مصروف ───────────────────────────────────────────────

    public function test_recording_an_expense_takes_money_out_of_the_treasury(): void
    {
        $before = app(TreasuryService::class)->totals();

        $this->postExpense(2_500)->assertRedirect()->assertSessionHasNoErrors();

        $expense = Expense::sole();
        $after = app(TreasuryService::class)->totals();

        $this->assertSame('EXP-'.Carbon::today()->year.'-00001', $expense->number);
        $this->assertSame(2_500.0, (float) $expense->amount);

        // سند صرف في الخزينة، والصافي نزل بنفس المبلغ
        $this->assertNotNull($expense->payment_id);
        $this->assertSame(Payment::TYPE_EXPENSE, $expense->payment->type);
        $this->assertSame(round($before['out'] + 2_500, 2), $after['out']);
        $this->assertSame(round($before['net'] - 2_500, 2), $after['net']);
    }

    /** المصروف بيبان في كشف الخزينة باسم تصنيفه */
    public function test_the_expense_shows_in_the_treasury_ledger(): void
    {
        $this->postExpense(800)->assertRedirect();

        $row = collect(app(TreasuryService::class)->ledger()['rows'])->first();

        $this->assertSame('إيجار', $row['party']);
        $this->assertSame(-800.0, $row['signedEgp']);
    }

    /**
     * المصروف مابيأثّرش على عمولة المندوب.
     *
     * العمولة على المحصّل ناقص المردود (`disbursement`) — والمصروف نوع
     * تالت عن قصد عشان ما يدخلش في الحسبة دي.
     */
    public function test_an_expense_does_not_touch_rep_commission(): void
    {
        $rep = SalesRep::create(['name' => 'سامي', 'commission_rate' => 10]);

        Payment::create([
            'type' => Payment::TYPE_RECEIPT,
            'amount' => 1_000,
            'method' => 'cash',
            'date' => Carbon::today()->toDateString(),
            'user_id' => $this->admin->id,
            'sales_rep_id' => $rep->id,
        ]);

        $before = app(SalesRepService::class)->summary($rep);

        $this->postExpense(500)->assertRedirect();

        $after = app(SalesRepService::class)->summary($rep->fresh());

        $this->assertSame($before['commission'], $after['commission']);
        $this->assertSame(100.0, $after['commission']);
    }

    /**
     * المصروف بيتسجّل على بند مش على مجموعة.
     *
     * لو سمحنا بالمجموعة، التقرير بالتصنيف بيطلع فيه سطر مجموعة جنب
     * بنودها ومحدش عارف يجمع.
     */
    public function test_an_expense_cannot_be_booked_on_a_group(): void
    {
        $group = ExpenseCategory::where('code', '5100')->sole();

        $this->actingAs($this->admin)->post('/expenses', [
            'expense_category_id' => $group->id,
            'date' => Carbon::today()->toDateString(),
            'amount' => 100,
            'method' => 'cash',
        ])->assertSessionHasErrors('expense_category_id');

        $this->assertSame(0, Expense::count());
    }

    public function test_a_zero_amount_is_refused(): void
    {
        $this->postExpense(0)->assertSessionHasErrors('amount');
    }

    public function test_only_admins_and_accountants_can_record(): void
    {
        $this->actingAs($this->salesUser())->get('/expenses')->assertForbidden();

        $this->actingAs($this->accountant())->get('/expenses')->assertOk();
    }

    // ── التقرير ───────────────────────────────────────────────────

    public function test_the_report_groups_by_category(): void
    {
        $this->postExpense(1_000, '5110');   // إيجار — ثابتة
        $this->postExpense(400, '5210');     // نقل وشحن — متغيرة
        $this->postExpense(600, '5210');

        $report = app(ExpenseService::class)->byCategory();

        $fixed = collect($report['rows'])->firstWhere('name', 'مصروفات ثابتة');
        $variable = collect($report['rows'])->firstWhere('name', 'مصروفات متغيرة');

        $this->assertSame(1_000.0, $fixed['total']);
        $this->assertSame(1_000.0, $variable['total']);
        $this->assertSame(2_000.0, $report['total']);

        $transport = collect($variable['children'])->firstWhere('name', 'نقل وشحن');

        $this->assertSame(1_000.0, $transport['total']);
        $this->assertSame(2, $transport['entries']);
    }

    public function test_the_report_downloads_as_pdf_and_excel(): void
    {
        $this->postExpense(1_000);

        $this->actingAs($this->admin)
            ->get('/exports/report?report=expenses')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->admin)
            ->get('/exports/report?report=expenses&format=excel')
            ->assertOk();
    }

    public function test_the_report_screen_renders(): void
    {
        $this->actingAs($this->admin)
            ->get('/reports?report=expenses')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('report', 'expenses')
                ->has('data.rows')
            );
    }

    /** الفلتر على مجموعة بيجيب بنودها كلها */
    public function test_filtering_by_a_group_includes_its_items(): void
    {
        $this->postExpense(1_000, '5110');
        $this->postExpense(400, '5210');

        $group = ExpenseCategory::where('code', '5100')->sole();

        $this->actingAs($this->admin)
            ->get("/expenses?category_id={$group->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('expenses.data', 1));
    }

    // ── صافي الربح ────────────────────────────────────────────────

    /**
     * صافي الربح = مجمل الربح ناقص المصروفات.
     *
     * من غير الطرح ده، اللوحة بتقول إن الشهر كويس وصاحب المحل لسه
     * مادفعش الإيجار.
     */
    public function test_expenses_lower_the_net_profit_on_the_dashboard(): void
    {
        $before = app(DashboardService::class)->kpis()['profit'];

        $this->postExpense(1_500)->assertRedirect();

        $after = app(DashboardService::class)->kpis()['profit'];

        $this->assertSame($before['gross'], $after['gross']);
        $this->assertSame(1_500.0, $after['expenses']);
        $this->assertSame(round($before['net'] - 1_500, 2), $after['net']);
    }

    // ── شجرة التصنيفات ────────────────────────────────────────────

    public function test_an_admin_can_add_and_rename_a_category(): void
    {
        $group = ExpenseCategory::where('code', '5100')->sole();

        $this->actingAs($this->admin)->post('/expenses/categories', [
            'parent_id' => $group->id,
            'name' => 'تأمينات',
            'code' => '5150',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $created = ExpenseCategory::where('code', '5150')->sole();

        $this->assertSame($group->id, $created->parent_id);

        $this->actingAs($this->admin)
            ->put("/expenses/categories/{$created->id}", [
                'name' => 'تأمينات اجتماعية',
                'code' => '5150',
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertSame('تأمينات اجتماعية', $created->fresh()->name);
        $this->assertFalse($created->fresh()->is_active);
    }

    public function test_a_duplicate_code_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post('/expenses/categories', ['name' => 'تاني', 'code' => '5110'])
            ->assertSessionHasErrors('code');
    }

    /** التصنيف اللي عليه مصروفات بيتوقف مش بيتمسح */
    public function test_a_category_with_expenses_cannot_be_deleted(): void
    {
        $this->postExpense(300, '5110');

        $category = ExpenseCategory::where('code', '5110')->sole();

        $this->actingAs($this->admin)
            ->delete("/expenses/categories/{$category->id}")
            ->assertSessionHasErrors('category');

        $this->assertNotNull($category->fresh());
    }

    public function test_an_empty_category_can_be_deleted(): void
    {
        $this->actingAs($this->admin)
            ->post('/expenses/categories', ['name' => 'مؤقت', 'code' => '5999']);

        $category = ExpenseCategory::where('code', '5999')->sole();

        $this->actingAs($this->admin)
            ->delete("/expenses/categories/{$category->id}")
            ->assertRedirect();

        $this->assertNull(ExpenseCategory::find($category->id));
    }

    public function test_only_an_admin_can_edit_the_tree(): void
    {
        $this->actingAs($this->accountant())
            ->get('/expenses/categories')
            ->assertForbidden();
    }

    // ── مساعدات ───────────────────────────────────────────────────

    private function postExpense(float $amount, string $code = '5110')
    {
        return $this->actingAs($this->admin)->post('/expenses', [
            'expense_category_id' => ExpenseCategory::where('code', $code)->value('id'),
            'date' => Carbon::today()->toDateString(),
            'amount' => $amount,
            'method' => 'cash',
            'paid_to' => 'المالك',
        ]);
    }
}
