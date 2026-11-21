<?php

namespace Tests\Feature\Ai;

use App\Ai\InvoiceSchema;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AliasService;
use App\Services\InvoiceScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\TestCase;

/**
 * الفاتورة الحقيقية بتاعة العميل.
 *
 * `tests/fixtures/client-invoice.jpeg` صورة فاتورة فعلية: مكتوبة بخط
 * اليد على دفتر مطبوع، تلات خانات (الكمية / اسم العميل والصنف /
 * السعر)، اسم العميل في أول سطر، أرقام هندية، وحدات مختصرة بـ«ك»،
 * وسعر مكتوب «الجوز»، والصورة مايلة وعليها ظل.
 *
 * الصورة بتترفع فعلاً (فبنتأكد إن الملف نفسه بيعدّي من التحقق)، لكن
 * رد الموديل متزيّف بالقراءة اللي متوقعينها منه — مفيش نداء شبكة.
 */
class HandwrittenInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE = 'tests/fixtures/client-invoice.jpeg';

    private Customer $customer;

    private Product $kabouli;

    private Product $faruq;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('prism.providers.gemini.api_key', 'test-key');
        config()->set('ai.enabled', true);
        config()->set('ai.provider', 'gemini');

        Storage::fake('local');

        Warehouse::create(['name' => 'المخزن الرئيسي']);
        $category = Category::create(['name' => 'بقالة']);

        $this->customer = Customer::create([
            'name' => 'وليد عبد العظيم',
            'price_tier' => 'wholesale',
        ]);

        $this->kabouli = Product::create([
            'sku' => 'KB-001',
            'name' => 'كبولي مجوز',
            'category_id' => $category->id,
            'unit' => 'كرتونة',
            'purchase_price' => 150,
            'sale_price' => 220,
            'wholesale_price' => 200,
            'min_stock' => 5,
        ]);

        $this->faruq = Product::create([
            'sku' => 'FR-001',
            'name' => 'الفاروق فضي',
            'category_id' => $category->id,
            'unit' => 'كرتونة',
            'purchase_price' => 80,
            'sale_price' => 120,
            'wholesale_price' => 110,
            'min_stock' => 5,
        ]);
    }

    public function test_the_real_invoice_image_is_accepted(): void
    {
        $this->assertFileExists(base_path(self::FIXTURE));

        $this->fakeReading();

        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', [
                'file' => $this->fixture(),
                'type' => 'sale',
            ])
            ->assertOk();

        // الصورة اتعلّقت على المسودة عشان تتعرض جنب الفورم
        $draft = Invoice::query()->drafts()->sole();

        $this->assertCount(1, $draft->attachments);
        $this->assertSame('client-invoice.jpeg', $draft->attachments->first()->original_name);
    }

    /**
     * اسم العميل مكتوب في أول سطر من خانة الصنف، مش في خانة لوحده.
     * لازم يروح للتاجر مش يبقى بند.
     */
    public function test_the_customer_name_becomes_the_party_not_a_line(): void
    {
        $this->fakeReading();

        $this->scan();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        $this->assertSame('وليد الثلاثين', $payload['review']['party']['extracted']);

        $names = array_column(
            array_column($payload['review']['items'], 'extracted'),
            'name'
        );

        $this->assertNotContains('وليد الثلاثين', $names);
    }

    public function test_arabic_indic_quantities_with_unit_letters_are_read_as_numbers(): void
    {
        $this->fakeReading();

        $this->scan();

        $form = Invoice::query()->drafts()->sole()->scan_payload['form'];

        // «١ ك» و«٣ ك» و«٢٠٠»
        $this->assertSame(1.0, (float) $form['items'][0]['quantity']);
        $this->assertSame(3.0, (float) $form['items'][1]['quantity']);
        $this->assertSame(200.0, (float) $form['items'][2]['quantity']);
    }

    /**
     * «حوز» قراية وحشة لـ«جوز». الموديل بيرجّع أقرب قراءة، والنظام
     * بيعلّمها صفرا عشان المستخدم يراجع.
     */
    public function test_a_messy_reading_is_flagged_for_review(): void
    {
        $this->fakeReading();

        $this->scan();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;
        $kabouliLine = $payload['review']['items'][2];

        $this->assertSame('كبولى حوز سيوستة بدون دعم', $kabouliLine['extracted']['name']);
        $this->assertSame(InvoiceScanService::MATCH_NONE, $kabouliLine['confidence']);
        $this->assertNull($payload['form']['items'][2]['product_id']);
    }

    /** الاسم اللي اتطابق بالتطبيع بيعدّي، بس متعلّم «محتاج نظرة» */
    public function test_a_close_spelling_still_matches(): void
    {
        $this->fakeReading();

        $this->scan();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        // «الفاروق فضى» بألف مقصورة — نفس «الفاروق فضي»
        $this->assertSame($this->faruq->id, $payload['form']['items'][0]['product_id']);
        $this->assertSame(InvoiceScanService::MATCH_LIKELY, $payload['review']['items'][0]['confidence']);
    }

    /**
     * بعد ما المستخدم يصحّح «كبولى حوز» مرة واحدة، الفاتورة الجاية من
     * نفس العميل بتتقرا لوحدها.
     */
    public function test_once_corrected_the_same_handwriting_matches_next_time(): void
    {
        app(AliasService::class)->rememberProduct(
            $this->kabouli,
            'كبولى حوز سيوستة بدون دعم',
        );

        app(AliasService::class)->rememberCustomer($this->customer, 'وليد الثلاثين');

        $this->fakeReading();

        $this->scan();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        $this->assertSame($this->customer->id, $payload['form']['customer_id']);
        $this->assertSame('alias', $payload['review']['party']['via']);

        $this->assertSame($this->kabouli->id, $payload['form']['items'][2]['product_id']);
        $this->assertSame('alias', $payload['review']['items'][2]['via']);
    }

    public function test_a_line_with_no_price_falls_back_to_the_tier_price(): void
    {
        app(AliasService::class)->rememberCustomer($this->customer, 'وليد الثلاثين');

        $this->fakeReading();

        $this->scan();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        // «الفاروق فضي» السعر عليه مش مقروء — بياخد سعر الجملة 110
        $this->assertSame(110.0, (float) $payload['form']['items'][0]['unit_price']);
        $this->assertTrue($payload['review']['items'][0]['priceFromSystem']);
    }

    /**
     * السعر لازم يطلع حتى لما يكون مكتوب معاه حروف.
     *
     * ده اللي وقع على السيرفر: الـ schema كان بيطلب `unit_price` كرقم،
     * والورق مكتوب فيه «٤٠ ج»، فالموديل مالقاش رقم صالح يرجّعه وساب
     * الخانة فاضية — عمود السعر كله رجع فاضي.
     */
    public function test_handwritten_prices_with_letters_are_extracted(): void
    {
        $this->fakeReading();

        $this->scan();

        $form = Invoice::query()->drafts()->sole()->scan_payload['form'];

        // «٣٤٢ ج» و«٤٠ ج الجوز»
        $this->assertSame(342.0, (float) $form['items'][1]['unit_price']);
        $this->assertSame(40.0, (float) $form['items'][2]['unit_price']);
    }

    /**
     * الـ schema نفسه لازم يطلب الكمية والسعر كنص.
     *
     * `Prism::fake()` مابيتحققش من الـ schema، فالاختبار اللي بيعدّي
     * نص في خانة رقم بيعدّي وهو غلط. بنفحص الـ schema نفسه هنا.
     */
    public function test_the_schema_asks_for_quantity_and_price_as_text(): void
    {
        $line = InvoiceSchema::make()->toArray()['properties']['items']['items'];

        // nullable فبيبقى ['string', 'null'] — المهم إنه مش 'number'
        $this->assertContains('string', (array) $line['properties']['unit_price']['type']);
        $this->assertContains('string', (array) $line['properties']['quantity']['type']);
        $this->assertNotContains('number', (array) $line['properties']['unit_price']['type']);
    }

    /**
     * الموديل ساعات بياخد آخر سطر صنف ويحطّه كاسم عميل. اسم العميل في
     * أول سطر دايمًا، فخمس سطور بيطلعوا عميل و٤ أصناف.
     */
    public function test_the_last_line_is_never_taken_as_the_party(): void
    {
        $this->fakeMisreadParty();

        $this->scan();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        // أول سطر بقى اسم العميل
        $this->assertSame('وليد الثلاثين', $payload['review']['party']['extracted']);

        // والأربعة الباقيين أصناف — آخر واحد لسه صنف
        $this->assertCount(4, $payload['review']['items']);

        $names = array_column(
            array_column($payload['review']['items'], 'extracted'),
            'name'
        );

        $this->assertSame('احمد مجرول', $names[3]);
        $this->assertNotContains('وليد الثلاثين', $names);
    }

    public function test_a_correct_reading_is_left_alone(): void
    {
        $this->fakeReading();

        $this->scan();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        // اسم العميل مش مكرر في السطور، فمفيش تصحيح بيحصل
        $this->assertSame('وليد الثلاثين', $payload['review']['party']['extracted']);
        $this->assertCount(3, $payload['review']['items']);
    }

    /**
     * القراءة اللي متوقعينها من الموديل للصورة دي.
     *
     * مكتوبة بإيد عن قصد: الاختبار بيتأكد من المطابقة وبناء المسودة،
     * مش من دقة الموديل.
     */
    private function fakeReading(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'party_name' => 'وليد الثلاثين',
                'party_code' => null,
                'party_phone' => null,
                'number' => null,
                'date' => null,
                'currency' => null,
                'exchange_rate' => null,
                'discount' => null,
                'shipping_cost' => null,
                'customs_cost' => null,
                'clearance_cost' => null,
                'notes' => null,
                'items' => [
                    // «١ ك | الفاروق فضى» — خانة السعر فاضية على الورق
                    ['code' => null, 'name' => 'الفاروق فضى', 'quantity' => '١ ك', 'unit_price' => null],
                    // «٣ ك | ٧٧٧ بلاوظ | ٣٤٢ ج»
                    ['code' => null, 'name' => '٧٧٧ بلاوظ', 'quantity' => '٣ ك', 'unit_price' => '٣٤٢ ج'],
                    // «٢٠٠ | كبولى حوز سيوستة بدون دعم | ٤٠ ج الجوز»
                    ['code' => null, 'name' => 'كبولى حوز سيوستة بدون دعم', 'quantity' => '٢٠٠', 'unit_price' => '٤٠ ج الجوز'],
                ],
            ]),
        ]);
    }

    /**
     * نفس الفاتورة بس الموديل خد آخر سطر («احمد مجرول») كاسم عميل
     * وساب السطور كلها في الأصناف — ده اللي حصل على السيرفر بالظبط.
     */
    private function fakeMisreadParty(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'party_name' => 'احمد مجرول',
                'party_code' => null,
                'party_phone' => null,
                'number' => null,
                'date' => null,
                'currency' => null,
                'exchange_rate' => null,
                'discount' => null,
                'shipping_cost' => null,
                'customs_cost' => null,
                'clearance_cost' => null,
                'notes' => null,
                'items' => [
                    ['code' => null, 'name' => 'وليد الثلاثين', 'quantity' => null, 'unit_price' => null],
                    ['code' => null, 'name' => 'الفاروق فضى', 'quantity' => '١ ك', 'unit_price' => null],
                    ['code' => null, 'name' => '٧٧٧ بلاوظ', 'quantity' => '٣ ك', 'unit_price' => '٣٤٢ ج'],
                    ['code' => null, 'name' => 'كبولى حوز سيوستة', 'quantity' => '٢٠٠', 'unit_price' => '٤٠ ج'],
                    ['code' => null, 'name' => 'احمد مجرول', 'quantity' => '٢ ك', 'unit_price' => '٩٠ ج'],
                ],
            ]),
        ]);
    }

    private function scan(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/ai/scan-invoice', ['file' => $this->fixture(), 'type' => 'sale'])
            ->assertOk();
    }

    /**
     * الصورة الحقيقية — بتتنسخ لملف مؤقت عشان الرفع ما يحركش الأصل.
     */
    private function fixture(): UploadedFile
    {
        $source = base_path(self::FIXTURE);
        $temp = tempnam(sys_get_temp_dir(), 'scan').'.jpeg';

        copy($source, $temp);

        return new UploadedFile($temp, 'client-invoice.jpeg', 'image/jpeg', null, true);
    }
}
