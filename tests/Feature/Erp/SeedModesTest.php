<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\BaselineSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ErpUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SeedModesTest extends TestCase
{
    use RefreshDatabase;

    /** الوضع الافتراضي: مستخدمين وأمثلة بس — من غير أي حركة */
    public function test_the_default_seed_leaves_the_system_clean(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::count());
        $this->assertSame(1, Warehouse::count());
        $this->assertSame(1, Category::count());
        $this->assertSame(1, Product::count());
        $this->assertSame(1, Customer::count());
        $this->assertSame(1, Supplier::count());

        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, StockMovement::count());
    }

    public function test_the_default_seed_creates_the_three_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['admin@erp.test', 'accountant@erp.test', 'sales@erp.test'] as $email) {
            $user = User::where('email', $email)->sole();

            $this->assertNotNull($user->roles->first(), "المستخدم {$email} من غير دور.");
            $this->assertSame(ErpUserSeeder::COMPANY_NAME, $user->currentTeam?->name);
        }
    }

    /** تشغيل السيدر مرتين ما يكرّرش حاجة */
    public function test_the_default_seed_is_safe_to_run_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::count());
        $this->assertSame(1, Product::count());
        $this->assertSame(1, Customer::count());
    }

    /** لوحة التحكم على نظام نضيف: شاشة ترحيب بخطوات التشغيل، مش أصفار */
    public function test_the_dashboard_shows_the_setup_screen_when_there_is_no_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs(User::first())
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('setup.hasData', false)
                ->has('setup.steps', 5)
                // الأصناف والموردين والتجار موجودين من BaselineSeeder
                ->where('setup.steps.0.done', true)
                ->where('setup.steps.3.done', false)  // فاتورة شراء
                ->where('setup.steps.4.done', false)  // فاتورة بيع
            );
    }

    /** أول ما تدخل فاتورة، اللوحة بتشتغل عادي */
    public function test_the_dashboard_switches_to_the_real_view_once_an_invoice_exists(): void
    {
        $this->seed(DatabaseSeeder::class);

        Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => 'S-2026-00001',
            'customer_id' => Customer::first()->id,
            'warehouse_id' => Warehouse::first()->id,
            'user_id' => User::first()->id,
            'date' => now()->toDateString(),
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'subtotal' => 100,
            'total' => 100,
            'paid' => 0,
            'status' => Invoice::STATUS_CONFIRMED,
        ]);

        $this->actingAs(User::first())
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('setup.hasData', true)
                ->has('kpis')
                ->has('series.daily')
            );
    }

    /** الشاشات الفاضية بتبعت hasAny = false عشان تعرض زرار إجراء مش «غيّر الفلاتر» */
    public function test_the_list_screens_report_that_they_have_no_records_yet(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::first();

        $this->actingAs($user)->get('/sales')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('hasAny', false));

        $this->actingAs($user)->get('/purchases')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('hasAny', false));

        $this->actingAs($user)->get('/treasury')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('hasAny', false));

        // دول فيهم مثال واحد من BaselineSeeder
        $this->actingAs($user)->get('/customers')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('hasAny', true));

        $this->actingAs($user)->get('/inventory')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('hasAny', true));
    }

    /** BaselineSeeder لوحده ما بيعملش مستخدمين — ده شغل ErpUserSeeder */
    public function test_the_baseline_seeder_only_creates_example_records(): void
    {
        $this->seed(BaselineSeeder::class);

        $this->assertSame(0, User::count());
        $this->assertSame(1, Product::count());
        $this->assertSame(0, Invoice::count());
    }
}
