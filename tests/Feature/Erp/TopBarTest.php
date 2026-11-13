<?php

namespace Tests\Feature\Erp;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TopBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    public function test_the_top_bar_gets_the_date_and_low_stock_count(): void
    {
        $user = User::factory()->create();
        $this->seedStock(minStock: 50, onHand: 10);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('lowStockCount', 1)
                ->where('today', now()->toDateString())
            );
    }

    public function test_low_stock_counts_only_products_under_their_minimum(): void
    {
        $service = app(InventoryService::class);

        $this->seedStock(minStock: 50, onHand: 10);   // تحت الحد
        $this->seedStock(minStock: 10, onHand: 80);   // فوق الحد
        $this->seedStock(minStock: 20, onHand: 20);   // على الحد بالظبط — مش ناقص

        $this->assertSame(1, $service->lowStockCount());
        $this->assertSame(10.0, (float) $service->lowStockProducts()->first()->stock_balance);
    }

    public function test_inventory_value_is_priced_at_cost(): void
    {
        $this->seedStock(minStock: 0, onHand: 10, cost: 250);
        $this->seedStock(minStock: 0, onHand: 4, cost: 1000);

        $this->assertSame(6500.0, app(InventoryService::class)->inventoryValue());
    }

    /** صنف برصيد معيّن في مخزن واحد */
    private function seedStock(float $minStock, float $onHand, float $cost = 100): Product
    {
        $warehouse = Warehouse::firstOrCreate(['name' => 'مخزن الاختبار']);

        $product = Product::create([
            'sku' => 'T-'.fake()->unique()->numerify('####'),
            'name' => 'صنف اختبار',
            'purchase_price' => $cost,
            'sale_price' => $cost * 1.3,
            'min_stock' => $minStock,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => $onHand,
            'unit_cost' => $cost,
            'date' => now()->toDateString(),
        ]);

        return $product;
    }
}
