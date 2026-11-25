<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ExportBuilder;
use App\Services\SpreadsheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

/**
 * تحميل PDF وإكسيل.
 *
 * الـ PDF بيتولّد فعلاً هنا (mPDF شغّال في الاختبارات)، فاللي بيتفحص
 * هو الملف الحقيقي مش mock — عشان لو الخط العربي باظ الاختبار يقع.
 *
 * **والكاش بيتمسح قبل الرسم.** ده مش تفصيلة: mPDF بيخزّن تحليل الخط
 * في `storage/app/mpdf`، وقبل كده الاختبار كان بيعدّي على الكاش الدافي
 * بتاع جهاز التطوير وهو عمّال يخفي إن الخط نفسه مابيتقراش. السيرفر
 * بيبدأ بكاش فاضي، فالاختبار لازم يبدأ زيه.
 */
class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Supplier $supplier;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $category = Category::create(['name' => 'بقالة']);

        $this->customer = Customer::create([
            'name' => 'وليد عبد العظيم',
            'price_tier' => 'sale',
        ]);

        $this->supplier = Supplier::create([
            'name' => 'شركة النور',
            'currency' => 'TRY',
        ]);

        $product = Product::create([
            'sku' => 'KB-25', 'name' => 'كبولي مجوز', 'category_id' => $category->id,
            'unit' => 'جوز', 'purchase_price' => 100, 'sale_price' => 150, 'min_stock' => 5,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 50,
            'unit_cost' => 100,
            'date' => now()->subDays(10)->toDateString(),
        ]);

        $this->invoice = Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => 'S-2026-00001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->admin->id,
            'date' => now()->subDays(3)->toDateString(),
            'status' => Invoice::STATUS_CONFIRMED,
            'subtotal' => 1500,
            'tax' => 210,
            'total' => 1710,
            'paid' => 500,
        ]);

        InvoiceItem::create([
            'invoice_id' => $this->invoice->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 150,
            'total' => 1500,
        ]);
    }

    // ── الخط ──────────────────────────────────────────────────────

    /**
     * الخط المتضمّن لازم يفضل ثابت.
     *
     * الخط المتغيّر (variable) بيبوّظ كل PDF في النظام: mPDF مابيعرفهوش،
     * وبيقع وهو بيقرا جداوله بـ «Undefined array key 0». الفحص ده أسرع
     * وأوضح من إننا نستنى الرسم يقع — بيقول الغلط نفسه على طول.
     */
    public function test_the_bundled_arabic_fonts_are_static_not_variable(): void
    {
        $fonts = glob(storage_path('fonts/*.ttf')) ?: [];

        $this->assertNotEmpty($fonts, 'مفيش خط متضمّن في storage/fonts.');

        foreach ($fonts as $font) {
            foreach (['fvar', 'gvar', 'HVAR'] as $table) {
                $this->assertNotContains(
                    $table,
                    $this->fontTables($font),
                    basename($font).' خط متغيّر — mPDF مابيرسمش بيه. ولّد نسخة ثابتة بـ fonttools varLib.instancer.',
                );
            }
        }
    }

    // ── PDF ───────────────────────────────────────────────────────

    public function test_a_report_downloads_as_a_real_pdf(): void
    {
        $this->forgetFontCache();

        $response = $this->actingAs($this->admin)
            ->get('/exports/report?report=period')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $pdf = $response->getContent();

        // ملف PDF حقيقي وفيه محتوى
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(5000, strlen($pdf));

        // الخط المتضمّن اتقرا فعلاً بجداول التشكيل العربي بتاعته
        $this->assertFileExists(
            storage_path('app/mpdf/mpdf/ttfontdata/tajawal.GSUB.arab.DFLT.json'),
        );
    }

    /**
     * الخط المرسوم بيه هو خطنا إحنا.
     *
     * ده مش تحصيل حاصل: mPDF بيقرا `lang="ar"` من القالب و`autoLangToFont`
     * بيخلّيه يستبدل خطنا بخطه هو (XB Riyaz) من غير أي تنبيه — فالخط
     * اللي ضامّينه للمشروع يفضل قاعد مالوش لازمة والـ PDF تطلع بخط
     * تاني. الفحص على أسماء الخطوط المحقونة في الملف نفسه.
     */
    public function test_the_pdf_embeds_our_bundled_font_not_mpdfs_own(): void
    {
        $pdf = $this->actingAs($this->admin)
            ->get('/exports/report?report=period')
            ->assertOk()
            ->getContent();

        $fonts = $this->embeddedFonts($pdf);

        $this->assertNotEmpty($fonts, 'مفيش أي خط محقون في الـ PDF.');

        foreach ($fonts as $font) {
            $this->assertStringContainsString(
                'Tajawal',
                $font,
                "الـ PDF اترسم بـ {$font} مش بالخط المتضمّن — شوف autoLangToFont.",
            );
        }

        // العريض والعادي الاتنين — العناوين وعناوين الأعمدة عريضة
        $this->assertContains('MPDFAA+Tajawal-Regular', $fonts);
        $this->assertContains('MPDFAA+Tajawal-Bold', $fonts);
    }

    /**
     * كل نوع تصدير بيترسم فعلاً وكاش الخط فاضي.
     *
     * ده الاختبار اللي كان ناقص لما كل PDF على السيرفر رجّع 500: كل نوع
     * له قالب وبيانات مختلفة، وكان فيه أنواع محدش جرّبها بكاش بارد.
     */
    public function test_every_export_type_renders_arabic_from_a_cold_font_cache(): void
    {
        $this->forgetFontCache();

        $purchase = $this->purchaseInvoice();

        $urls = [
            'تقرير بالفترة' => '/exports/report?report=period',
            'تقرير بالصنف' => '/exports/report?report=product',
            'تقرير بالتاجر' => '/exports/report?report=customer',
            'تقييم المخزون' => '/exports/report?report=inventory',
            'كشف تاجر' => "/exports/customers/{$this->customer->id}",
            'كشف مورد' => "/exports/suppliers/{$this->supplier->id}",
            'فاتورة بيع' => "/exports/invoices/{$this->invoice->id}",
            'فاتورة شراء' => "/exports/invoices/{$purchase->id}",
        ];

        foreach ($urls as $label => $url) {
            $response = $this->actingAs($this->admin)->get($url);

            $this->assertSame(200, $response->getStatusCode(), "{$label} ما اترسمش: {$url}");
            $this->assertStringStartsWith('%PDF-', $response->getContent(), $label);
            $this->assertGreaterThan(3000, strlen($response->getContent()), $label);
        }
    }

    public function test_the_pdf_filename_carries_the_arabic_title(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/exports/report?report=product')
            ->assertOk();

        $disposition = $response->headers->get('content-disposition');

        // اسم لاتيني احتياطي + النسخة العربية بصيغة RFC 5987
        $this->assertStringContainsString("filename*=UTF-8''", $disposition);
        $this->assertStringContainsString(rawurlencode('تقرير المبيعات بالصنف'), $disposition);
    }

    public function test_every_report_type_renders(): void
    {
        foreach (array_keys(ExportBuilder::REPORT_TITLES) as $report) {
            $this->actingAs($this->admin)
                ->get("/exports/report?report={$report}")
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_a_customer_statement_downloads(): void
    {
        $this->actingAs($this->admin)
            ->get("/exports/customers/{$this->customer->id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_a_supplier_statement_downloads(): void
    {
        $this->actingAs($this->admin)
            ->get("/exports/suppliers/{$this->supplier->id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * الكشف لازم يشيل رقم الفاتورة.
     *
     * من غيره الكشف مابينفعش يتطابق مع أي حاجة: اللي بيستلمه بيبص على
     * سطر «فاتورة بيع» بـ 1,710 ومعندوش حاجة يربطها بفاتورة بعينها.
     */
    public function test_a_statement_carries_the_invoice_number_as_a_column(): void
    {
        $table = app(ExportBuilder::class)->statement([
            'opening' => 0,
            'rows' => [[
                'date' => '2026-11-20', 'description' => 'فاتورة بيع',
                'reference' => 'S-2026-00001',
                'debit' => 1710, 'credit' => 0, 'balance' => 1710,
            ]],
            'closing' => 1710,
        ]);

        $this->assertContains('رقم الفاتورة', $table['headers']);
        $this->assertSame('S-2026-00001', $table['rows'][0][2]);

        // أعمدة الأرقام لازم تزحزح معاها، وإلا التحاذي بيبوظ
        $this->assertSame([3, 4, 5], $table['numeric']);
    }

    public function test_the_statement_excel_carries_the_invoice_number(): void
    {
        $sheet = $this->sheetOf(
            "/exports/customers/{$this->customer->id}?format=excel"
        );

        $this->assertStringContainsString('رقم الفاتورة', $sheet);
        $this->assertStringContainsString('S-2026-00001', $sheet);
    }

    /** التحميل بياخد نفس بحث الشاشة — الملف لازم يطابق اللي قدام المستخدم */
    public function test_a_statement_export_honours_the_invoice_number_search(): void
    {
        $sheet = $this->sheetOf(
            "/exports/customers/{$this->customer->id}?format=excel&search=S-2026-00001"
        );

        $this->assertStringContainsString('S-2026-00001', $sheet);

        $empty = $this->sheetOf(
            "/exports/customers/{$this->customer->id}?format=excel&search=S-2026-09999"
        );

        $this->assertStringNotContainsString('S-2026-00001', $empty);
    }

    public function test_a_sale_invoice_downloads(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/exports/invoices/{$this->invoice->id}")
            ->assertOk();

        $this->assertStringContainsString(
            rawurlencode('فاتورة بيع S-2026-00001'),
            $response->headers->get('content-disposition'),
        );
    }

    public function test_a_draft_cannot_be_downloaded(): void
    {
        $this->invoice->update(['status' => Invoice::STATUS_DRAFT]);

        $this->actingAs($this->admin)
            ->get("/exports/invoices/{$this->invoice->id}")
            ->assertNotFound();
    }

    public function test_guests_cannot_download(): void
    {
        $this->get('/exports/report')->assertRedirect('/login');
    }

    // ── إكسيل ─────────────────────────────────────────────────────

    public function test_a_report_downloads_as_a_real_xlsx(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/exports/report?report=period&format=excel')
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->getContent());

        // زِب صالح فيه أجزاء xlsx الحقيقية
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($path) === true);
        $this->assertNotFalse($zip->locateName('xl/workbook.xml'));
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet1.xml'));

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');

        // من اليمين للشمال، وفيه عناويننا العربية
        $this->assertStringContainsString('rightToLeft="1"', $sheet);
        $this->assertStringContainsString('الإجمالي', $sheet);

        $zip->close();
        unlink($path);
    }

    public function test_excel_keeps_numbers_as_numbers(): void
    {
        $table = app(ExportBuilder::class)->invoice([
            'currency' => 'EGP',
            'items' => [[
                'sku' => 'A-1', 'name' => 'صنف',
                'quantity' => 3, 'unitPrice' => 150.5, 'total' => 451.5,
            ]],
        ], raw: true);

        // الإكسيل بياخد أرقام خام يقدر يجمعها، مش نص فيه «ج.م»
        $this->assertSame(150.5, $table['rows'][0][3]);
        $this->assertIsFloat($table['rows'][0][4]);
    }

    public function test_pdf_keeps_numbers_formatted_with_the_currency(): void
    {
        $table = app(ExportBuilder::class)->invoice([
            'currency' => 'EGP',
            'items' => [[
                'sku' => 'A-1', 'name' => 'صنف',
                'quantity' => 3, 'unitPrice' => 150.5, 'total' => 451.5,
            ]],
        ], raw: false);

        $this->assertSame('150.50 ج.م', $table['rows'][0][3]);
    }

    public function test_the_csv_fallback_carries_a_bom(): void
    {
        // نفس المسار اللي بيشتغل لو ZipArchive مش موجودة
        $service = new class extends SpreadsheetService
        {
            public function available(): bool
            {
                return false;
            }
        };

        $response = $service->download('تقرير', ['الصنف', 'الكمية'], [['كبولي', 5]]);

        $body = $response->getContent();

        // من غير الـ BOM إكسيل بيقرا العربي رموز مكسّرة
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('كبولي', $body);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    // ── جداول الوكلاء ─────────────────────────────────────────────

    public function test_an_agent_table_can_be_downloaded(): void
    {
        $table = "الكود | الصنف | الرصيد\n"
            .str_repeat('—', 40)."\n"
            ."KB-25 | كبولي مجوز | 50\n"
            .'FR-01 | الفاروق فضي | 12';

        $response = $this->actingAs($this->admin)
            ->post('/ai/export-table', [
                'text' => $table,
                'title' => 'الأصناف الراكدة',
                'format' => 'excel',
            ])
            ->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->getContent());

        $zip = new ZipArchive;
        $zip->open($path);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');

        $this->assertStringContainsString('كبولي مجوز', $sheet);
        $this->assertStringContainsString('الفاروق فضي', $sheet);

        $zip->close();
        unlink($path);
    }

    public function test_an_agent_reply_without_a_table_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/ai/export-table', [
                'text' => 'مفيش أصناف تحت حد الطلب حاليًا.',
                'format' => 'pdf',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'الرد ده مفيهوش جدول ينفع يتحمّل.');
    }

    public function test_the_agent_table_parser_drops_the_divider(): void
    {
        $parsed = app(ExportBuilder::class)->parseAgentTable(
            "الكود | الصنف\n".str_repeat('—', 40)."\nA-1 | صنف"
        );

        $this->assertSame(['الكود', 'الصنف'], $parsed['headers']);
        $this->assertCount(1, $parsed['rows']);
        $this->assertSame(['A-1', 'صنف'], $parsed['rows'][0]);
    }

    // ── مساعدات ───────────────────────────────────────────────────

    /**
     * بيمسح تحليل الخط المخزّن عشان الرسم يقرا الملف من أوله.
     *
     * من غير كده الاختبار بيعدّي على جهاز فيه كاش دافي حتى لو الخط
     * نفسه مابيتقراش — وده بالظبط اللي خلّى عطل السيرفر يعدّي.
     */
    private function forgetFontCache(): void
    {
        foreach (glob(storage_path('app/mpdf/mpdf/ttfontdata/*')) ?: [] as $file) {
            @unlink($file);
        }
    }

    /** محتوى ورقة الإكسيل اللي الرابط ده بيرجّعها */
    private function sheetOf(string $url): string
    {
        $response = $this->actingAs($this->admin)->get($url)->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->getContent());

        $zip = new ZipArchive;
        $zip->open($path);
        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);

        return $sheet;
    }

    /**
     * أسماء الخطوط المحقونة جوه ملف PDF.
     *
     * @return array<int, string>
     */
    private function embeddedFonts(string $pdf): array
    {
        preg_match_all('#/BaseFont\s*/([A-Za-z0-9+\-,]+)#', $pdf, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * أسماء جداول ملف TTF.
     *
     * @return array<int, string>
     */
    private function fontTables(string $path): array
    {
        $handle = fopen($path, 'rb');
        $count = (int) (unpack('Nversion/ntables', (string) fread($handle, 12))['tables'] ?? 0);
        $tables = [];

        for ($i = 0; $i < $count; $i++) {
            $tables[] = trim((string) unpack('a4tag', (string) fread($handle, 16))['tag']);
        }

        fclose($handle);

        return $tables;
    }

    /** فاتورة شراء بعملة المورد — قالب مختلف عن فاتورة البيع */
    private function purchaseInvoice(): Invoice
    {
        $purchase = Invoice::create([
            'type' => Invoice::TYPE_PURCHASE,
            'number' => 'P-2026-00001',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->invoice->warehouse_id,
            'user_id' => $this->admin->id,
            'date' => now()->subDays(5)->toDateString(),
            'currency' => 'TRY',
            'exchange_rate' => 1.46,
            'status' => Invoice::STATUS_CONFIRMED,
            'subtotal' => 900,
            'total' => 900,
            'paid' => 0,
        ]);

        InvoiceItem::create([
            'invoice_id' => $purchase->id,
            'product_id' => $this->invoice->items()->value('product_id'),
            'quantity' => 9,
            'unit_price' => 100,
            'total' => 900,
        ]);

        return $purchase;
    }
}
