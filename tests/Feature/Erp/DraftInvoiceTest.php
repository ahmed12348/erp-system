<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CustomerService;
use App\Services\InvoiceDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * دورة حياة الفاتورة الممسوحة: مسودة → مراجعة → تأكيد.
 *
 * أهم حاجة هنا إن المسودة **ما بتلمسش** حاجة: لا مخزون ولا رصيد تاجر
 * ولا خزينة ولا عمولة. كل ده بيحصل عند التأكيد بس، وبالمسار العادي
 * بتاع `InvoiceService` — مش بمسار تاني موازي.
 */
class DraftInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Product $product;

    private Warehouse $warehouse;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $category = Category::create(['name' => 'أجهزة منزلية']);

        $this->customer = Customer::create([
            'name' => 'محمد عبد الله',
            'price_tier' => 'wholesale',
        ]);

        $this->product = Product::create([
            'sku' => 'HA-9001',
            'name' => 'كبولي مجوز',
            'category_id' => $category->id,
            'unit' => 'كرتونة',
            'purchase_price' => 100,
            'sale_price' => 180,
            'wholesale_price' => 160,
            'min_stock' => 5,
        ]);

        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 100,
            'unit_cost' => 100,
            'date' => now()->toDateString(),
        ]);
    }

    public function test_the_draft_page_shows_the_scan_and_the_image(): void
    {
        $draft = $this->draft();

        $this->actingAs($this->user)
            ->get(route('sales.drafts.show', $draft))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('drafts/Sale')
                ->where('draft.id', $draft->id)
                ->where('draft.review.items.0.extracted.name', 'كبولى محوز')
                ->has('draft.image')
                ->etc()
            );
    }

    public function test_a_confirmed_invoice_is_not_reachable_as_a_draft(): void
    {
        $draft = $this->draft();
        $draft->update(['status' => Invoice::STATUS_CONFIRMED]);

        $this->actingAs($this->user)
            ->get(route('sales.drafts.show', $draft))
            ->assertNotFound();
    }

    public function test_saving_as_draft_keeps_everything_untouched(): void
    {
        $draft = $this->draft();

        $this->actingAs($this->user)
            ->put(route('sales.drafts.update', $draft), $this->reviewed())
            ->assertRedirect();

        $draft->refresh();

        $this->assertSame(Invoice::STATUS_DRAFT, $draft->status);
        $this->assertSame($this->customer->id, $draft->customer_id);

        // ولا بند ولا حركة مخزون — المخزون لسه 100 زي ما بدأ
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertSame(1, StockMovement::count());
        $this->assertSame(0.0, app(CustomerService::class)->balanceFor($this->customer));
    }

    public function test_confirming_runs_the_normal_invoice_path(): void
    {
        $draft = $this->draft();

        $this->actingAs($this->user)
            ->post(route('sales.drafts.confirm', $draft), $this->reviewed())
            ->assertRedirect();

        // المسودة اتشالت، والفاتورة الحقيقية اتعملت
        $this->assertDatabaseMissing('invoices', ['id' => $draft->id]);

        $invoice = Invoice::query()->sales()->confirmed()->sole();

        $this->assertSame($this->customer->id, $invoice->customer_id);
        $this->assertCount(1, $invoice->items);
        $this->assertSame(3.0, (float) $invoice->items->first()->quantity);

        // المخزون اتخصم والرصيد اتحرك — زي أي فاتورة اتكتبت بالإيد
        $this->assertSame(2, StockMovement::count());
        $this->assertGreaterThan(0, app(CustomerService::class)->balanceFor($this->customer));
    }

    public function test_the_scanned_image_moves_to_the_confirmed_invoice(): void
    {
        $draft = $this->draft();

        $this->actingAs($this->user)
            ->post(route('sales.drafts.confirm', $draft), $this->reviewed())
            ->assertRedirect();

        $invoice = Invoice::query()->sales()->confirmed()->sole();

        $this->assertCount(1, $invoice->attachments);
        $this->assertSame('صورة الفاتورة الممسوحة', $invoice->attachments->first()->notes);
    }

    /**
     * أهم جزء في التحسين: التصحيح اليدوي بيتحفظ، فالمسح الجاي يعرفه.
     */
    public function test_confirming_learns_the_handwritten_names(): void
    {
        $draft = $this->draft();

        $this->actingAs($this->user)
            ->post(route('sales.drafts.confirm', $draft), $this->reviewed())
            ->assertRedirect();

        // الاسم اللي كان على الورق اتربط بالصنف اللي المستخدم اختاره
        $this->assertDatabaseHas('product_aliases', [
            'product_id' => $this->product->id,
            'alias' => 'كبولى محوز',
        ]);

        $this->assertDatabaseHas('customer_aliases', [
            'customer_id' => $this->customer->id,
            'alias' => 'محمد عبدالله الثلاتيني',
        ]);
    }

    public function test_saving_as_draft_also_learns_the_names(): void
    {
        $draft = $this->draft();

        $this->actingAs($this->user)
            ->put(route('sales.drafts.update', $draft), $this->reviewed())
            ->assertRedirect();

        $this->assertDatabaseHas('product_aliases', ['alias' => 'كبولى محوز']);
    }

    public function test_a_learned_alias_is_not_stored_when_it_equals_the_real_name(): void
    {
        $draft = $this->draft(scannedName: 'كبولي مجوز');

        $this->actingAs($this->user)
            ->put(route('sales.drafts.update', $draft), $this->reviewed())
            ->assertRedirect();

        // الاسم زي الأصل بالظبط — مفيش فايدة من حفظه كاسم بديل
        $this->assertDatabaseMissing('product_aliases', ['alias' => 'كبولي مجوز']);
    }

    public function test_a_draft_can_be_discarded(): void
    {
        $draft = $this->draft();

        $this->actingAs($this->user)
            ->delete(route('drafts.destroy', $draft))
            ->assertRedirect(route('sales.index'));

        $this->assertDatabaseMissing('invoices', ['id' => $draft->id]);
        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_confirming_rejects_a_line_with_no_product(): void
    {
        $draft = $this->draft();

        $payload = $this->reviewed();
        $payload['items'][0]['product_id'] = '';

        $this->actingAs($this->user)
            ->put(route('sales.drafts.update', $draft), $payload)
            ->assertSessionHasErrors('items.0.product_id');
    }

    public function test_drafts_can_be_filtered_in_the_list(): void
    {
        $this->draft();

        $this->actingAs($this->user)
            ->get('/sales?status=draft')
            ->assertInertia(fn ($page) => $page->has('invoices.data', 1)->etc());

        $this->actingAs($this->user)
            ->get('/sales?status=confirmed')
            ->assertInertia(fn ($page) => $page->has('invoices.data', 0)->etc());
    }

    /**
     * مسودة زي ما المسح بيعملها: الصنف لسه ما اتطابقش، والاسم على
     * الورق مكتوب غلط شوية.
     */
    private function draft(string $scannedName = 'كبولى محوز'): Invoice
    {
        $scan = [
            'type' => Invoice::TYPE_SALE,
            'form' => [
                'customer_id' => null,
                'date' => now()->toDateString(),
                'discount' => 0,
                'notes' => null,
                'items' => [
                    ['product_id' => null, 'quantity' => 3, 'unit_price' => 160, 'discount' => 0],
                ],
            ],
            'review' => [
                'party' => [
                    'confidence' => 'none',
                    'extracted' => 'محمد عبدالله الثلاتيني',
                    'matched' => null,
                    'via' => null,
                ],
                'fields' => [],
                'items' => [[
                    'confidence' => 'none',
                    'extracted' => ['code' => null, 'name' => $scannedName],
                    'matched' => null,
                    'matchedSku' => null,
                    'unit' => null,
                    'via' => null,
                    'priceFromSystem' => false,
                    'missingPrice' => false,
                ]],
            ],
        ];

        $draft = app(InvoiceDraftService::class)->createFromScan($scan, $this->user);

        $draft->attachments()->create([
            'disk' => 'local',
            'path' => 'attachments/invoice/'.$draft->id.'/scan.jpg',
            'original_name' => 'scan.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'user_id' => $this->user->id,
            'notes' => 'صورة الفاتورة الممسوحة',
        ]);

        return $draft;
    }

    /**
     * اللي المستخدم بيبعته بعد ما يراجع ويصحّح المطابقات.
     *
     * @return array<string, mixed>
     */
    private function reviewed(): array
    {
        return [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => now()->toDateString(),
            'discount' => 0,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'unit_price' => 160,
                    'discount' => 0,
                ],
            ],
        ];
    }
}
