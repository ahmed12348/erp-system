<?php

namespace Tests\Feature\Erp;

use App\Mail\TestAlertMail;
use App\Models\AlertEmailRecipient;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\Erp\DailyStockDigest;
use App\Notifications\Erp\LowStockAlert;
use App\Services\StockAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * تنبيهات المخزون على الإيميل.
 *
 * الحتة الجديدة: عناوين بتستقبل التنبيهات من غير ما تكون مستخدمين —
 * صاحب المحل مش بالضرورة بيفتح النظام، وساعات المحاسب الخارجي عايز
 * يتابع من غير حساب.
 */
class AlertEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Warehouse $warehouse;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);

        $this->product = Product::create([
            'sku' => 'KB-25', 'name' => 'كبولي مجوز', 'unit' => 'جوز',
            'purchase_price' => 100, 'sale_price' => 150, 'min_stock' => 20,
        ]);

        // الإيميل متظبّط في الاختبارات عشان القناة تبقى متاحة —
        // الـ fake هو اللي بيمسك الإرسال، مفيش حاجة بتطلع بره
        config()->set('mail.default', 'smtp');
        config()->set('mail.from.address', 'noreply@usi-eg.com');
    }

    // ── الإيميل الافتراضي ─────────────────────────────────────────

    /** الهجرة بتزرع الإيميل الافتراضي عشان يشتغل على السيرفر على طول */
    public function test_the_default_recipient_is_seeded(): void
    {
        $recipient = AlertEmailRecipient::query()
            ->where('email', 'ahmedayad944@yahoo.com')
            ->first();

        $this->assertNotNull($recipient);
        $this->assertTrue($recipient->is_active);
    }

    // ── الإرسال ───────────────────────────────────────────────────

    /** التنبيه بيروح للإيميلات الزيادة زي ما بيروح للمستخدمين */
    public function test_a_low_stock_alert_reaches_the_extra_emails(): void
    {
        Notification::fake();

        $this->receive(50);
        $this->consume(40);   // الرصيد بقى 10، تحت حد الطلب 20

        Notification::assertSentOnDemand(
            LowStockAlert::class,
            fn (LowStockAlert $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === ['ahmedayad944@yahoo.com']
                && $channels === ['mail'],
        );
    }

    public function test_the_daily_digest_reaches_the_extra_emails(): void
    {
        Notification::fake();

        $this->receive(5);    // تحت الحد من أول لحظة

        app(StockAlertService::class)->sendDigest();

        Notification::assertSentOnDemand(DailyStockDigest::class);
    }

    /**
     * الملخّص بيوصل الإيميلات الزيادة حتى لو مفيش مستخدم مشترك فيه.
     *
     * الخروج بدري لما مفيش مستخدمين كان بيمنعهم من غير أي سبب — هما
     * مش مستخدمين أصلاً.
     */
    public function test_the_digest_still_mails_when_no_user_subscribes(): void
    {
        Notification::fake();

        // مفيش مستخدمين خالص
        User::query()->delete();

        $this->receive(5);

        app(StockAlertService::class)->sendDigest();

        Notification::assertSentOnDemand(DailyStockDigest::class);
    }

    public function test_a_deactivated_email_gets_nothing(): void
    {
        Notification::fake();

        AlertEmailRecipient::query()->update(['is_active' => false]);

        $this->receive(50);
        $this->consume(40);

        Notification::assertNotSentTo(new AnonymousNotifiable, LowStockAlert::class);
    }

    /** من غير إعدادات MAIL_ مفيش محاولة إرسال أصلاً */
    public function test_nothing_is_mailed_when_mail_is_not_configured(): void
    {
        Notification::fake();

        config()->set('mail.from.address', null);

        $this->receive(50);
        $this->consume(40);

        Notification::assertNotSentTo(new AnonymousNotifiable, LowStockAlert::class);
    }

    // ── محتوى الرسالة ─────────────────────────────────────────────

    /**
     * الرسالة فيها الصنف والمخزن والرصيد وحد الطلب ولينك الصنف.
     *
     * الرصيد الإجمالي لوحده مابيقولش فين الناقص — وعلى مخزنين، «باقي
     * ٣» ممكن تكون ٣ في مخزن وصفر في التاني.
     */
    public function test_the_email_carries_the_product_warehouse_stock_and_link(): void
    {
        Notification::fake();

        $this->receive(50);
        $this->consume(40);

        $mail = (new LowStockAlert($this->product, 10.0))
            ->toMail(new AnonymousNotifiable);

        $data = $mail->viewData;

        $this->assertSame('صنف قرب يخلص: كبولي مجوز', $data['title']);
        $this->assertStringContainsString('/inventory/'.$this->product->id, $data['actionUrl']);

        $lines = implode(' | ', $data['lines']);

        $this->assertStringContainsString('كبولي مجوز', $lines);
        $this->assertStringContainsString('حد الطلب', $lines);
        // المخزن بالاسم والرصيد اللي فيه
        $this->assertStringContainsString('مخزن المخزن الرئيسي', $lines);
        $this->assertStringContainsString('10 جوز', $lines);
    }

    /** المستقبل اللي مش مستخدم مالوش اسم — التحية بتعدّي من غيره */
    public function test_the_greeting_works_without_a_user(): void
    {
        $mail = (new LowStockAlert($this->product, 1.0))
            ->toMail(new AnonymousNotifiable);

        $this->assertSame('أهلاً', $mail->viewData['greeting']);
    }

    public function test_an_anonymous_recipient_only_gets_the_mail_channel(): void
    {
        $channels = (new LowStockAlert($this->product, 1.0))
            ->via(new AnonymousNotifiable);

        $this->assertSame(['mail'], $channels);
    }

    // ── الشاشة ────────────────────────────────────────────────────

    public function test_an_admin_sees_and_manages_the_list(): void
    {
        $this->actingAs($this->admin)
            ->get('/settings/notifications')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('alertEmails.manageable', true)
                ->has('alertEmails.recipients', 1)
            );

        $this->actingAs($this->admin)
            ->post('/settings/alert-emails', ['email' => 'owner@example.com', 'name' => 'المالك'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(2, AlertEmailRecipient::count());
    }

    public function test_a_duplicate_email_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post('/settings/alert-emails', ['email' => 'ahmedayad944@yahoo.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_an_invalid_email_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post('/settings/alert-emails', ['email' => 'مش إيميل'])
            ->assertSessionHasErrors('email');
    }

    public function test_an_email_can_be_paused_and_deleted(): void
    {
        $recipient = AlertEmailRecipient::sole();

        $this->actingAs($this->admin)
            ->put("/settings/alert-emails/{$recipient->id}", ['is_active' => false])
            ->assertRedirect();

        $this->assertFalse($recipient->fresh()->is_active);

        $this->actingAs($this->admin)
            ->delete("/settings/alert-emails/{$recipient->id}")
            ->assertRedirect();

        $this->assertSame(0, AlertEmailRecipient::count());
    }

    public function test_only_an_admin_can_manage_the_list(): void
    {
        foreach ([$this->accountant(), $this->salesUser()] as $user) {
            $this->actingAs($user)
                ->post('/settings/alert-emails', ['email' => 'x@example.com'])
                ->assertForbidden();
        }
    }

    // ── الإيميل التجريبي ──────────────────────────────────────────

    /** الزرار بيبعت فعلاً — مش بيحط في الطابور */
    public function test_the_test_email_is_sent_immediately(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)
            ->post('/settings/alert-emails/test', ['email' => 'check@example.com'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        Mail::assertSent(
            TestAlertMail::class,
            fn ($mail): bool => $mail->hasTo('check@example.com'),
        );
    }

    /** من غير MAIL_ بيقول السبب بدل ما يقول «اتبعت» وهو مااتبعتش */
    public function test_the_test_email_explains_when_mail_is_unconfigured(): void
    {
        Mail::fake();

        config()->set('mail.from.address', null);

        $this->actingAs($this->admin)
            ->post('/settings/alert-emails/test', ['email' => 'check@example.com'])
            ->assertSessionHasErrors('test_email');

        Mail::assertNothingSent();
    }

    public function test_only_an_admin_can_send_a_test(): void
    {
        $this->actingAs($this->accountant())
            ->post('/settings/alert-emails/test', ['email' => 'x@example.com'])
            ->assertForbidden();
    }

    // ── مساعدات ───────────────────────────────────────────────────

    private function receive(float $quantity): void
    {
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => $quantity,
            'unit_cost' => 100,
            'date' => Carbon::today()->subDays(2)->toDateString(),
        ]);

        app(StockAlertService::class)->check([$this->product->id]);
    }

    private function consume(float $quantity): void
    {
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => StockMovement::TYPE_OUT,
            'quantity' => -$quantity,
            'unit_cost' => 100,
            'date' => Carbon::today()->toDateString(),
        ]);

        app(StockAlertService::class)->check([$this->product->id]);
    }
}
