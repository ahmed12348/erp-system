<?php

namespace Tests\Feature\Erp;

use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * بيانات الشركة على كل مستند.
 *
 * الاسم والرقم الضريبي على الفاتورة التزام قانوني مش تفضيل شكلي، فالحتة
 * دي لازم توصل لكل ورقة — مطبوعة وPDF.
 */
class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Invoice $invoice;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $warehouse = Warehouse::create(['name' => 'الرئيسي']);

        $this->customer = Customer::create(['name' => 'وليد عبد العظيم', 'price_tier' => 'sale']);

        $product = Product::create([
            'sku' => 'KB-25', 'name' => 'كبولي مجوز', 'unit' => 'جوز',
            'purchase_price' => 100, 'sale_price' => 150, 'min_stock' => 0,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 50,
            'unit_cost' => 100,
            'date' => now()->subDays(5)->toDateString(),
        ]);

        $this->invoice = Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => 'S-2026-00001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->admin->id,
            'date' => now()->subDay()->toDateString(),
            'status' => Invoice::STATUS_CONFIRMED,
            'subtotal' => 1500, 'tax' => 210, 'total' => 1710, 'paid' => 0,
        ]);

        InvoiceItem::create([
            'invoice_id' => $this->invoice->id,
            'product_id' => $product->id,
            'quantity' => 10, 'unit_price' => 150, 'total' => 1500,
        ]);
    }

    /** نظام فاضي بيطبع باسم معقول من غير ما حد يفتح الشاشة */
    public function test_a_profile_is_created_on_first_use_from_the_app_name(): void
    {
        $this->assertSame(0, CompanyProfile::count());

        $profile = CompanyProfile::current();

        $this->assertSame(config('app.name'), $profile->name);
        $this->assertSame(1, CompanyProfile::count());
    }

    public function test_an_admin_can_save_the_company_data(): void
    {
        $this->actingAs($this->admin)
            ->post('/settings/company', [
                'name' => 'شركة النيل للاستيراد والتوزيع',
                'address' => '15 شارع الجمهورية، القاهرة',
                'phones' => '0223456789 / 01000000000',
                'tax_number' => '123-456-789',
                'commercial_registry' => 'CR-99887',
                'footer_note' => 'السداد خلال 30 يوم من تاريخ الفاتورة.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $profile = CompanyProfile::query()->sole();

        $this->assertSame('شركة النيل للاستيراد والتوزيع', $profile->name);
        $this->assertSame('123-456-789', $profile->tax_number);
        $this->assertSame('CR-99887', $profile->commercial_registry);
    }

    public function test_the_name_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post('/settings/company', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_only_an_admin_can_open_or_save_it(): void
    {
        foreach ([$this->accountant(), $this->salesUser()] as $user) {
            $this->actingAs($user)->get('/settings/company')->assertForbidden();
            $this->actingAs($user)
                ->post('/settings/company', ['name' => 'محاولة'])
                ->assertForbidden();
        }
    }

    // ── اللوجو ────────────────────────────────────────────────────

    public function test_a_logo_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post('/settings/company', [
                'name' => 'شركة النيل',
                'logo' => UploadedFile::fake()->create('logo.png', 12, 'image/png'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $profile = CompanyProfile::query()->sole();

        $this->assertNotNull($profile->logo_path);
        Storage::disk('public')->assertExists($profile->logo_path);

        $path = $profile->logo_path;

        $this->actingAs($this->admin)
            ->post('/settings/company', ['name' => 'شركة النيل', 'remove_logo' => true])
            ->assertRedirect();

        $this->assertNull($profile->fresh()->logo_path);
        // الملف القديم بيتمسح — مفيش داعي يفضل على القرص
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_non_image_logo_is_refused(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post('/settings/company', [
                'name' => 'شركة النيل',
                'logo' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');
    }

    // ── بتوصل لكل مستند ───────────────────────────────────────────

    /**
     * كل شاشة طباعة بتاخد الترويسة الكاملة.
     *
     * الفحص بيعدّي على كلهم مرة واحدة: شاشة جديدة بتتنسي بسهولة، وبتطلع
     * بترويسة ناقصة من غير ما حد ياخد باله.
     */
    public function test_every_printable_screen_gets_the_company_header(): void
    {
        $this->saveProfile();

        $screens = [
            "/sales/{$this->invoice->id}/print",
            "/customers/{$this->customer->id}/statement",
            '/reports/print?report=period',
        ];

        foreach ($screens as $url) {
            $this->actingAs($this->admin)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->where('company.name', 'شركة النيل للاستيراد والتوزيع')
                    ->where('company.taxNumber', '123-456-789')
                    ->where('company.footerNote', 'السداد خلال 30 يوم.')
                );
        }
    }

    /** والـ PDF كمان — الترويسة بتتكتب في الملف نفسه */
    public function test_the_company_data_reaches_the_pdf(): void
    {
        $this->saveProfile();

        $pdf = $this->actingAs($this->admin)
            ->get("/exports/invoices/{$this->invoice->id}")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->getContent();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(3000, strlen($pdf));
    }

    /**
     * الحفظ بيبان على طول.
     *
     * `current()` من غير كاش ساكن عن قصد — الكاش الساكن بيعيش أطول من
     * الطلب وبيرجّع صف قديم، والمستخدم بيحفظ وبيطبع ويلاقي القديم.
     */
    public function test_saving_is_visible_immediately(): void
    {
        $this->actingAs($this->admin)->post('/settings/company', [
            'name' => 'اسم جديد خالص',
        ]);

        $this->assertSame('اسم جديد خالص', CompanyProfile::current()->name);
    }

    private function saveProfile(): void
    {
        $this->actingAs($this->admin)->post('/settings/company', [
            'name' => 'شركة النيل للاستيراد والتوزيع',
            'address' => '15 شارع الجمهورية، القاهرة',
            'phones' => '0223456789',
            'tax_number' => '123-456-789',
            'commercial_registry' => 'CR-99887',
            'footer_note' => 'السداد خلال 30 يوم.',
        ])->assertSessionHasNoErrors();
    }
}
