<?php

namespace Tests\Feature\Erp;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PreparationOrder;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * مشاركة المستندات على واتساب.
 *
 * فتح شات `wa.me` كله في المتصفح ومش بيعدّي على السيرفر. اللي بيتختبر
 * هنا هو اللي بيعدّي: الإرسال المباشر بالـ Cloud API، حفظ رقم التاجر،
 * الرابط الموقّع، وتفضيل «فتح واتساب على».
 */
class WhatsAppShareTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Warehouse $warehouse;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();

        $this->customer = Customer::create([
            'name' => 'محمد عبد الله',
            'phone' => '01012345678',
        ]);

        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);

        $this->product = Product::create([
            'sku' => 'HA-9001', 'name' => 'خلاط كهربائي', 'unit' => 'قطعة',
            'purchase_price' => 100, 'sale_price' => 180, 'min_stock' => 5,
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

    /** مفاتيح الـ Cloud API متظبّطة */
    private function enableApi(): void
    {
        config()->set('notifications.whatsapp.token', 'test-token');
        config()->set('notifications.whatsapp.phone_number_id', '100200300');
    }

    // ── الرابط الموقّع ────────────────────────────────────────────

    /** صفحة الفاتورة بتدّي رابط PDF موقّع — ده اللي بيروح في الرسالة */
    public function test_the_invoice_page_carries_a_signed_pdf_link(): void
    {
        $invoice = $this->sale();

        $this->actingAs($this->admin)
            ->get("/sales/{$invoice->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pdfUrl', fn (string $url): bool => str_contains($url, 'signature='))
            );
    }

    /** اللينك الموقّع بيفتح من غير تسجيل دخول — ده بيت القصيد */
    public function test_a_signed_invoice_link_opens_without_logging_in(): void
    {
        $invoice = $this->sale();

        $this->get($this->signedInvoiceUrl($invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_an_unsigned_invoice_link_is_refused(): void
    {
        $invoice = $this->sale();

        $this->get("/invoices/{$invoice->id}/pdf")->assertForbidden();
    }

    /** المسودّة لسه مش مستند — مفيش لينك ليها */
    public function test_a_draft_has_no_pdf(): void
    {
        $this->get($this->signedInvoiceUrl($this->draft('DRAFT-1')))
            ->assertNotFound();
    }

    // ── الإرسال المباشر ──────────────────────────────────────────

    /** الزرار مخفي لما المفاتيح مش متظبّطة، والراوت بيرفض كمان */
    public function test_direct_send_is_hidden_and_refused_without_keys(): void
    {
        config()->set('notifications.whatsapp.token', null);

        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('whatsapp.direct', false)
            );

        $invoice = $this->sale();

        $this->actingAs($this->admin)
            ->post('/whatsapp/send', ['type' => 'invoice', 'id' => $invoice->id])
            ->assertNotFound();
    }

    public function test_it_sends_an_invoice_to_the_traders_number(): void
    {
        $this->enableApi();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        $invoice = $this->sale();

        $this->actingAs($this->admin)
            ->post('/whatsapp/send', ['type' => 'invoice', 'id' => $invoice->id])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            // الرقم بيتبعت دولي — `01012345678` مابيوصلش حد
            return $body['to'] === '201012345678'
                && $body['type'] === 'template'
                && $body['template']['name'] === 'document_share';
        });
    }

    /** الرسالة قالب معتمد بمتغيّراته: الشركة، المستند، لينك الـ PDF */
    public function test_the_template_carries_the_document_and_its_link(): void
    {
        $this->enableApi();
        Http::fake(['graph.facebook.com/*' => Http::response([])]);

        $invoice = $this->sale();

        $this->actingAs($this->admin)
            ->post('/whatsapp/send', ['type' => 'invoice', 'id' => $invoice->id]);

        Http::assertSent(function ($request) use ($invoice): bool {
            $values = array_column(
                $request->data()['template']['components'][0]['parameters'],
                'text',
            );

            return $values[0] === config('app.name')
                && $values[1] === 'فاتورة بيع '.$invoice->number
                && str_contains($values[2], 'signature=');
        });
    }

    public function test_it_sends_a_preparation_order(): void
    {
        $this->enableApi();
        Http::fake(['graph.facebook.com/*' => Http::response([])]);

        $order = $this->order();

        $this->actingAs($this->admin)
            ->post('/whatsapp/send', ['type' => 'preparation-order', 'id' => $order->id])
            ->assertSessionHasNoErrors();

        Http::assertSent(fn ($request): bool => $request->data()['to'] === '201012345678');
    }

    /** الرقم المكتوب بيكسب على المسجّل — المستخدم كتبه لسبب */
    public function test_a_typed_number_wins_over_the_stored_one(): void
    {
        $this->enableApi();
        Http::fake(['graph.facebook.com/*' => Http::response([])]);

        $invoice = $this->sale();

        $this->actingAs($this->admin)->post('/whatsapp/send', [
            'type' => 'invoice',
            'id' => $invoice->id,
            'phone' => '+20 100 999 8877',
        ]);

        Http::assertSent(fn ($request): bool => $request->data()['to'] === '201009998877');
    }

    public function test_it_refuses_when_there_is_no_usable_number(): void
    {
        $this->enableApi();
        Http::fake();

        $this->customer->update(['phone' => null]);
        $invoice = $this->sale();

        $this->actingAs($this->admin)
            ->post('/whatsapp/send', ['type' => 'invoice', 'id' => $invoice->id])
            ->assertSessionHasErrors('whatsapp');

        Http::assertNothingSent();
    }

    /** واتساب رفض = رسالة بالعربي، مش «اتبعت» كدب */
    public function test_a_refusal_from_whatsapp_comes_back_as_an_error(): void
    {
        $this->enableApi();
        Http::fake([
            'graph.facebook.com/*' => Http::response(
                ['error' => ['message' => 'Template name does not exist']],
                400,
            ),
        ]);

        $invoice = $this->sale();

        $this->actingAs($this->admin)
            ->post('/whatsapp/send', ['type' => 'invoice', 'id' => $invoice->id])
            ->assertSessionHasErrors('whatsapp');

        $this->assertSame(0, Activity::where('event', 'whatsapp_sent')->count());
    }

    public function test_a_successful_send_is_logged(): void
    {
        $this->enableApi();
        Http::fake(['graph.facebook.com/*' => Http::response([])]);

        $invoice = $this->sale();

        $this->actingAs($this->admin)
            ->post('/whatsapp/send', ['type' => 'invoice', 'id' => $invoice->id]);

        $activity = Activity::where('event', 'whatsapp_sent')->firstOrFail();

        $this->assertSame($invoice->id, $activity->subject_id);
        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('201012345678', $activity->properties['to']);
    }

    public function test_a_draft_cannot_be_sent(): void
    {
        $this->enableApi();
        Http::fake();

        $draft = $this->draft('DRAFT-2');

        $this->actingAs($this->admin)
            ->post('/whatsapp/send', ['type' => 'invoice', 'id' => $draft->id])
            ->assertNotFound();
    }

    public function test_a_guest_cannot_send(): void
    {
        $this->enableApi();

        $this->post('/whatsapp/send', ['type' => 'invoice', 'id' => 1])
            ->assertRedirect('/login');
    }

    // ── حفظ رقم الطرف ────────────────────────────────────────────

    public function test_a_phone_can_be_saved_on_the_trader(): void
    {
        $this->customer->update(['phone' => null]);

        $this->actingAs($this->admin)
            ->post('/whatsapp/phone', [
                'party' => 'customer',
                'id' => $this->customer->id,
                'phone' => '0100 999 8877',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // بيتحفظ بشكله المحلي زي ما المستخدم كتبه — مش دولي
        $this->assertSame('0100 999 8877', $this->customer->fresh()->phone);
    }

    public function test_a_phone_can_be_saved_on_a_supplier(): void
    {
        $supplier = Supplier::create(['name' => 'شركة النور']);

        $this->actingAs($this->admin)
            ->post('/whatsapp/phone', [
                'party' => 'supplier',
                'id' => $supplier->id,
                'phone' => '01112223344',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('01112223344', $supplier->fresh()->phone);
    }

    public function test_a_nonsense_phone_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post('/whatsapp/phone', [
                'party' => 'customer',
                'id' => $this->customer->id,
                'phone' => '0101234',
            ])
            ->assertSessionHasErrors('phone');
    }

    /** حفظ بيانات التاجر صلاحية — المبيعات بتبعت بس مش بتعدّل الدفتر */
    public function test_sales_cannot_save_a_phone(): void
    {
        $this->actingAs($this->salesUser())
            ->post('/whatsapp/phone', [
                'party' => 'customer',
                'id' => $this->customer->id,
                'phone' => '01112223344',
            ])
            ->assertForbidden();
    }

    // ── «فتح واتساب على» ─────────────────────────────────────────

    public function test_the_default_target_is_the_app(): void
    {
        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('whatsapp.target', 'app')
            );
    }

    public function test_a_user_can_switch_to_whatsapp_web(): void
    {
        $this->actingAs($this->admin)
            ->put('/settings/whatsapp-target', ['target' => 'web'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('web', $this->admin->fresh()->whatsapp_target);

        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('whatsapp.target', 'web')
            );
    }

    /** التفضيل شخصي — تغيير المدير مابيأثرش على حد تاني */
    public function test_the_target_is_per_user(): void
    {
        $sales = $this->salesUser();

        $this->actingAs($this->admin)->put('/settings/whatsapp-target', ['target' => 'web']);

        $this->assertSame('app', $sales->fresh()->whatsapp_target);
    }

    public function test_an_unknown_target_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->put('/settings/whatsapp-target', ['target' => 'telegram'])
            ->assertSessionHasErrors('target');
    }

    public function test_the_settings_screen_shows_the_current_choice(): void
    {
        $this->enableApi();
        $this->admin->update(['whatsapp_target' => 'web']);

        $this->actingAs($this->admin)
            ->get('/settings/notifications')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('whatsappShare.target', 'web')
                ->where('whatsappShare.directAvailable', true)
            );
    }

    // ── مساعدات ──────────────────────────────────────────────────

    private function sale(): Invoice
    {
        $this->actingAs($this->admin)->post('/sales', [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => now()->toDateString(),
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 2,
                'unit_price' => 180,
                'discount' => 0,
            ]],
        ])->assertRedirect();

        return Invoice::query()->sales()->latest('id')->firstOrFail();
    }

    private function order(): PreparationOrder
    {
        $this->actingAs($this->admin)->post('/preparation-orders', [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => now()->toDateString(),
            'items' => [['product_id' => $this->product->id, 'quantity' => 3]],
        ])->assertRedirect();

        return PreparationOrder::latest('id')->firstOrFail();
    }

    /** مسودّة فاتورة — بتتكتب على الجدول مباشرة، مفيش راوت بيعملها */
    private function draft(string $number): Invoice
    {
        return Invoice::create([
            'type' => Invoice::TYPE_SALE,
            'number' => $number,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'date' => now()->toDateString(),
            'status' => Invoice::STATUS_DRAFT,
            'currency' => 'EGP', 'exchange_rate' => 1,
            'subtotal' => 0, 'total' => 0, 'paid' => 0,
        ]);
    }

    private function signedInvoiceUrl(Invoice $invoice): string
    {
        return URL::temporarySignedRoute(
            'invoices.pdf',
            now()->addWeek(),
            ['invoice' => $invoice->id],
        );
    }
}
