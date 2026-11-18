<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesRep;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * صلاحيات البيانات الأساسية حسب الدور.
 *
 * المدير والمحاسب بيديروا البيانات الأساسية، والمبيعات بيشوفوا بس —
 * ما عدا إضافة صنف سريعة من جوه الفاتورة. المستخدمين والأدوار للمدير لوحده.
 */
class MasterDataPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** الشاشات اللي بتتدار بصلاحية البيانات الأساسية */
    private const MASTER_DATA = [
        '/warehouses' => ['name' => 'مخزن'],
        '/categories' => ['name' => 'تصنيف'],
        '/products' => ['name' => 'صنف', 'sku' => 'P-1', 'unit' => 'قطعة', 'sale_price' => 10],
        '/suppliers' => ['name' => 'مورد', 'currency' => 'USD'],
        '/customers' => ['name' => 'تاجر', 'price_tier' => 'sale'],
        '/reps' => ['name' => 'مندوب', 'commission_rate' => 2],
    ];

    public function test_the_accountant_can_manage_every_master_data_screen(): void
    {
        $accountant = $this->accountant();

        foreach (self::MASTER_DATA as $url => $payload) {
            $this->actingAs($accountant)
                ->from($url)
                ->post($url, $payload)
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(1, Warehouse::count());
        $this->assertSame(1, Category::count());
        $this->assertSame(1, Product::count());
        $this->assertSame(1, Supplier::count());
        $this->assertSame(1, Customer::count());
        $this->assertSame(1, SalesRep::count());
    }

    public function test_the_sales_role_can_read_master_data_but_not_change_it(): void
    {
        $sales = $this->salesUser();

        foreach (self::MASTER_DATA as $url => $payload) {
            // القراءة مسموحة
            $this->actingAs($sales)->get($url)->assertOk();

            // الكتابة لأ
            $this->actingAs($sales)->from($url)->post($url, $payload)->assertForbidden();
        }

        $this->assertSame(0, Warehouse::count());
        $this->assertSame(0, Product::count());
        $this->assertSame(0, Customer::count());
    }

    public function test_the_sales_role_cannot_edit_or_delete_master_data(): void
    {
        $sales = $this->salesUser();
        $warehouse = Warehouse::create(['name' => 'مخزن']);

        $this->actingAs($sales)->from('/warehouses')
            ->put("/warehouses/{$warehouse->id}", ['name' => 'اسم تاني'])
            ->assertForbidden();

        $this->actingAs($sales)->from('/warehouses')
            ->delete("/warehouses/{$warehouse->id}")
            ->assertForbidden();

        $this->assertSame('مخزن', $warehouse->fresh()->name);
    }

    /** المندوب بيضيف صنف وهو بيكتب الفاتورة — ده الاستثناء الوحيد */
    public function test_the_sales_role_can_still_quick_add_a_product_from_an_invoice(): void
    {
        $sales = $this->salesUser();

        $this->actingAs($sales)
            ->from('/purchases/create')
            ->post('/products/quick', [
                'name' => 'صنف جديد',
                'sku' => 'P-9',
                'unit' => 'قطعة',
                'sale_price' => 50,
            ])
            ->assertRedirect('/purchases/create')
            ->assertSessionHasNoErrors();

        $this->assertSame('صنف جديد', Product::sole()->name);
    }

    public function test_only_the_admin_reaches_the_users_screen(): void
    {
        $this->actingAs($this->admin())->get('/users')->assertOk();
        $this->actingAs($this->accountant())->get('/users')->assertForbidden();
        $this->actingAs($this->salesUser())->get('/users')->assertForbidden();
    }

    public function test_only_the_admin_can_create_users(): void
    {
        $payload = [
            'name' => 'مستخدم جديد',
            'email' => 'new@erp.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'sales',
        ];

        $this->actingAs($this->accountant())->from('/users')
            ->post('/users', $payload)->assertForbidden();

        $this->actingAs($this->salesUser())->from('/users')
            ->post('/users', $payload)->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'new@erp.test']);

        $this->actingAs($this->admin())->from('/users')
            ->post('/users', $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'new@erp.test']);
    }

    /** مستخدم من غير دور مالوش أي صلاحية — بنفشل مقفولين مش مفتوحين */
    public function test_a_user_without_a_role_can_change_nothing(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->from('/warehouses')
            ->post('/warehouses', ['name' => 'مخزن'])
            ->assertForbidden();

        $this->actingAs($stranger)->get('/users')->assertForbidden();

        $this->assertSame(0, Warehouse::count());
    }

    /** الواجهة بتعرف تخفي الأزرار لأنها بتستقبل الصلاحيات مع كل صفحة */
    public function test_the_permissions_are_shared_with_every_page(): void
    {
        $this->actingAs($this->admin())->get('/warehouses')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.role', 'admin')
                ->where('auth.can.manageMasterData', true)
                ->where('auth.can.manageUsers', true)
                ->where('auth.can.quickAddProduct', true)
            );

        $this->actingAs($this->accountant())->get('/warehouses')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.role', 'accountant')
                ->where('auth.can.manageMasterData', true)
                ->where('auth.can.manageUsers', false)
            );

        $this->actingAs($this->salesUser())->get('/warehouses')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.role', 'sales')
                ->where('auth.can.manageMasterData', false)
                ->where('auth.can.quickAddProduct', true)
                ->where('auth.can.manageUsers', false)
            );
    }
}
