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
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * الدورة الكاملة على نظام فاضي — عميل جديد بيبدأ من صفر.
 *
 * الاختبار ده بيمشي نفس خطوات المستخدم بالترتيب ومن خلال نفس المسارات
 * اللي الواجهة بتضرب عليها: مخزن ← مورد ← تصنيف ← أصناف ← مندوب ← تاجر
 * ← فاتورة شراء ← فاتورة بيع ← تحصيل. مفيش أي سيدر ولا إدخال مباشر في
 * قاعدة البيانات — كل سجل بيتعمل من الشاشة.
 */
class CleanSystemWalkthroughTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_client_can_run_the_whole_cycle_from_an_empty_system(): void
    {
        $user = $this->admin();

        // ———— البداية: كل حاجة فاضية ————
        $this->assertSame(0, Warehouse::count());
        $this->assertSame(0, Supplier::count());
        $this->assertSame(0, Product::count());
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $this->actingAs($user)->get('/dashboard')->assertOk();

        // ———— 1. المخزن ————
        $this->actingAs($user)->from('/warehouses')
            ->post('/warehouses', [
                'name' => 'المخزن الرئيسي',
                'location' => 'العبور',
                'is_active' => true,
            ])->assertSessionHasNoErrors();

        $warehouse = Warehouse::sole();

        // ———— 2. المورد ————
        $this->actingAs($user)->from('/suppliers')
            ->post('/suppliers', [
                'name' => 'شركة الأناضول للتجارة',
                'country' => 'تركيا',
                'currency' => 'USD',
                'phone' => '+905320000000',
                'is_active' => true,
            ])->assertSessionHasNoErrors();

        $supplier = Supplier::sole();

        // ———— 3. التصنيف ————
        $this->actingAs($user)->from('/categories')
            ->post('/categories', ['name' => 'زيوت ومنظفات', 'is_active' => true])
            ->assertSessionHasNoErrors();

        $category = Category::sole();

        // ———— 4. الأصناف ————
        $this->actingAs($user)->from('/products')
            ->post('/products', [
                'name' => 'زيت عباد شمس 1 لتر',
                'sku' => 'OIL-1L',
                'unit' => 'قطعة',
                'category_id' => $category->id,
                'sale_price' => 95,
                'wholesale_price' => 88,
                'min_stock' => 48,
                'is_active' => true,
            ])->assertSessionHasNoErrors();

        // التاني بيتضاف من جوه فورم الفاتورة — الإضافة السريعة
        $this->actingAs($user)->from('/purchases/create')
            ->post('/products/quick', [
                'name' => 'صابون سائل 500 مل',
                'sku' => 'SOAP-500',
                'unit' => 'قطعة',
                'category_id' => $category->id,
                'sale_price' => 42,
            ])->assertSessionHasNoErrors();

        $oil = Product::where('sku', 'OIL-1L')->sole();
        $soap = Product::where('sku', 'SOAP-500')->sole();

        $this->assertSame(2, Product::count());

        // ———— 5. المندوب ————
        $this->actingAs($user)->from('/reps')
            ->post('/reps', [
                'name' => 'كريم سعيد',
                'region' => 'الدلتا',
                'phone' => '01112345678',
                'commission_rate' => 2.5,
                'is_active' => true,
            ])->assertSessionHasNoErrors();

        $rep = SalesRep::sole();

        // ———— 6. التاجر ————
        $this->actingAs($user)->from('/customers')
            ->post('/customers', [
                'name' => 'محمد عبد الله للتجارة',
                'city' => 'الدقهلية',
                'phone' => '01012345678',
                'price_tier' => 'wholesale',
                'sales_rep_id' => $rep->id,
                'credit_limit' => 50000,
                'is_active' => true,
            ])->assertSessionHasNoErrors();

        $customer = Customer::sole();

        $this->assertSame($rep->id, $customer->sales_rep_id);

        // ———— 7. فاتورة الشراء ————
        // الفورم دلوقتي عنده كل اللي محتاجه
        $this->actingAs($user)->get('/purchases/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('warehouses', 1)
                ->has('suppliers', 1)
                ->has('products', 2)
            );

        $this->actingAs($user)
            ->post('/purchases', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'date' => now()->toDateString(),
                'currency' => 'USD',
                'exchange_rate' => 49.4,
                'shipping_cost' => 8000,
                'customs_cost' => 12000,
                'clearance_cost' => 2000,
                'discount' => 0,
                'items' => [
                    ['product_id' => $oil->id, 'quantity' => 500, 'unit_price' => 1.2],
                    ['product_id' => $soap->id, 'quantity' => 300, 'unit_price' => 0.6],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $purchase = Invoice::where('type', Invoice::TYPE_PURCHASE)->sole();

        // البضاعة دخلت المخزن فعلاً
        $this->assertSame(500.0, $oil->stock($warehouse->id));
        $this->assertSame(300.0, $soap->stock($warehouse->id));

        // وفاتورة الشراء حدّثت تكلفة الصنف بالتكلفة الواصلة (مش سعر المورد بس)
        $oil->refresh();
        $this->assertGreaterThan(1.2 * 49.4, (float) $oil->purchase_price);

        // والمستحق للمورد اتقيّد بعملته
        $this->assertSame(780.0, round($supplier->fresh()->balance(), 2));

        // ———— 8. فاتورة البيع ————
        $this->actingAs($user)->get('/sales/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('customers', 1)
                ->has('warehouses', 1)
            );

        $this->actingAs($user)
            ->post('/sales', [
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'date' => now()->toDateString(),
                'discount' => 0,
                'items' => [
                    // التاجر جملة، فالسعر بتاعه هو سعر الجملة
                    ['product_id' => $oil->id, 'quantity' => 100, 'unit_price' => $oil->priceFor($customer)],
                    ['product_id' => $soap->id, 'quantity' => 50, 'unit_price' => $soap->priceFor($customer)],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $sale = Invoice::where('type', Invoice::TYPE_SALE)->sole();

        // الكميات اتخصمت من المخزن
        $this->assertSame(400.0, $oil->stock($warehouse->id));
        $this->assertSame(250.0, $soap->stock($warehouse->id));

        // والتاجر عليه مديونية بقيمة الفاتورة
        $this->assertSame(round((float) $sale->total, 2), round($customer->fresh()->balance(), 2));

        // ———— 9. التحصيل ————
        $this->actingAs($user)->from("/customers/{$customer->id}")
            ->post("/customers/{$customer->id}/payments", [
                'amount' => 5000,
                'method' => 'cash',
                'date' => now()->toDateString(),
                'invoice_id' => $sale->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Payment::count());

        $this->assertSame(
            round((float) $sale->total - 5000, 2),
            round($customer->fresh()->balance(), 2),
        );

        // ———— النهاية: كل شاشة بتعرض بياناتها ————
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/inventory')->assertOk();
        $this->actingAs($user)->get('/treasury')->assertOk();
        $this->actingAs($user)->get('/reports')->assertOk();

        $this->actingAs($user)->get('/reps')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('reps.0.customers', 1)
                // العمولة بتتحسب على المحصّل: 5000 × 2.5%
                ->where('reps.0.commission', 125)
                ->where('reps.0.collected', 5000)
            );

        // كل حاجة اتسجّلت من الشاشة، مفيش سيدر ولا إدخال يدوي
        $this->assertSame(1, Warehouse::count());
        $this->assertSame(1, Supplier::count());
        $this->assertSame(2, Product::count());
        $this->assertSame(1, Customer::count());
        $this->assertSame(2, Invoice::count());
        $this->assertSame(4, StockMovement::count());
        $this->assertSame(1, User::count());
        $this->assertNotNull($purchase->number);
        $this->assertNotNull($sale->number);
    }
}
