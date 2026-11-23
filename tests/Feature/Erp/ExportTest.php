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

    // ── PDF ───────────────────────────────────────────────────────

    public function test_a_report_downloads_as_a_real_pdf(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/exports/report?report=period')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $pdf = $response->getContent();

        // ملف PDF حقيقي وفيه محتوى
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(5000, strlen($pdf));

        /*
         * الخط المتضمّن اتقرا فعلاً بجداول التشكيل العربي بتاعته.
         * اسم الخط نفسه جوه مجاري مضغوطة في الـ PDF فمش بيتدوّر عليه
         * كنص، لكن وجود كاش GSUB العربي بتاع Cairo بيثبت إن mPDF قرا
         * الخط بتاعنا واشتغل بيه — مش بخط افتراضي من السيرفر.
         */
        $this->assertFileExists(
            storage_path('app/mpdf/mpdf/ttfontdata/cairo.GSUB.arab.DFLT.json'),
        );
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
}
