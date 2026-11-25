<?php

namespace Tests\Feature\Ai;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoiceScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Prism\Prism\Exceptions\PrismStructuredDecodingException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Testing\PrismFake;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\Support\FailingPrism;
use Tests\TestCase;

/**
 * «مسح فاتورة» — من رفع الصورة لحد المسودة.
 *
 * قراءة الصورة متزيّفة بالكامل: `Prism::fake()` بيرجّع الـ JSON اللي
 * كنا متوقعينه من الموديل، فاللي بيتختبر هنا هو المطابقة وبناء
 * المسودة والتحقق — مش الموديل نفسه.
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
        Storage::fake('local');

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
            'file' => $this->image(),
            'type' => 'purchase',
        ])->assertUnauthorized();
    }

    public function test_it_fills_a_purchase_draft_from_the_image(): void
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

        $this->scan('purchase')->assertOk();

        $draft = Invoice::query()->drafts()->sole();
        $form = $draft->scan_payload['form'];
        $review = $draft->scan_payload['review'];

        $this->assertSame(Invoice::TYPE_PURCHASE, $draft->type);
        $this->assertSame($this->supplier->id, $form['supplier_id']);
        $this->assertSame('2026-11-10', $form['date']);
        // العملة بتبدأ جنيه مهما كان المكتوب على الورق — المستخدم بيبدّل بإيده
        $this->assertSame('EGP', $form['currency']);
        $this->assertSame(1.0, (float) $form['exchange_rate']);
        $this->assertSame(1200.0, (float) $form['shipping_cost']);
        $this->assertSame($this->product->id, $form['items'][0]['product_id']);
        $this->assertSame(20.0, (float) $form['items'][0]['quantity']);
        $this->assertSame(95.5, $form['items'][0]['unit_price']);

        $this->assertSame(InvoiceScanService::MATCH_EXACT, $review['party']['confidence']);
        // الورق كاتب ليرة وإحنا حاطّين جنيه — الخانة لازم تتعلّم للمراجعة
        $this->assertSame(['currency', 'exchange_rate'], $review['fields']);

        // رقم الفاتورة الأصلي بيتحفظ في الملاحظات
        $this->assertStringContainsString('INV-553', $form['notes']);
    }

    public function test_a_sale_draft_has_no_purchase_only_fields(): void
    {
        $this->fakeExtraction([
            'party_name' => 'محمد عبد الله',
            'date' => '2026-11-10',
            'items' => [
                ['code' => null, 'name' => 'خلاط كهربائي', 'quantity' => 3, 'unit_price' => 175],
            ],
        ]);

        $this->scan('sale')->assertOk();

        $form = Invoice::query()->drafts()->sole()->scan_payload['form'];

        $this->assertSame($this->customer->id, $form['customer_id']);
        $this->assertSame(175.0, (float) $form['items'][0]['unit_price']);
        $this->assertArrayNotHasKey('currency', $form);
        $this->assertArrayNotHasKey('exchange_rate', $form);
        $this->assertArrayNotHasKey('shipping_cost', $form);
    }

    public function test_a_sale_line_without_a_price_falls_back_to_the_traders_tier(): void
    {
        $this->fakeExtraction([
            'party_name' => 'محمد عبد الله',
            'items' => [
                ['code' => 'HA-9001', 'name' => 'خلاط كهربائي', 'quantity' => 2, 'unit_price' => null],
            ],
        ]);

        $this->scan('sale')->assertOk();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        // التاجر شريحته جملة الجملة، فالسعر 160 مش 180
        $this->assertSame(160.0, (float) $payload['form']['items'][0]['unit_price']);
        $this->assertTrue($payload['review']['items'][0]['priceFromSystem']);
    }

    public function test_it_matches_by_code_before_name(): void
    {
        $this->fakeExtraction([
            'party_name' => 'شركة النور للتجارة',
            'items' => [
                ['code' => 'HA-9001', 'name' => 'حاجة مكتوبة غلط', 'quantity' => 1, 'unit_price' => 10],
            ],
        ]);

        $this->scan('purchase')->assertOk();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        $this->assertSame($this->product->id, $payload['form']['items'][0]['product_id']);
        $this->assertSame('code', $payload['review']['items'][0]['via']);
    }

    public function test_it_matches_arabic_names_written_differently(): void
    {
        // «شركه» بهاء وتطويل، و«كهربائى» بألف مقصورة
        $this->fakeExtraction([
            'party_name' => 'شركه النور للتجـارة',
            'items' => [
                ['code' => null, 'name' => 'خلاط كهربائى', 'quantity' => 1, 'unit_price' => 90],
            ],
        ]);

        $this->scan('purchase')->assertOk();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        $this->assertSame($this->supplier->id, $payload['form']['supplier_id']);
        $this->assertSame($this->product->id, $payload['form']['items'][0]['product_id']);
        $this->assertSame(InvoiceScanService::MATCH_LIKELY, $payload['review']['party']['confidence']);
    }

    public function test_it_matches_the_party_by_phone_when_the_name_differs(): void
    {
        $this->fakeExtraction([
            'party_name' => 'النور تريدينج',
            'party_phone' => '٠١٠-١٢٣٤ ٥٦٧٨',
            'items' => [],
        ]);

        $this->scan('purchase')->assertOk();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        $this->assertSame($this->supplier->id, $payload['form']['supplier_id']);
        $this->assertSame(InvoiceScanService::MATCH_LIKELY, $payload['review']['party']['confidence']);
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

        $this->scan('purchase')->assertOk();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        // مفيش تخمين — الحقول فاضية والمستخدم هو اللي يختار
        $this->assertNull($payload['form']['supplier_id']);
        $this->assertNull($payload['form']['items'][0]['product_id']);
        $this->assertSame(InvoiceScanService::MATCH_NONE, $payload['review']['party']['confidence']);
        $this->assertSame('مورد مش موجود في النظام', $payload['review']['party']['extracted']);
        $this->assertSame('صنف مش موجود', $payload['review']['items'][0]['extracted']['name']);

        $this->assertContains('date', $payload['review']['fields']);
        $this->assertContains('currency', $payload['review']['fields']);
    }

    public function test_arabic_indic_quantities_and_prices_are_normalised(): void
    {
        // الموديل ساعات بيرجّع الأرقام زي ما هي على الورق، ومعاها الوحدة
        $this->fakeExtraction([
            'party_name' => 'محمد عبد الله',
            'items' => [
                ['code' => null, 'name' => 'خلاط كهربائي', 'quantity' => '٣ ك', 'unit_price' => '٣٤٢'],
            ],
        ]);

        $this->scan('sale')->assertOk();

        $form = Invoice::query()->drafts()->sole()->scan_payload['form'];

        $this->assertSame(3.0, (float) $form['items'][0]['quantity']);
        $this->assertSame(342.0, (float) $form['items'][0]['unit_price']);
    }

    /**
     * المسح مابيفترضش عملة أجنبية — ولا الفورم العادي كمان. كل فورم
     * فاتورة في النظام بيبدأ بالجنيه وسعر صرف ١، والمستخدم هو اللي
     * يبدّل لدولار أو يورو بإيده.
     */
    public function test_a_scan_without_a_written_currency_defaults_to_egp(): void
    {
        $this->fakeExtraction([
            // مورد مش موجود، فمفيش عملة نرثها منه كمان
            'party_name' => 'مورد مش موجود',
            'currency' => null,
            'items' => [],
        ]);

        $this->scan('purchase')->assertOk();

        $form = Invoice::query()->drafts()->sole()->scan_payload['form'];

        $this->assertSame('EGP', $form['currency']);
        $this->assertSame(1.0, (float) $form['exchange_rate']);
    }

    /**
     * حتى العملة المكتوبة على الورق مابتغيّرش الفورم — بتتعلّم للمراجعة
     * وخلاص. الفورم بيفضل جنيه لحد ما المستخدم يبدّل بإيده.
     */
    public function test_a_written_currency_is_flagged_not_applied(): void
    {
        $this->fakeExtraction([
            'party_name' => 'شركة النور للتجارة',
            'currency' => 'USD',
            'exchange_rate' => '49.4',
            'items' => [],
        ]);

        $this->scan('purchase')->assertOk();

        $draft = Invoice::query()->drafts()->sole();

        $this->assertSame('EGP', $draft->scan_payload['form']['currency']);
        $this->assertSame(1.0, (float) $draft->scan_payload['form']['exchange_rate']);
        $this->assertContains('currency', $draft->scan_payload['review']['fields']);
    }

    /**
     * عملة المورد مابتتورّثش.
     *
     * مورد متسجّل بالليرة كان بيخلّي كل فاتورة ممسوحة ليه تطلع بالليرة،
     * فورقة بـ 500 جنيه بتتحوّل 500 ليرة من غير ما حد ياخد باله.
     */
    public function test_a_matched_supplier_does_not_lend_its_currency(): void
    {
        $this->fakeExtraction([
            'party_name' => 'شركة النور للتجارة',
            'currency' => null,
            'items' => [],
        ]);

        $this->scan('purchase')->assertOk();

        $this->assertSame(
            'EGP',
            Invoice::query()->drafts()->sole()->scan_payload['form']['currency'],
        );
    }

    /**
     * المستخدم بيبدأ المسح من شاشة، وساعات بيصوّر فاتورة من النوع
     * التاني. الاسم هو اللي بيكشفها.
     */
    public function test_a_sale_invoice_scanned_from_purchases_is_flagged(): void
    {
        $this->fakeExtraction([
            // اسم تاجر، مش مورد
            'party_name' => 'محمد عبد الله',
            'items' => [],
        ]);

        $this->scan('purchase')->assertOk();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        $this->assertSame(Invoice::TYPE_SALE, $payload['review']['suggestedType']);
    }

    public function test_a_purchase_invoice_scanned_from_sales_is_flagged(): void
    {
        $this->fakeExtraction([
            'party_name' => 'شركة النور للتجارة',
            'items' => [],
        ]);

        $this->scan('sale')->assertOk();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        $this->assertSame(Invoice::TYPE_PURCHASE, $payload['review']['suggestedType']);
    }

    public function test_the_right_type_is_not_flagged(): void
    {
        $this->fakeExtraction([
            'party_name' => 'شركة النور للتجارة',
            'items' => [],
        ]);

        $this->scan('purchase')->assertOk();

        $this->assertNull(
            Invoice::query()->drafts()->sole()->scan_payload['review']['suggestedType'],
        );
    }

    /**
     * التحويل بيعيد بناء المطابقة من نفس القراءة — من غير ما يقرا
     * الصورة تاني، فمفيش نداء جديد للمزوّد.
     */
    public function test_a_draft_can_be_switched_to_the_other_type(): void
    {
        $this->fakeExtraction([
            'party_name' => 'محمد عبد الله',
            'items' => [
                ['code' => 'HA-9001', 'name' => 'خلاط كهربائي', 'quantity' => '٢', 'unit_price' => '١٥٠'],
            ],
        ]);

        $this->scan('purchase')->assertOk();

        $draft = Invoice::query()->drafts()->sole();

        $this->actingAs(User::factory()->create())
            ->post(route('drafts.switch-type', $draft))
            ->assertRedirect(route('sales.drafts.show', $draft));

        $draft->refresh();

        $this->assertSame(Invoice::TYPE_SALE, $draft->type);
        $this->assertStringStartsWith('S-', $draft->number);
        $this->assertSame($this->customer->id, $draft->customer_id);
        $this->assertNull($draft->supplier_id);

        // البنود اتبنت تاني للنوع الجديد
        $form = $draft->scan_payload['form'];

        $this->assertSame($this->product->id, $form['items'][0]['product_id']);
        $this->assertArrayNotHasKey('currency', $form);
    }

    public function test_a_future_date_is_dropped_because_the_form_rejects_it(): void
    {
        $this->fakeExtraction([
            'party_name' => 'شركة النور للتجارة',
            'date' => now()->addYear()->toDateString(),
            'items' => [],
        ]);

        $this->scan('purchase')->assertOk();

        $this->assertNull(Invoice::query()->drafts()->sole()->scan_payload['form']['date']);
    }

    public function test_it_sends_the_image_to_the_vision_model(): void
    {
        $fake = $this->fakeExtraction(['party_name' => null, 'items' => []]);

        $this->scan('purchase')->assertOk();

        $fake->assertRequest(function (array $requests): void {
            /** @var StructuredRequest $request */
            $request = $requests[0];

            $this->assertSame('gemini-3.5-flash', $request->model());
            $this->assertCount(1, $request->messages()[0]->images());
        });
    }

    public function test_it_sends_a_pdf_as_a_document_not_an_image(): void
    {
        $fake = $this->fakeExtraction(['party_name' => null, 'items' => []]);

        $this->scan('purchase', UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'))
            ->assertOk();

        $fake->assertRequest(function (array $requests): void {
            /** @var StructuredRequest $request */
            $request = $requests[0];

            $this->assertCount(1, $request->messages()[0]->documents());
            $this->assertCount(0, $request->messages()[0]->images());
        });
    }

    /**
     * فواتير العميل مكتوبة بخط اليد وتلات خانات بس، واسم العميل في
     * أول سطر من خانة الصنف. التعليمات دي لازم توصل للموديل، وإلا
     * هيقرا اسم العميل كأنه صنف.
     */
    public function test_a_sale_scan_explains_the_handwritten_layout(): void
    {
        $fake = $this->fakeExtraction(['party_name' => null, 'items' => []]);

        $this->scan('sale')->assertOk();

        $fake->assertRequest(function (array $requests): void {
            /** @var StructuredRequest $request */
            $request = $requests[0];

            $prompt = $request->messages()[0]->text();

            $this->assertStringContainsString('أول سطر في خانة', $prompt);
            $this->assertStringContainsString('«ك» يعني كرتونة', $prompt);
            $this->assertStringContainsString('الجوز', $prompt);
            $this->assertStringContainsString('مقلوبة ٩٠ درجة', $prompt);
            $this->assertStringContainsString('أقرب قراءة معقولة', $prompt);
        });
    }

    public function test_it_rejects_an_oversized_file(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/ai/scan-invoice', [
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
                'file' => $this->image(),
                'type' => 'sale_return',
            ])
            ->assertJsonValidationErrors('type');
    }

    public function test_an_unreadable_invoice_gives_a_friendly_arabic_message(): void
    {
        FailingPrism::fake(new PrismStructuredDecodingException('not json'));

        $this->actingAs(User::factory()->create())
            ->postJson('/ai/scan-invoice', ['file' => $this->image(), 'type' => 'purchase'])
            ->assertStatus(503)
            ->assertJson([
                'reason' => 'bad_json',
                'message' => 'مقدرتش أقرا الفاتورة دي. جرّب صورة أوضح، أو اكتب البيانات يدوي.',
            ]);

        // العطل مابيسيبش مسودة ورا
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_it_rate_limits_scanning(): void
    {
        config()->set('ai.rate_limits.scan', 1);

        $this->fakeExtraction(['party_name' => null, 'items' => []]);

        $user = User::factory()->create();

        $this->actingAs($user)->post('/ai/scan-invoice', [
            'file' => $this->image('a.jpg'), 'type' => 'purchase',
        ])->assertOk();

        $this->actingAs($user)->postJson('/ai/scan-invoice', [
            'file' => $this->image('b.jpg'), 'type' => 'purchase',
        ])->assertStatus(429);
    }

    /**
     * بيت القصيد: المسودة موجودة، بس مالهاش أي أثر على المخزون ولا
     * الأرصدة ولا الخزينة لحد ما تتأكّد.
     */
    public function test_the_scan_creates_a_draft_that_touches_nothing(): void
    {
        $this->fakeExtraction([
            'party_name' => 'محمد عبد الله',
            'items' => [
                ['code' => 'HA-9001', 'name' => 'خلاط', 'quantity' => 5, 'unit_price' => 90],
            ],
        ]);

        $response = $this->scan('sale')->assertOk();

        $draft = Invoice::query()->drafts()->sole();

        $this->assertSame(Invoice::STATUS_DRAFT, $draft->status);
        $this->assertNotNull($draft->scanned_at);

        // ولا حركة مخزون ولا بند فاتورة ولا دفعة
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(0.0, (float) $draft->total);

        // والصورة اتعلّقت على المسودة عشان تتعرض جنب الفورم
        $this->assertCount(1, $draft->attachments);

        $response->assertJsonPath('redirect', route('sales.drafts.show', $draft));
    }

    public function test_the_draft_shows_in_the_list_but_not_in_the_totals(): void
    {
        $this->fakeExtraction([
            'party_name' => 'محمد عبد الله',
            'items' => [['code' => 'HA-9001', 'name' => 'خلاط', 'quantity' => 5, 'unit_price' => 90]],
        ]);

        $this->scan('sale')->assertOk();

        $this->actingAs(User::factory()->create())
            ->get('/sales')
            ->assertInertia(fn ($page) => $page
                ->where('invoices.data.0.status', Invoice::STATUS_DRAFT)
                ->where('totals.total', 0)
                ->etc()
            );
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    private function fakeExtraction(array $structured): PrismFake
    {
        return Prism::fake([
            StructuredResponseFake::make()->withStructured($structured),
        ]);
    }

    private function scan(string $type, ?UploadedFile $file = null): TestResponse
    {
        return $this->actingAs(User::factory()->create())->post('/ai/scan-invoice', [
            'file' => $file ?? $this->image(),
            'type' => $type,
        ]);
    }

    private function image(string $name = 'invoice.jpg'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 40, 'image/jpeg');
    }
}
