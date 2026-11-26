<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\PreparationOrder;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\StockDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * أذون الإضافة والخصم، والجرد.
 *
 * القاعدة اللي كل حاجة هنا بتدور حواليها: **الاعتماد هو اللي بيحرّك
 * البضاعة، مش الحفظ**. الورقة بتتكتب في المخزن وبتتراجع، والاعتماد
 * بيبقى عند المدير أو المحاسب.
 */
class StockDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Warehouse $warehouse;

    private Product $product;

    private Product $other;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $this->category = Category::create(['name' => 'بقالة']);

        $this->product = Product::create([
            'sku' => 'KB-25', 'name' => 'كبولي مجوز', 'category_id' => $this->category->id,
            'unit' => 'جوز', 'purchase_price' => 100, 'sale_price' => 150, 'min_stock' => 0,
        ]);

        $this->other = Product::create([
            'sku' => 'FR-01', 'name' => 'الفاروق فضي', 'unit' => 'قطعة',
            'purchase_price' => 50, 'sale_price' => 80, 'min_stock' => 0,
        ]);

        $this->receive($this->product, 1000);
        $this->receive($this->other, 200);
    }

    // ── إذن الإضافة والخصم ────────────────────────────────────────

    /** الحفظ مسودة — ولا حركة مخزون */
    public function test_a_saved_adjustment_does_not_move_stock_yet(): void
    {
        $before = StockMovement::count();

        $this->createAdjustment(StockAdjustment::TYPE_ADDITION, 50)->assertRedirect();

        $adjustment = StockAdjustment::sole();

        $this->assertSame(StockAdjustment::STATUS_DRAFT, $adjustment->status);
        $this->assertSame($before, StockMovement::count());
        $this->assertSame(1000.0, $this->stock($this->product));
    }

    public function test_approving_an_addition_raises_the_balance(): void
    {
        $this->createAdjustment(StockAdjustment::TYPE_ADDITION, 50)->assertRedirect();

        $adjustment = StockAdjustment::sole();

        $this->actingAs($this->admin)
            ->post("/stock-adjustments/{$adjustment->id}/approve")
            ->assertRedirect();

        $this->assertSame(StockAdjustment::STATUS_APPROVED, $adjustment->fresh()->status);
        $this->assertSame(1050.0, $this->stock($this->product));
    }

    public function test_approving_a_deduction_lowers_the_balance(): void
    {
        $this->createAdjustment(StockAdjustment::TYPE_DEDUCTION, 50)->assertRedirect();

        $this->approve(StockAdjustment::sole());

        $this->assertSame(950.0, $this->stock($this->product));
    }

    /** التكلفة بتتجمّد على الإذن، فالحركة بتقرا نفس الرقم */
    public function test_the_movement_carries_the_frozen_cost(): void
    {
        $this->actingAs($this->admin)->post('/stock-adjustments', [
            'type' => StockAdjustment::TYPE_ADDITION,
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->toDateString(),
            'reason' => 'opening',
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 10,
                'unit_cost' => 77.5,
            ]],
        ])->assertRedirect();

        $adjustment = StockAdjustment::sole();

        // التكلفة الحالية اتغيّرت بعد الكتابة
        $this->product->update(['purchase_price' => 999]);

        $this->approve($adjustment);

        $movement = StockMovement::query()
            ->where('reference_type', StockAdjustment::class)
            ->sole();

        $this->assertSame(77.5, (float) $movement->unit_cost);
        $this->assertSame(775.0, $adjustment->fresh()->load('items')->value());
    }

    /** من غير تكلفة، بتتاخد تكلفة الصنف الحالية */
    public function test_the_cost_defaults_to_the_products_cost(): void
    {
        $this->createAdjustment(StockAdjustment::TYPE_ADDITION, 10)->assertRedirect();

        $this->assertSame(
            100.0,
            (float) StockAdjustment::sole()->items()->value('unit_cost'),
        );
    }

    public function test_an_adjustment_cannot_be_approved_twice(): void
    {
        $this->createAdjustment(StockAdjustment::TYPE_ADDITION, 10)->assertRedirect();

        $adjustment = StockAdjustment::sole();

        $this->approve($adjustment);

        $this->actingAs($this->admin)
            ->post("/stock-adjustments/{$adjustment->id}/approve")
            ->assertSessionHasErrors('status');

        $this->assertSame(1, StockMovement::query()
            ->where('reference_type', StockAdjustment::class)
            ->count());
    }

    /**
     * الخصم بيتفحص على **المتاح** مش الرصيد الخام.
     *
     * بضاعة محجوزة بطلب تحضير لسه على الرف، بس مش ملكنا نخصمها — وإلا
     * الطلب بيروح يتجهّز ويلاقي الرف فاضي.
     */
    public function test_a_deduction_cannot_eat_reserved_stock(): void
    {
        $customer = Customer::create(['name' => 'تاجر', 'price_tier' => 'sale']);

        $order = PreparationOrder::create([
            'number' => 'PR-2026-00001',
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'date' => Carbon::today()->toDateString(),
            'status' => PreparationOrder::STATUS_OPEN,
        ]);

        $order->items()->create(['product_id' => $this->product->id, 'quantity' => 980]);

        // الرصيد 1000، المحجوز 980، فالمتاح 20 بس
        $this->createAdjustment(StockAdjustment::TYPE_DEDUCTION, 100)->assertRedirect();

        $this->actingAs($this->admin)
            ->post('/stock-adjustments/'.StockAdjustment::sole()->id.'/approve')
            ->assertSessionHasErrors();

        $this->assertSame(StockAdjustment::STATUS_DRAFT, StockAdjustment::sole()->status);
        $this->assertSame(1000.0, $this->stock($this->product));
    }

    // ── الصلاحيات ─────────────────────────────────────────────────

    /** المندوب بيكتب الورقة — بس مابيعتمدش */
    public function test_a_sales_user_can_write_but_not_approve(): void
    {
        $sales = $this->salesUser();

        $this->actingAs($sales)->post('/stock-adjustments', [
            'type' => StockAdjustment::TYPE_ADDITION,
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->toDateString(),
            'reason' => 'damaged',
            'items' => [['product_id' => $this->product->id, 'quantity' => 5]],
        ])->assertRedirect();

        $adjustment = StockAdjustment::sole();

        $this->actingAs($sales)
            ->post("/stock-adjustments/{$adjustment->id}/approve")
            ->assertForbidden();

        // ولا حركة — البضاعة مااتحركتش
        $this->assertSame(1000.0, $this->stock($this->product));
    }

    public function test_an_accountant_can_approve(): void
    {
        $this->createAdjustment(StockAdjustment::TYPE_ADDITION, 10)->assertRedirect();

        $this->actingAs($this->accountant())
            ->post('/stock-adjustments/'.StockAdjustment::sole()->id.'/approve')
            ->assertRedirect();

        $this->assertSame(1010.0, $this->stock($this->product));
    }

    // ── الجرد ─────────────────────────────────────────────────────

    public function test_a_count_sheet_freezes_the_system_quantities(): void
    {
        $this->openCount()->assertRedirect();

        $count = StockCount::sole();

        $this->assertSame(StockCount::STATUS_OPEN, $count->status);
        $this->assertSame(2, $count->items()->count());

        $item = $count->items()->where('product_id', $this->product->id)->sole();

        $this->assertSame(1000.0, (float) $item->system_quantity);
        $this->assertNull($item->counted_quantity);
    }

    /** الجرد على تصنيف واحد بياخد أصنافه بس */
    public function test_a_count_can_be_limited_to_one_category(): void
    {
        $this->actingAs($this->admin)->post('/stock-counts', [
            'warehouse_id' => $this->warehouse->id,
            'category_id' => $this->category->id,
            'date' => Carbon::today()->toDateString(),
        ])->assertRedirect();

        $count = StockCount::sole();

        $this->assertSame(1, $count->items()->count());
        $this->assertSame($this->product->id, $count->items()->value('product_id'));
    }

    /**
     * 1000 في النظام، 950 معدود → إذن خصم بـ 50.
     *
     * ده المثال اللي الميزة كلها اتعملت عشانه.
     */
    public function test_approving_a_count_creates_the_difference_documents(): void
    {
        $this->openCount()->assertRedirect();

        $count = StockCount::sole();
        $short = $count->items()->where('product_id', $this->product->id)->sole();
        $over = $count->items()->where('product_id', $this->other->id)->sole();

        $this->actingAs($this->admin)->put("/stock-counts/{$count->id}", [
            'items' => [
                ['id' => $short->id, 'counted_quantity' => 950],
                ['id' => $over->id, 'counted_quantity' => 210],
            ],
        ])->assertRedirect();

        $this->actingAs($this->admin)
            ->post("/stock-counts/{$count->id}/approve")
            ->assertRedirect();

        $deduction = StockAdjustment::query()
            ->where('type', StockAdjustment::TYPE_DEDUCTION)->sole();
        $addition = StockAdjustment::query()
            ->where('type', StockAdjustment::TYPE_ADDITION)->sole();

        $this->assertSame(50.0, (float) $deduction->items()->value('quantity'));
        $this->assertSame(10.0, (float) $addition->items()->value('quantity'));

        // الأذون بتتعمد مع الجرد، فالأرصدة بتطابق المعدود
        $this->assertSame(950.0, $this->stock($this->product));
        $this->assertSame(210.0, $this->stock($this->other));

        $this->assertSame(StockCount::STATUS_APPROVED, $count->fresh()->status);
        $this->assertSame('count', $deduction->reason);
    }

    /**
     * البند اللي مااتعدّش بيتسيب على حاله.
     *
     * ورقة نص مليانة معناها المخزن لسه بيعد — لو عدّيناه كصفر، أول
     * اعتماد كان هيمسح نص المخزن.
     */
    public function test_an_uncounted_line_is_left_alone(): void
    {
        $this->openCount()->assertRedirect();

        $count = StockCount::sole();
        $item = $count->items()->where('product_id', $this->product->id)->sole();

        $this->actingAs($this->admin)->put("/stock-counts/{$count->id}", [
            'items' => [['id' => $item->id, 'counted_quantity' => 900]],
        ])->assertRedirect();

        $this->actingAs($this->admin)->post("/stock-counts/{$count->id}/approve");

        // الصنف التاني مااتعدّش، فرصيده زي ما هو
        $this->assertSame(200.0, $this->stock($this->other));
        $this->assertSame(900.0, $this->stock($this->product));
    }

    public function test_a_count_with_no_differences_creates_no_documents(): void
    {
        $this->openCount()->assertRedirect();

        $count = StockCount::sole();

        $this->actingAs($this->admin)->put("/stock-counts/{$count->id}", [
            'items' => $count->items->map(fn ($item) => [
                'id' => $item->id,
                'counted_quantity' => (float) $item->system_quantity,
            ])->all(),
        ])->assertRedirect();

        $this->actingAs($this->admin)->post("/stock-counts/{$count->id}/approve");

        $this->assertSame(0, StockAdjustment::count());
        $this->assertSame(StockCount::STATUS_APPROVED, $count->fresh()->status);
    }

    public function test_an_approved_count_cannot_be_edited(): void
    {
        $this->openCount()->assertRedirect();

        $count = StockCount::sole();
        $item = $count->items()->first();

        $this->actingAs($this->admin)->post("/stock-counts/{$count->id}/approve");

        $this->actingAs($this->admin)
            ->put("/stock-counts/{$count->id}", [
                'items' => [['id' => $item->id, 'counted_quantity' => 5]],
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_a_sales_user_cannot_approve_a_count(): void
    {
        $this->openCount()->assertRedirect();

        $this->actingAs($this->salesUser())
            ->post('/stock-counts/'.StockCount::sole()->id.'/approve')
            ->assertForbidden();
    }

    public function test_a_count_can_be_cancelled(): void
    {
        $this->openCount()->assertRedirect();

        $this->actingAs($this->admin)
            ->post('/stock-counts/'.StockCount::sole()->id.'/cancel')
            ->assertRedirect();

        $this->assertSame(StockCount::STATUS_CANCELLED, StockCount::sole()->status);
    }

    /** الجرد بياخد الصنف اللي رصيده صفر — ده أهم اكتشاف فيه */
    public function test_a_count_includes_products_with_no_balance(): void
    {
        $fresh = Product::create([
            'sku' => 'NEW-1', 'name' => 'صنف من غير رصيد', 'unit' => 'قطعة',
            'purchase_price' => 10, 'sale_price' => 15, 'min_stock' => 0,
        ]);

        $this->openCount()->assertRedirect();

        $item = StockCount::sole()->items()->where('product_id', $fresh->id)->sole();

        $this->assertSame(0.0, (float) $item->system_quantity);
    }

    // ── الربط بباقي النظام ────────────────────────────────────────

    /** الإذن المعتمد بيبان في سجل حركة الصنف وبيفتح منه */
    public function test_an_approved_adjustment_shows_in_the_movement_history(): void
    {
        $this->createAdjustment(StockAdjustment::TYPE_ADDITION, 25)->assertRedirect();

        $adjustment = StockAdjustment::sole();
        $this->approve($adjustment);

        $history = app(InventoryService::class)->movementHistory($this->product);
        $row = collect($history['rows'])->firstWhere('reference', $adjustment->number);

        $this->assertNotNull($row);
        $this->assertSame("/stock-adjustments/{$adjustment->id}", $row['link']);
    }

    /** وبيدخل في تقييم المخزون زي أي حركة */
    public function test_an_approved_adjustment_changes_the_inventory_valuation(): void
    {
        $before = app(InventoryService::class)->inventoryValue();

        $this->createAdjustment(StockAdjustment::TYPE_ADDITION, 10)->assertRedirect();
        $this->approve(StockAdjustment::sole());

        $after = app(InventoryService::class)->inventoryValue();

        $this->assertSame(round($before + 1_000, 2), round($after, 2));
    }

    // ── الشاشات وسجل النشاط ───────────────────────────────────────

    public function test_the_stock_screens_render(): void
    {
        $this->createAdjustment(StockAdjustment::TYPE_ADDITION, 5)->assertRedirect();
        $this->openCount()->assertRedirect();

        $adjustment = StockAdjustment::sole();
        $count = StockCount::sole();

        $screens = [
            '/stock-adjustments' => 'stock/adjustments/Index',
            '/stock-adjustments/create?type=deduction' => 'stock/adjustments/Create',
            "/stock-adjustments/{$adjustment->id}" => 'stock/adjustments/Show',
            "/stock-adjustments/{$adjustment->id}/print" => 'print/StockAdjustment',
            '/stock-counts' => 'stock/counts/Index',
            '/stock-counts/create' => 'stock/counts/Create',
            "/stock-counts/{$count->id}" => 'stock/counts/Show',
            "/stock-counts/{$count->id}/print" => 'print/StockCount',
        ];

        foreach ($screens as $url => $component) {
            $this->actingAs($this->admin)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
        }
    }

    public function test_the_approval_is_written_to_the_activity_log(): void
    {
        $this->createAdjustment(StockAdjustment::TYPE_ADDITION, 10)->assertRedirect();

        $adjustment = StockAdjustment::sole();
        $this->approve($adjustment);

        $activity = Activity::query()
            ->where('log_name', 'stock_adjustment')
            ->where('event', 'approved')
            ->sole();

        $this->assertSame($adjustment->number, $activity->properties['number']);
        $this->assertSame(1_000.0, (float) $activity->properties['value']);
    }

    public function test_the_count_approval_is_logged_with_its_documents(): void
    {
        $this->openCount()->assertRedirect();

        $count = StockCount::sole();
        $item = $count->items()->where('product_id', $this->product->id)->sole();

        $this->actingAs($this->admin)->put("/stock-counts/{$count->id}", [
            'items' => [['id' => $item->id, 'counted_quantity' => 940]],
        ]);

        $this->actingAs($this->admin)->post("/stock-counts/{$count->id}/approve");

        $activity = Activity::query()
            ->where('log_name', 'stock_count')
            ->where('event', 'approved')
            ->sole();

        $this->assertSame(
            StockAdjustment::query()->where('type', StockAdjustment::TYPE_DEDUCTION)->value('number'),
            $activity->properties['deduction'],
        );
    }

    // ── مساعدات ───────────────────────────────────────────────────

    private function createAdjustment(string $type, float $quantity)
    {
        return $this->actingAs($this->admin)->post('/stock-adjustments', [
            'type' => $type,
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->toDateString(),
            'reason' => $type === StockAdjustment::TYPE_ADDITION ? 'opening' : 'damaged',
            'items' => [['product_id' => $this->product->id, 'quantity' => $quantity]],
        ]);
    }

    private function openCount()
    {
        return $this->actingAs($this->admin)->post('/stock-counts', [
            'warehouse_id' => $this->warehouse->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    private function approve(StockAdjustment $adjustment): void
    {
        app(StockDocumentService::class)->approveAdjustment($adjustment, $this->admin);
    }

    private function stock(Product $product): float
    {
        return app(InventoryService::class)->stockFor($product->id, $this->warehouse->id);
    }

    private function receive(Product $product, float $quantity): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => $quantity,
            'unit_cost' => $product->purchase_price,
            'date' => Carbon::today()->subDays(10)->toDateString(),
        ]);
    }
}
