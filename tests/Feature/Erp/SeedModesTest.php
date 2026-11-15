<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesRep;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ErpUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SeedModesTest extends TestCase
{
    use RefreshDatabase;

    /** الوضع الافتراضي: مستخدمين وبس — ولا سجل بيانات واحد */
    public function test_the_default_seed_creates_users_and_nothing_else(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::count());

        foreach ([
            Warehouse::class,
            Category::class,
            Product::class,
            Customer::class,
            Supplier::class,
            SalesRep::class,
            Invoice::class,
            Payment::class,
            StockMovement::class,
        ] as $model) {
            $this->assertSame(0, $model::count(), $model.' المفروض يكون فاضي.');
        }
    }

    public function test_the_default_seed_creates_the_three_roles_and_the_company_team(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expected = [
            'admin@erp.test' => 'admin',
            'accountant@erp.test' => 'accountant',
            'sales@erp.test' => 'sales',
        ];

        foreach ($expected as $email => $role) {
            $user = User::where('email', $email)->sole();

            $this->assertTrue($user->hasRole($role), "المستخدم {$email} المفروض يكون دوره {$role}.");
            $this->assertSame(ErpUserSeeder::COMPANY_NAME, $user->currentTeam?->name);
            $this->assertNotNull($user->currentTeam?->slug, 'الفريق لازم يكون له slug.');

            // من غير تفعيل الإيميل الـ middleware بتاع verified هيقفل النظام
            $this->assertTrue($user->hasVerifiedEmail(), "إيميل {$email} مش مفعّل.");
        }
    }

    /**
     * السيدر بيشتغل على السيرفر بعد composer install --no-dev، يعني من غير
     * Faker. الاختبار ده بيقفل الباب على إن حد يرجّع factory() تاني.
     */
    public function test_the_default_seeders_never_touch_factories_or_faker(): void
    {
        foreach (['DatabaseSeeder', 'ErpUserSeeder'] as $seeder) {
            $source = file_get_contents(database_path("seeders/{$seeder}.php"));

            // بنشيل التعليقات الأول عشان الكلام المكتوب في الشرح ما يضلّلش
            $code = preg_replace('#/\*.*?\*/|//.*#s', '', $source);

            $this->assertStringNotContainsString('factory(', $code, "{$seeder} بيستخدم factory.");
            $this->assertStringNotContainsString('fake(', $code, "{$seeder} بيستخدم fake.");
            $this->assertStringNotContainsString('Faker', $code, "{$seeder} بيستخدم Faker.");
        }
    }

    /** تشغيل السيدر مرتين ما يكرّرش حاجة */
    public function test_the_default_seed_is_safe_to_run_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::count());
        $this->assertSame(3, Team::count());
    }

    /** لوحة التحكم على نظام فاضي: شاشة ترحيب بخطوات التشغيل، مش أصفار */
    public function test_the_dashboard_shows_the_setup_screen_when_there_is_no_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs(User::first())
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('setup.hasData', false)
                ->has('setup.steps', 5)
                // كله لسه ماتعملش — النظام فاضي تمامًا
                ->where('setup.steps.0.done', false)
                ->where('setup.steps.3.done', false)
                ->where('setup.steps.4.done', false)
            );
    }

    /** أول ما تدخل فاتورة، اللوحة بتشتغل عادي */
    public function test_the_dashboard_switches_to_the_real_view_once_an_invoice_exists(): void
    {
        $this->seed(DatabaseSeeder::class);

        $warehouse = Warehouse::create(['name' => 'مخزن']);
        $customer = Customer::create(['name' => 'تاجر', 'price_tier' => 'sale']);

        Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => 'S-2026-00001',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
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

    /** كل شاشات القوايم بتفتح على نظام فاضي من غير ما تقع */
    public function test_every_list_screen_opens_with_zero_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::first();

        $screens = [
            '/dashboard', '/sales', '/sales/create', '/purchases', '/purchases/create',
            '/inventory', '/warehouses', '/customers', '/suppliers', '/reps',
            '/treasury', '/reports',
        ];

        foreach ($screens as $screen) {
            $this->actingAs($user)->get($screen)->assertOk();
        }
    }

    /** الشاشات اللي فيها فلاتر بتقول «لسه مافيش» مش «غيّر الفلاتر» */
    public function test_the_list_screens_report_that_they_have_no_records_yet(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::first();

        foreach (['/sales', '/purchases', '/treasury', '/customers', '/inventory'] as $screen) {
            $this->actingAs($user)->get($screen)
                ->assertInertia(fn (AssertableInertia $page) => $page->where('hasAny', false));
        }
    }
}
