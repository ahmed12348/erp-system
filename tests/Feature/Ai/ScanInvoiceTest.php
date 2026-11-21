<?php

namespace Tests\Feature\Ai;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoiceScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Exceptions\PrismStructuredDecodingException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Testing\PrismFake;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\Support\FailingPrism;
use Tests\TestCase;

/**
 * «مسح فاتورة» — من رفع الصورة لحد ما الملف يتعلّق على الفاتورة.
 *
 * قراءة الصورة متزيّفة بالكامل: `Prism::fake()` بيرجّع الـ JSON اللي
 * كنا متوقعينه من الموديل، فاللي بيتختبر هنا هو المطابقة وبناء الفورم
 * والتحقق — مش الموديل نفسه.
 */
class ScanInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;

    private Customer $customer;

    private Product $product;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('prism.providers.gemini.api_key', 'test-key');
        config()->set('ai.enabled', true);
        config()->set('ai.provider', 'gemini');

        RateLimiter::clear('ai-scan');

        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $category = Category::create(['name' => 'أجهزة منزلية']);

        $this->supplier = Supplier::create([
            'name' => 'شركة النور للتجارة',
            'phone' => '01012345678',
            'country' => 'تركيا',
            'currency' => 'TRY',
        ]);

        $this->customer = Customer::create([
            'name' => 'محمد عبد الله',
            'price_tier' => 'wholesale',
        ]);

        $this->product = Product::create([
            'sku' => 'HA-9001',
            'name' => 'خلاط كهربائي',
            'category_id' => $category->id,
            'unit' => 'قطعة',
            'purchase_price' => 100,
            'sale_price' => 180,
            'wholesale_price' => 160,
            'min_stock' => 5,
        ]);
    }

    public function test_guests_cannot_scan(): void
    {
        $this->postJson('/ai/scan-invoice', [
            'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
            'type' => 'purchase',
        ])->assertUnauthorized();
    }

    public function test_it_fills_a_purchase_invoice_from_the_image(): void
    {
        $this->fakeExtraction([
            'party_name' => 'شركة النور للتجارة',
            'date' => '2026-11-10',
            'currency' => 'TRY',
            'exchange_rate' => 1.46,
            'discount' => 50,
            'shipping_cost' => 1200,
            'customs_cost' => 800,
            'clearance_cost' => 300,
            'number' => 'INV-553',
            'items' => [
                ['code' => 'HA-9001', 'name' => 'خلاط كهربائي', 'quantity' => 20, 'unit_price' => 95.5],
            ],
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'purchase',
            ])
            ->assertOk();

        $response->assertJsonPath('form.supplier_id', $this->supplier->id)
            ->assertJsonPath('form.date', '2026-11-10')
            ->assertJsonPath('form.currency', 'TRY')
            ->assertJsonPath('form.exchange_rate', 1.46)
            ->assertJsonPath('form.discount', 50)
            ->assertJsonPath('form.shipping_cost', 1200)
            ->assertJsonPath('form.customs_cost', 800)
            ->assertJsonPath('form.clearance_cost', 300)
            ->assertJsonPath('form.items.0.product_id', $this->product->id)
            ->assertJsonPath('form.items.0.quantity', 20)
            ->assertJsonPath('form.items.0.unit_price', 95.5)
            // كل حاجة اتطابقت بالظبط — مفيش حاجة صفرا
            ->assertJsonPath('review.party.confidence', InvoiceScanService::MATCH_EXACT)
            ->assertJsonPath('review.items.0.confidence', InvoiceScanService::MATCH_EXACT)
            ->assertJsonPath('review.fields', []);

        // رقم الفاتورة الأصلي بيتحفظ في الملاحظات
        $this->assertStringContainsString('INV-553', $response->json('form.notes'));
    }

    public function test_it_fills_a_sale_invoice_without_the_purchase_only_fields(): void
    {
        $this->fakeExtraction([
            'party_name' => 'محمد عبد الله',
            'date' => '2026-11-10',
            'items' => [
                ['code' => null, 'name' => 'خلاط كهربائي', 'quantity' => 3, 'unit_price' => 175],
            ],
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'sale',
            ])
            ->assertOk()
            ->assertJsonPath('form.customer_id', $this->customer->id)
            ->assertJsonPath('form.items.0.unit_price', 175)
            // فاتورة البيع مالهاش عملة ولا سعر صرف ولا مصاريف استيراد
            ->assertJsonMissingPath('form.currency')
            ->assertJsonMissingPath('form.exchange_rate')
            ->assertJsonMissingPath('form.shipping_cost');
    }

    public function test_a_sale_line_without_a_price_falls_back_to_the_traders_tier(): void
    {
        $this->fakeExtraction([
            'party_name' => 'محمد عبد الله',
            'items' => [
                ['code' => 'HA-9001', 'name' => 'خلاط كهربائي', 'quantity' => 2, 'unit_price' => null],
            ],
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'sale',
            ])
            ->assertOk()
            // التاجر شريحته جملة الجملة، فالسعر 160 مش 180
            ->assertJsonPath('form.items.0.unit_price', 160)
            // وبيتعلّم عشان الواجهة تلوّنه أصفر — السعر ده من النظام مش من الورق
            ->assertJsonPath('review.items.0.priceFromSystem', true);
    }

    public function test_a_retail_trader_falls_back_to_the_retail_price(): void
    {
        $retail = Customer::create(['name' => 'سمير التجزئة', 'price_tier' => 'sale']);

        $this->fakeExtraction([
            'party_name' => 'سمير التجزئة',
            'items' => [
                ['code' => 'HA-9001', 'name' => 'خلاط', 'quantity' => 1, 'unit_price' => null],
            ],
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'sale',
            ])
            ->assertOk()
            ->assertJsonPath('form.customer_id', $retail->id)
            ->assertJsonPath('form.items.0.unit_price', 180);
    }

    public function test_it_matches_by_code_before_name(): void
    {
        // الكود بتاع الخلاط بس الاسم بتاع صنف تاني خالص
        $this->fakeExtraction([
            'party_name' => 'شركة النور للتجارة',
            'items' => [
                ['code' => 'HA-9001', 'name' => 'حاجة مكتوبة غلط', 'quantity' => 1, 'unit_price' => 10],
            ],
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'purchase',
            ])
            ->assertOk()
            ->assertJsonPath('form.items.0.product_id', $this->product->id)
            ->assertJsonPath('review.items.0.confidence', InvoiceScanService::MATCH_EXACT);
    }

    public function test_it_matches_arabic_names_written_differently(): void
    {
        // «احمد» من غير همزة، و«شركه» بهاء، وتطويل — نفس الاسم عند اللي بيقرا
        $this->fakeExtraction([
            'party_name' => 'شركه النور للتجـارة',
            'items' => [
                ['code' => null, 'name' => 'خلاط كهربائى', 'quantity' => 1, 'unit_price' => 90],
            ],
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'purchase',
            ])
            ->assertOk()
            ->assertJsonPath('form.supplier_id', $this->supplier->id)
            ->assertJsonPath('form.items.0.product_id', $this->product->id)
            // اتطابق بس بعد تطبيع — محتاج نظرة من المستخدم
            ->assertJsonPath('review.party.confidence', InvoiceScanService::MATCH_LIKELY)
            ->assertJsonPath('review.items.0.confidence', InvoiceScanService::MATCH_LIKELY);
    }

    public function test_it_matches_the_party_by_phone_when_the_name_differs(): void
    {
        $this->fakeExtraction([
            'party_name' => 'النور تريدينج',
            'party_phone' => '٠١٠-١٢٣٤ ٥٦٧٨',
            'items' => [],
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'purchase',
            ])
            ->assertOk()
            ->assertJsonPath('form.supplier_id', $this->supplier->id)
            ->assertJsonPath('review.party.confidence', InvoiceScanService::MATCH_LIKELY);
    }

    public function test_unmatched_rows_come_back_flagged_instead_of_guessed(): void
    {
        $this->fakeExtraction([
            'party_name' => 'مورد مش موجود في النظام',
            'date' => null,
            'currency' => null,
            'items' => [
                ['code' => 'ZZ-0000', 'name' => 'صنف مش موجود', 'quantity' => 4, 'unit_price' => 12],
            ],
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'purchase',
            ])
            ->assertOk();

        // مفيش تخمين — الحقول فاضية والمستخدم هو اللي يختار
        $response->assertJsonPath('form.supplier_id', null)
            ->assertJsonPath('form.items.0.product_id', null)
            ->assertJsonPath('review.party.confidence', InvoiceScanService::MATCH_NONE)
            ->assertJsonPath('review.party.extracted', 'مورد مش موجود في النظام')
            ->assertJsonPath('review.items.0.confidence', InvoiceScanService::MATCH_NONE)
            ->assertJsonPath('review.items.0.extracted.name', 'صنف مش موجود');

        // التاريخ والعملة مالقاهمش، فبيتعلّموا للمراجعة
        $this->assertContains('date', $response->json('review.fields'));
        $this->assertContains('currency', $response->json('review.fields'));
    }

    public function test_a_future_date_is_dropped_because_the_form_rejects_it(): void
    {
        $this->fakeExtraction([
            'party_name' => 'شركة النور للتجارة',
            'date' => now()->addYear()->toDateString(),
            'items' => [],
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'purchase',
            ])
            ->assertOk()
            ->assertJsonPath('form.date', null);
    }

    public function test_it_sends_the_image_to_the_vision_model(): void
    {
        $fake = $this->fakeExtraction(['party_name' => null, 'items' => []]);

        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'purchase',
            ])
            ->assertOk();

        $fake->assertRequest(function (array $requests): void {
            /** @var StructuredRequest $request */
            $request = $requests[0];

            $this->assertSame('gemini-2.5-flash', $request->model());
            // الصورة اتبعتت فعلاً مع الرسالة
            $this->assertCount(1, $request->messages()[0]->images());
        });
    }

    public function test_it_sends_a_pdf_as_a_document_not_an_image(): void
    {
        $fake = $this->fakeExtraction(['party_name' => null, 'items' => []]);

        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
                'type' => 'purchase',
            ])
            ->assertOk();

        $fake->assertRequest(function (array $requests): void {
            /** @var StructuredRequest $request */
            $request = $requests[0];

            $this->assertCount(1, $request->messages()[0]->documents());
            $this->assertCount(0, $request->messages()[0]->images());
        });
    }

    public function test_it_rejects_an_oversized_file(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/ai/scan-invoice', [
                // 9 ميجا — فوق الحد بميجا
                'file' => UploadedFile::fake()->create('invoice.jpg', 9216, 'image/jpeg'),
                'type' => 'purchase',
            ])
            ->assertJsonValidationErrors('file')
            ->assertJsonPath('errors.file.0', 'الملف لازم يكون أقل من 8 ميجا.');
    }

    public function test_it_rejects_an_unsupported_file_type(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.docx', 10),
                'type' => 'purchase',
            ])
            ->assertJsonValidationErrors('file');
    }

    public function test_it_rejects_an_unknown_invoice_type(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'sale_return',
            ])
            ->assertJsonValidationErrors('type');
    }

    public function test_an_unreadable_invoice_gives_a_friendly_arabic_message(): void
    {
        FailingPrism::fake(
            new PrismStructuredDecodingException('not json')
        );

        $this->actingAs(User::factory()->create())
            ->postJson('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'purchase',
            ])
            ->assertStatus(503)
            ->assertJson([
                'reason' => 'bad_json',
                'message' => 'مقدرتش أقرا الفاتورة دي. جرّب صورة أوضح، أو اكتب البيانات يدوي.',
            ]);
    }

    public function test_it_rate_limits_scanning(): void
    {
        config()->set('ai.rate_limits.scan', 1);

        $this->fakeExtraction(['party_name' => null, 'items' => []]);

        $user = User::factory()->create();

        $this->actingAs($user)->post('/ai/scan-invoice', [
            'file' => UploadedFile::fake()->create('a.jpg', 40, 'image/jpeg'),
            'type' => 'purchase',
        ])->assertOk();

        $this->actingAs($user)->postJson('/ai/scan-invoice', [
            'file' => UploadedFile::fake()->create('b.jpg', 40, 'image/jpeg'),
            'type' => 'purchase',
        ])->assertStatus(429);
    }

    public function test_nothing_is_saved_until_the_user_saves_the_invoice(): void
    {
        $this->fakeExtraction([
            'party_name' => 'شركة النور للتجارة',
            'items' => [
                ['code' => 'HA-9001', 'name' => 'خلاط', 'quantity' => 5, 'unit_price' => 90],
            ],
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
                'type' => 'purchase',
            ])
            ->assertOk();

        // المسح لوحده مابيسجّلش فاتورة ولا حركة مخزون ولا مرفق
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_the_scanned_file_is_attached_when_the_purchase_is_saved(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/purchases', [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => now()->toDateString(),
            'currency' => 'TRY',
            'exchange_rate' => 1.46,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 90],
            ],
            'attachment' => UploadedFile::fake()->create('scan.jpg', 40, 'image/jpeg'),
        ])->assertRedirect();

        $invoice = Invoice::query()->purchases()->sole();

        $this->assertCount(1, $invoice->attachments);
        $this->assertSame('scan.jpg', $invoice->attachments->first()->original_name);
        $this->assertSame('صورة الفاتورة الممسوحة', $invoice->attachments->first()->notes);
    }

    public function test_the_scanned_file_is_attached_when_the_sale_is_saved(): void
    {
        Storage::fake('local');

        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 50,
            'unit_cost' => 100,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs(User::factory()->create())->post('/sales', [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => now()->toDateString(),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 160],
            ],
            'attachment' => UploadedFile::fake()->create('scan.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $invoice = Invoice::query()->sales()->sole();

        $this->assertCount(1, $invoice->attachments);
        $this->assertSame('scan.pdf', $invoice->attachments->first()->original_name);
    }

    public function test_saving_an_invoice_without_a_scan_still_works(): void
    {
        $this->actingAs(User::factory()->create())->post('/purchases', [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => now()->toDateString(),
            'currency' => 'TRY',
            'exchange_rate' => 1.46,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 90],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('attachments', 0);
        $this->assertSame(1, Invoice::query()->purchases()->count());
    }

    /**
     * بيزيّف رد الموديل بالـ JSON اللي كنا متوقعينه من قراءة الصورة.
     *
     * @param  array<string, mixed>  $structured
     */
    private function fakeExtraction(array $structured): PrismFake
    {
        return Prism::fake([
            StructuredResponseFake::make()->withStructured($structured),
        ]);
    }
}
