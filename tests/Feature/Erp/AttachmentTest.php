<?php

namespace Tests\Feature\Erp;

use App\Models\Attachment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'مخزن الاختبار']);
        $customer = Customer::create([
            'name' => 'تاجر الاختبار',
            'price_tier' => 'retail',
        ]);

        $this->invoice = Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => 'S-TEST-1',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'subtotal' => 100,
            'total' => 100,
            'paid' => 0,
            'status' => Invoice::STATUS_CONFIRMED,
        ]);
    }

    public function test_an_image_can_be_attached_to_an_invoice(): void
    {
        $response = $this->actingAs($this->user)->post(
            "/invoices/{$this->invoice->id}/attachments",
            ['file' => UploadedFile::fake()->create('delivery.jpg', 40, 'image/jpeg'), 'notes' => 'إذن تسليم'],
        );

        $response->assertRedirect();

        $attachment = Attachment::sole();

        $this->assertSame('delivery.jpg', $attachment->original_name);
        $this->assertSame('إذن تسليم', $attachment->notes);
        $this->assertSame($this->user->id, $attachment->user_id);
        $this->assertTrue($attachment->isImage());
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_a_pdf_can_be_attached_to_a_payment(): void
    {
        $payment = Payment::create([
            'type' => Payment::TYPE_RECEIPT,
            'customer_id' => $this->invoice->customer_id,
            'amount' => 50,
            'currency' => 'EGP',
            'exchange_rate' => 1,
            'method' => 'bank',
            'date' => now()->toDateString(),
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)->post(
            "/payments/{$payment->id}/attachments",
            ['file' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf')],
        )->assertRedirect();

        $this->assertSame(1, $payment->attachments()->count());
        $this->assertTrue(Attachment::sole()->isPdf());
    }

    /** أنواع تانية (مثلاً exe أو docx) مرفوضة برسالة عربية */
    public function test_an_unsupported_file_type_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->from("/sales/{$this->invoice->id}")
            ->post("/invoices/{$this->invoice->id}/attachments", [
                'file' => UploadedFile::fake()->create('notes.docx', 20),
            ])
            ->assertSessionHasErrors(['file' => 'الملف لازم يكون صورة (JPG أو PNG أو WEBP) أو ملف PDF.']);

        $this->assertSame(0, Attachment::count());
    }

    public function test_a_file_over_eight_megabytes_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->from("/sales/{$this->invoice->id}")
            ->post("/invoices/{$this->invoice->id}/attachments", [
                'file' => UploadedFile::fake()->create('huge.pdf', 9000, 'application/pdf'),
            ])
            ->assertSessionHasErrors(['file' => 'حجم الملف ماينفعش يزيد عن 8 ميجابايت.']);

        $this->assertSame(0, Attachment::count());
    }

    public function test_the_invoice_page_lists_its_attachments(): void
    {
        $this->actingAs($this->user)->post(
            "/invoices/{$this->invoice->id}/attachments",
            ['file' => UploadedFile::fake()->create('scan.png', 40, 'image/png')],
        );

        $this->actingAs($this->user)
            ->get("/sales/{$this->invoice->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('sales/Show')
                ->has('invoice.attachments', 1)
                ->where('invoice.attachments.0.name', 'scan.png')
                ->where('invoice.attachments.0.isImage', true)
            );
    }

    public function test_an_attachment_can_be_previewed_and_downloaded(): void
    {
        $this->actingAs($this->user)->post(
            "/invoices/{$this->invoice->id}/attachments",
            ['file' => UploadedFile::fake()->create('scan.png', 40, 'image/png')],
        );

        $attachment = Attachment::sole();

        $this->actingAs($this->user)
            ->get("/attachments/{$attachment->id}")
            ->assertOk()
            ->assertHeader('content-disposition', 'inline');

        $this->actingAs($this->user)
            ->get("/attachments/{$attachment->id}/download")
            ->assertOk()
            ->assertDownload('scan.png');
    }

    /** الحذف بيشيل السطر والملف مع بعض */
    public function test_deleting_an_attachment_removes_the_stored_file(): void
    {
        $this->actingAs($this->user)->post(
            "/invoices/{$this->invoice->id}/attachments",
            ['file' => UploadedFile::fake()->create('scan.png', 40, 'image/png')],
        );

        $attachment = Attachment::sole();
        $path = $attachment->path;

        $this->actingAs($this->user)
            ->from("/sales/{$this->invoice->id}")
            ->delete("/attachments/{$attachment->id}")
            ->assertRedirect("/sales/{$this->invoice->id}");

        $this->assertSame(0, Attachment::count());
        Storage::disk('local')->assertMissing($path);
    }

    /** الملفات على قرص خاص — الضيف ما يوصلش لصورة فاتورة بلينك مباشر */
    public function test_guests_cannot_reach_attachments(): void
    {
        // بننشئ المرفق على طول من غير actingAs عشان الجلسة تفضل لضيف
        Storage::disk('local')->put('attachments/invoice/1/scan.png', 'x');

        $attachment = Attachment::create([
            'attachable_type' => $this->invoice->getMorphClass(),
            'attachable_id' => $this->invoice->id,
            'disk' => 'local',
            'path' => 'attachments/invoice/1/scan.png',
            'original_name' => 'scan.png',
            'mime_type' => 'image/png',
            'size' => 1,
            'user_id' => $this->user->id,
        ]);

        $this->post("/invoices/{$this->invoice->id}/attachments")->assertRedirect('/login');
        $this->get("/attachments/{$attachment->id}")->assertRedirect('/login');
        $this->get("/attachments/{$attachment->id}/download")->assertRedirect('/login');
        $this->delete("/attachments/{$attachment->id}")->assertRedirect('/login');
    }
}
