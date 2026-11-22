<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * سجل النشاط: الدخول، وحركات البيزنس، والصلاحيات، والتقليم.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Warehouse $warehouse;

    private Product $product;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $category = Category::create(['name' => 'أجهزة منزلية']);

        $this->customer = Customer::create([
            'name' => 'محمد عبد الله',
            'price_tier' => 'sale',
        ]);

        $this->product = Product::create([
            'sku' => 'HA-9001',
            'name' => 'خلاط كهربائي',
            'category_id' => $category->id,
            'unit' => 'قطعة',
            'purchase_price' => 100,
            'sale_price' => 180,
            'min_stock' => 5,
        ]);

        Activity::query()->delete();
    }

    public function test_a_login_is_recorded_with_ip_and_browser(): void
    {
        $user = User::factory()->create([
            'email' => 'tester@erp.test',
            'password' => Hash::make('password-1234'),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '197.44.1.9'])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0'])
            ->post('/login', [
                'email' => 'tester@erp.test',
                'password' => 'password-1234',
            ]);

        $activity = Activity::where('log_name', 'auth')->where('event', 'login')->sole();

        $this->assertSame($user->id, $activity->causer_id);
        $this->assertSame('197.44.1.9', $activity->properties['ip']);
        $this->assertStringContainsString('Chrome', $activity->properties['agent']);

        // وآخر دخول اتسجّل على المستخدم نفسه
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_a_failed_login_is_recorded_without_the_password(): void
    {
        User::factory()->create([
            'email' => 'tester@erp.test',
            'password' => Hash::make('password-1234'),
        ]);

        $this->post('/login', [
            'email' => 'tester@erp.test',
            'password' => 'wrong-password',
        ]);

        $activity = Activity::where('event', 'login_failed')->sole();

        $this->assertSame('tester@erp.test', $activity->properties['email']);

        // الباسورد عمره ما بيدخل السجل
        $this->assertStringNotContainsString(
            'wrong-password',
            $activity->properties->toJson(),
        );
    }

    public function test_the_login_does_not_log_a_user_update(): void
    {
        User::factory()->create([
            'email' => 'tester@erp.test',
            'password' => Hash::make('password-1234'),
        ]);

        $this->post('/login', [
            'email' => 'tester@erp.test',
            'password' => 'password-1234',
        ]);

        /*
         * `last_login_at` بيتغيّر كل دخول — ما ينفعش يبقى سطر «عدّل
         * مستخدم». (سطر «أضاف مستخدم» من إنشاء الحساب فوق متوقّع.)
         */
        $this->assertSame(
            0,
            Activity::where('log_name', 'user')->where('event', 'updated')->count(),
        );
    }

    public function test_creating_a_product_is_recorded(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'صنف جديد',
            'sku' => 'NEW-1',
            'unit' => 'قطعة',
            'sale_price' => 50,
        ]);

        $activity = Activity::where('log_name', 'product')->where('event', 'created')->sole();

        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('NEW-1', $activity->attribute_changes['attributes']['sku']);
    }

    public function test_an_edit_records_before_and_after(): void
    {
        $this->actingAs($this->admin)->put("/products/{$this->product->id}", [
            'name' => 'خلاط كهربائي',
            'sku' => 'HA-9001',
            'unit' => 'قطعة',
            'sale_price' => 210,
            'purchase_price' => 100,
        ]);

        $activity = Activity::where('log_name', 'product')->where('event', 'updated')->sole();

        $this->assertSame('180.00', (string) $activity->attribute_changes['old']['sale_price']);
        $this->assertSame(210, (int) $activity->attribute_changes['attributes']['sale_price']);

        // والعرض بيطلّعها «قبل ← بعد» بأسماء عربية
        $presented = app(ActivityService::class)->present($activity);
        $prices = collect($presented['changes'])->firstWhere('field', 'سعر البيع');

        $this->assertNotNull($prices);
        $this->assertSame('210.00', $prices['to']);
    }

    public function test_a_delete_is_recorded(): void
    {
        $spare = Product::create([
            'sku' => 'DEL-1', 'name' => 'للحذف', 'unit' => 'قطعة',
            'purchase_price' => 1, 'sale_price' => 2, 'min_stock' => 0,
        ]);

        $this->actingAs($this->admin)->delete("/products/{$spare->id}");

        $this->assertSame(
            1,
            Activity::where('log_name', 'product')->where('event', 'deleted')->count(),
        );
    }

    public function test_an_invoice_records_who_created_it(): void
    {
        $this->seedStock();

        $this->actingAs($this->admin)->post('/sales', [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => now()->toDateString(),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 180],
            ],
        ])->assertRedirect();

        $invoice = Invoice::query()->sales()->sole();

        // العمود موجود أصلاً على الفاتورة
        $this->assertSame($this->admin->id, $invoice->user_id);

        $activity = Activity::where('log_name', 'invoice')->where('event', 'created')->sole();

        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame($invoice->number, $activity->attribute_changes['attributes']['number']);
    }

    public function test_sending_to_eta_is_recorded_as_its_own_event(): void
    {
        $this->seedStock();

        $this->actingAs($this->admin)->post('/sales', [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => now()->toDateString(),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 180],
            ],
        ]);

        $invoice = Invoice::query()->sales()->sole();

        $this->actingAs($this->admin)->post("/sales/{$invoice->id}/eta");

        $activity = Activity::where('event', 'eta_sent')->sole();

        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame($invoice->number, $activity->properties['number']);
    }

    public function test_a_payment_records_who_created_it(): void
    {
        $this->actingAs($this->admin)->post("/customers/{$this->customer->id}/payments", [
            'amount' => 500,
            'method' => 'cash',
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $activity = Activity::where('log_name', 'payment')->where('event', 'created')->sole();

        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame(500, (int) $activity->attribute_changes['attributes']['amount']);
    }

    public function test_only_admins_can_open_the_activity_page(): void
    {
        $this->actingAs($this->salesUser())->get('/activity')->assertForbidden();
        $this->actingAs($this->accountant())->get('/activity')->assertForbidden();
        $this->actingAs($this->admin)->get('/activity')->assertOk();
    }

    public function test_the_page_filters_by_user_action_and_entity(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'صنف للفلتر', 'sku' => 'F-1', 'unit' => 'قطعة', 'sale_price' => 10,
        ]);

        $this->actingAs($this->admin)
            ->get('/activity?log=product&event=created')
            ->assertInertia(fn ($page) => $page->has('rows.data', 1)->etc());

        $this->actingAs($this->admin)
            ->get('/activity?log=invoice')
            ->assertInertia(fn ($page) => $page->has('rows.data', 0)->etc());

        $this->actingAs($this->admin)
            ->get('/activity?user_id='.$this->accountant()->id)
            ->assertInertia(fn ($page) => $page->has('rows.data', 0)->etc());
    }

    public function test_the_page_filters_by_date_range(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'صنف', 'sku' => 'D-1', 'unit' => 'قطعة', 'sale_price' => 10,
        ]);

        Activity::query()->update(['created_at' => now()->subDays(10)]);

        $this->actingAs($this->admin)
            ->get('/activity?from='.now()->subDays(2)->toDateString())
            ->assertInertia(fn ($page) => $page->has('rows.data', 0)->etc());

        $this->actingAs($this->admin)
            ->get('/activity?from='.now()->subDays(20)->toDateString())
            ->assertInertia(fn ($page) => $page->has('rows.data', 1)->etc());
    }

    public function test_the_newest_row_comes_first(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'الأول', 'sku' => 'O-1', 'unit' => 'قطعة', 'sale_price' => 10,
        ]);

        $this->actingAs($this->admin)->post('/products', [
            'name' => 'التاني', 'sku' => 'O-2', 'unit' => 'قطعة', 'sale_price' => 10,
        ]);

        $this->actingAs($this->admin)
            ->get('/activity')
            ->assertInertia(fn ($page) => $page
                ->where('rows.data.0.subject', 'التاني')
                ->etc()
            );
    }

    /** غير المدير بيشوف نشاطه هو بس — حتى لو سأل المساعد */
    public function test_non_admins_only_see_their_own_activity(): void
    {
        $sales = $this->salesUser();

        $this->actingAs($this->admin)->post('/products', [
            'name' => 'صنف المدير', 'sku' => 'A-1', 'unit' => 'قطعة', 'sale_price' => 10,
        ]);

        $service = app(ActivityService::class);

        $this->assertSame(0, $service->query($sales)->count());
        $this->assertGreaterThan(0, $service->query($this->admin)->count());
    }

    public function test_pruning_removes_rows_older_than_the_retention(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'قديم', 'sku' => 'P-1', 'unit' => 'قطعة', 'sale_price' => 10,
        ]);

        Activity::query()->update(['created_at' => now()->subMonths(13)]);

        $this->actingAs($this->admin)->post('/products', [
            'name' => 'جديد', 'sku' => 'P-2', 'unit' => 'قطعة', 'sale_price' => 10,
        ]);

        $this->assertSame(2, Activity::count());

        $this->artisan('erp:prune-activity')->assertSuccessful();

        $this->assertSame(1, Activity::count());
        $this->assertSame('جديد', Activity::sole()->attribute_changes['attributes']['name']);
    }

    public function test_pruning_can_be_rehearsed_without_deleting(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'قديم', 'sku' => 'P-3', 'unit' => 'قطعة', 'sale_price' => 10,
        ]);

        Activity::query()->update(['created_at' => now()->subMonths(13)]);

        $this->artisan('erp:prune-activity --dry-run')->assertSuccessful();

        $this->assertSame(1, Activity::count());
    }

    private function seedStock(): void
    {
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 50,
            'unit_cost' => 100,
            'date' => now()->toDateString(),
        ]);
    }
}
