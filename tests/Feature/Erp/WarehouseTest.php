<?php

namespace Tests\Feature\Erp;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // إدارة المخازن محتاجة صلاحية البيانات الأساسية — الصلاحيات نفسها
        // متغطّية في MasterDataPermissionsTest
        $this->user = $this->admin();
    }

    /** ده المسار اللي بيخلي العميل يبدأ من نظام فاضي */
    public function test_the_first_warehouse_can_be_created_from_the_ui(): void
    {
        $this->assertSame(0, Warehouse::count());

        $this->actingAs($this->user)
            ->get('/warehouses')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('warehouses/Index')
                ->has('warehouses', 0)
            );

        $this->actingAs($this->user)
            ->from('/warehouses')
            ->post('/warehouses', [
                'name' => 'المخزن الرئيسي',
                'location' => 'العبور',
                'is_active' => true,
            ])
            ->assertRedirect('/warehouses');

        $warehouse = Warehouse::sole();

        $this->assertSame('المخزن الرئيسي', $warehouse->name);
        $this->assertSame('العبور', $warehouse->location);
        $this->assertTrue($warehouse->is_active);
    }

    public function test_the_name_is_required_and_unique_with_arabic_messages(): void
    {
        Warehouse::create(['name' => 'مخزن الإسكندرية']);

        $this->actingAs($this->user)->from('/warehouses')
            ->post('/warehouses', ['name' => ''])
            ->assertSessionHasErrors(['name' => 'اكتب اسم المخزن.']);

        $this->actingAs($this->user)->from('/warehouses')
            ->post('/warehouses', ['name' => 'مخزن الإسكندرية'])
            ->assertSessionHasErrors(['name' => 'في مخزن بالاسم ده بالفعل.']);

        $this->assertSame(1, Warehouse::count());
    }

    public function test_a_warehouse_can_be_renamed_and_switched_off(): void
    {
        $warehouse = Warehouse::create(['name' => 'مخزن قديم']);

        $this->actingAs($this->user)->from('/warehouses')
            ->put("/warehouses/{$warehouse->id}", [
                'name' => 'مخزن العبور',
                'location' => 'المنطقة الصناعية',
                'is_active' => false,
            ])
            ->assertRedirect('/warehouses');

        $warehouse->refresh();

        $this->assertSame('مخزن العبور', $warehouse->name);
        $this->assertFalse($warehouse->is_active);
    }

    /** الاسم نفسه مسموح وانت بتعدّل نفس المخزن */
    public function test_updating_a_warehouse_without_renaming_it_is_allowed(): void
    {
        $warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);

        $this->actingAs($this->user)->from('/warehouses')
            ->put("/warehouses/{$warehouse->id}", [
                'name' => 'المخزن الرئيسي',
                'location' => 'العبور',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_an_empty_warehouse_can_be_deleted(): void
    {
        $warehouse = Warehouse::create(['name' => 'مخزن فاضي']);

        $this->actingAs($this->user)->from('/warehouses')
            ->delete("/warehouses/{$warehouse->id}")
            ->assertRedirect('/warehouses');

        $this->assertSame(0, Warehouse::count());
    }

    /** مخزن عليه حركة ما يتحذفش — الأرصدة التاريخية هتبوظ */
    public function test_a_warehouse_with_movements_cannot_be_deleted(): void
    {
        $warehouse = Warehouse::create(['name' => 'مخزن شغّال']);

        $product = Product::create([
            'sku' => 'P-1',
            'name' => 'صنف',
            'unit' => 'قطعة',
            'purchase_price' => 10,
            'sale_price' => 20,
            'min_stock' => 0,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'unit_cost' => 10,
            'type' => 'purchase',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)->from('/warehouses')
            ->delete("/warehouses/{$warehouse->id}")
            ->assertSessionHasErrors('warehouse');

        $this->assertSame(1, Warehouse::count());
    }

    public function test_the_list_shows_the_stock_value_per_warehouse(): void
    {
        $warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);

        $product = Product::create([
            'sku' => 'P-1',
            'name' => 'صنف',
            'unit' => 'قطعة',
            'purchase_price' => 100,
            'sale_price' => 150,
            'min_stock' => 0,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 7,
            'unit_cost' => 100,
            'type' => 'purchase',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->get('/warehouses')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('warehouses.0.items', 1)
                ->where('warehouses.0.units', 7)
                ->where('warehouses.0.value', 700)
            );
    }

    public function test_guests_cannot_manage_warehouses(): void
    {
        $warehouse = Warehouse::create(['name' => 'مخزن']);

        $this->get('/warehouses')->assertRedirect('/login');
        $this->post('/warehouses', ['name' => 'جديد'])->assertRedirect('/login');
        $this->delete("/warehouses/{$warehouse->id}")->assertRedirect('/login');
    }

    /** فورم الفاتورة بيقول الناقص بدل ما يعرض قوايم فاضية */
    public function test_the_invoice_forms_explain_what_is_missing_on_an_empty_system(): void
    {
        $this->actingAs($this->user)->get('/purchases/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('warehouses', 0)
                ->has('suppliers', 0)
                ->has('products', 0)
            );

        $this->actingAs($this->user)->get('/sales/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('warehouses', 0)
                ->has('customers', 0)
                ->has('products', 0)
            );
    }
}
