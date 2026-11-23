<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockAlertState;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\Erp\DailyStockDigest;
use App\Notifications\Erp\LowStockAlert;
use App\Services\NotificationPreferences;
use App\Services\StockAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * تنبيهات النواقص.
 *
 * أهم حاجة هنا: التنبيه بيتبعت **عند العبور** بس. لو اتبعت مع كل
 * بيعة، صاحب المحل هيقفل التنبيهات كلها وهي أول أسبوع.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Warehouse $warehouse;

    private Product $product;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $category = Category::create(['name' => 'بقالة']);

        $this->customer = Customer::create([
            'name' => 'وليد عبد العظيم',
            'price_tier' => 'sale',
        ]);

        $this->product = Product::create([
            'sku' => 'KB-25', 'name' => 'كبولي مجوز', 'category_id' => $category->id,
            'unit' => 'كرتونة', 'purchase_price' => 100, 'sale_price' => 150,
            'min_stock' => 10,
        ]);

        $this->stock(50);
    }

    // ── العبور ────────────────────────────────────────────────────

    public function test_crossing_below_the_minimum_sends_once(): void
    {
        Notification::fake();

        // 50 → 5، يعني نزل تحت الحد (10)
        $this->sell(45);

        Notification::assertSentTo(
            $this->admin,
            LowStockAlert::class,
            fn (LowStockAlert $alert): bool => $alert->product->is($this->product)
                && ! $alert->outOfStock,
        );
    }

    /** ده بيت القصيد: البيعة التانية لنفس الصنف الناقص ما تبعتش تاني */
    public function test_selling_again_while_still_low_does_not_resend(): void
    {
        Notification::fake();

        $this->sell(45);
        Notification::assertSentTimes(LowStockAlert::class, 1);

        // لسه تحت الحد — مفيش تنبيه جديد
        $this->sell(2);
        Notification::assertSentTimes(LowStockAlert::class, 1);
    }

    public function test_hitting_zero_sends_an_out_of_stock_alert(): void
    {
        Notification::fake();

        $this->sell(50);

        Notification::assertSentTo(
            $this->admin,
            LowStockAlert::class,
            fn (LowStockAlert $alert): bool => $alert->outOfStock,
        );
    }

    /** الصنف اللي خلص مش محتاج رسالتين — «خلص» بتكسب على «قرب يخلص» */
    public function test_zero_does_not_also_send_a_low_stock_alert(): void
    {
        Notification::fake();

        $this->sell(50);

        Notification::assertSentTimes(LowStockAlert::class, 1);
    }

    public function test_restocking_then_dropping_again_sends_a_new_alert(): void
    {
        Notification::fake();

        $this->sell(45);
        Notification::assertSentTimes(LowStockAlert::class, 1);

        // اتعبّى فوق الحد
        $this->stock(40);
        app(StockAlertService::class)->check([$this->product->id]);

        // ونزل تاني
        $this->sell(40);

        Notification::assertSentTimes(LowStockAlert::class, 2);
    }

    public function test_a_product_above_its_minimum_sends_nothing(): void
    {
        Notification::fake();

        $this->sell(5);

        Notification::assertNothingSent();
    }

    public function test_the_alert_state_is_recorded(): void
    {
        $this->sell(45);

        $state = StockAlertState::where('product_id', $this->product->id)->sole();

        $this->assertTrue($state->below_minimum);
        $this->assertFalse($state->out_of_stock);
        $this->assertNotNull($state->notified_at);
    }

    // ── الملخّص اليومي ────────────────────────────────────────────

    public function test_the_daily_digest_gathers_everything_low(): void
    {
        Notification::fake();

        $this->sell(45);

        $this->artisan('erp:stock-digest')->assertSuccessful();

        Notification::assertSentTo(
            $this->admin,
            DailyStockDigest::class,
            fn (DailyStockDigest $digest): bool => $digest->products->count() === 1,
        );
    }

    public function test_the_digest_sends_nothing_when_stock_is_healthy(): void
    {
        Notification::fake();

        $this->artisan('erp:stock-digest')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_the_digest_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('erp:stock-digest');
    }

    // ── المستقبلين والتفضيلات ─────────────────────────────────────

    public function test_admins_and_accountants_receive_by_default(): void
    {
        $accountant = $this->accountant();
        $sales = $this->salesUser();

        $preferences = app(NotificationPreferences::class);

        $this->assertNotEmpty($preferences->channelsFor($this->admin, 'low_stock'));
        $this->assertNotEmpty($preferences->channelsFor($accountant, 'low_stock'));
        // المندوب مش من المستقبلين افتراضيًا
        $this->assertSame([], $preferences->channelsFor($sales, 'low_stock'));
    }

    public function test_a_user_can_turn_a_channel_off(): void
    {
        Notification::fake();

        app(NotificationPreferences::class)->save($this->admin, [
            'low_stock' => ['database' => true, 'mail' => false],
        ]);

        $channels = app(NotificationPreferences::class)
            ->channelsFor($this->admin->fresh(), 'low_stock');

        $this->assertSame(['database'], $channels);
    }

    public function test_an_unconfigured_channel_is_never_offered(): void
    {
        config()->set('notifications.whatsapp.token', null);

        app(NotificationPreferences::class)->save($this->admin, [
            'low_stock' => ['database' => true, 'whatsapp' => true],
        ]);

        $channels = app(NotificationPreferences::class)
            ->channelsFor($this->admin->fresh(), 'low_stock');

        // المستخدم فعّلها، بس السيرفر مش جاهز — فما بتتحاولش
        $this->assertNotContains('whatsapp', $channels);
    }

    public function test_the_settings_screen_shows_why_a_channel_is_off(): void
    {
        config()->set('notifications.whatsapp.token', null);

        $this->actingAs($this->admin)
            ->get('/settings/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Notifications')
                ->where('matrix.0.channels.3.available', false)
                ->where('matrix.0.channels.3.reason', 'محتاج مفاتيح واتساب في إعدادات السيرفر.')
                ->etc()
            );
    }

    public function test_settings_can_be_saved(): void
    {
        $this->actingAs($this->admin)
            ->put('/settings/notifications', [
                'channels' => ['low_stock' => ['database' => true, 'mail' => false]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $this->admin->id,
        ]);
    }

    // ── الجرس ─────────────────────────────────────────────────────

    public function test_the_bell_lists_unread_first(): void
    {
        $this->sell(45);

        $this->actingAs($this->admin)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('notifications.0.read', false)
            ->assertJsonPath('notifications.0.type', 'low_stock')
            ->assertJsonPath('notifications.0.url', '/inventory/'.$this->product->id);
    }

    public function test_a_notification_can_be_marked_read(): void
    {
        $this->sell(45);

        $id = $this->admin->notifications()->sole()->id;

        $this->actingAs($this->admin)
            ->postJson('/notifications/read', ['id' => $id])
            ->assertOk()
            ->assertJsonPath('unread', 0);
    }

    public function test_all_notifications_can_be_marked_read(): void
    {
        $this->sell(45);

        $this->actingAs($this->admin)
            ->postJson('/notifications/read', [])
            ->assertOk()
            ->assertJsonPath('unread', 0);
    }

    public function test_a_user_only_sees_their_own_notifications(): void
    {
        $this->sell(45);

        // المندوب مش من المستقبلين، فجرسه فاضي
        $this->actingAs($this->salesUser())
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonPath('unread', 0)
            ->assertJsonCount(0, 'notifications');
    }

    // ── Web Push ──────────────────────────────────────────────────

    public function test_a_browser_can_subscribe_and_unsubscribe(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/notifications/push', [
                'endpoint' => 'https://push.example/abc',
                'keys' => ['p256dh' => 'key-1', 'auth' => 'auth-1'],
            ])
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->admin->id,
            'endpoint' => 'https://push.example/abc',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson('/notifications/push', ['endpoint' => 'https://push.example/abc'])
            ->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_resubscribing_the_same_browser_does_not_duplicate(): void
    {
        foreach ([1, 2] as $_) {
            $this->actingAs($this->admin)->postJson('/notifications/push', [
                'endpoint' => 'https://push.example/same',
                'keys' => ['p256dh' => 'key', 'auth' => 'auth'],
            ]);
        }

        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    // ── واتساب ────────────────────────────────────────────────────

    public function test_whatsapp_only_routes_to_a_verified_number(): void
    {
        $this->admin->forceFill(['whatsapp_number' => '201001234567'])->save();

        // مكتوب بس متأكّدش منه — ما يتبعتلوش
        $this->assertNull($this->admin->routeNotificationForWhatsApp());

        $this->admin->forceFill(['whatsapp_verified_at' => now()])->save();

        $this->assertSame('201001234567', $this->admin->fresh()->routeNotificationForWhatsApp());
    }

    // ── مساعدات ───────────────────────────────────────────────────

    private function stock(float $quantity): void
    {
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => $quantity,
            'unit_cost' => 100,
            'date' => now()->toDateString(),
        ]);
    }

    /** بيعة حقيقية بالمسار العادي، عشان الهوك يشتغل زي الإنتاج */
    private function sell(float $quantity): void
    {
        $this->actingAs($this->admin)->post('/sales', [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'date' => now()->toDateString(),
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => $quantity,
                'unit_price' => 150,
            ]],
        ])->assertRedirect();
    }
}
