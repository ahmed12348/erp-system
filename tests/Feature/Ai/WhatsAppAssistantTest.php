<?php

namespace Tests\Feature\Ai;

use App\Ai\AgentRegistry;
use App\Jobs\AnswerWhatsAppMessage;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AiService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\Text\Request as TextRequest;
use Tests\Support\FailingPrism;
use Tests\TestCase;

/**
 * «اسأل السيستم» من على واتساب.
 *
 * أهم حاجة هنا الحارس: رقم مش مربوط بحساب متأكّد مابياخدش بيانات —
 * ولا حتى رد يأكّد له إن في نظام هنا.
 */
class WhatsAppAssistantTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('prism.providers.gemini.api_key', 'test-key');
        config()->set('ai.enabled', true);
        config()->set('ai.provider', 'gemini');

        // القناة مفتوحة في الاختبارات
        config()->set('notifications.whatsapp.token', 'test-token');
        config()->set('notifications.whatsapp.phone_number_id', '123456');
        config()->set('notifications.whatsapp.verify_token', 'secret-verify');

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        $this->admin = $this->admin();
        $this->admin->forceFill([
            'whatsapp_number' => '201001234567',
            'whatsapp_verified_at' => now(),
        ])->save();

        $warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $category = Category::create(['name' => 'بقالة']);

        $product = Product::create([
            'sku' => 'KB-25', 'name' => 'كبولي مجوز', 'category_id' => $category->id,
            'unit' => 'كرتونة', 'purchase_price' => 100, 'sale_price' => 150, 'min_stock' => 50,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 10,
            'unit_cost' => 100,
            'date' => now()->toDateString(),
        ]);

        RateLimiter::clear('whatsapp:'.$this->admin->id);
    }

    // ── تأكيد الاشتراك ────────────────────────────────────────────

    public function test_meta_can_verify_the_webhook(): void
    {
        $this->get('/ai/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=secret-verify&hub_challenge=12345')
            ->assertOk()
            ->assertSee('12345');
    }

    public function test_a_wrong_verify_token_is_refused(): void
    {
        $this->get('/ai/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=12345')
            ->assertForbidden();
    }

    // ── الحارس ────────────────────────────────────────────────────

    /** ده بيت القصيد: رقم مش معروف مابياخدش حاجة */
    public function test_an_unlinked_number_gets_no_answer(): void
    {
        Bus::fake();

        $this->postJson('/ai/whatsapp/webhook', $this->payload('201119999999', 'عندي كام صنف؟'))
            ->assertOk();

        Bus::assertNothingDispatched();

        // ولا حتى رسالة رفض — مفيش أي نداء لواتساب
        Http::assertNothingSent();
    }

    public function test_an_unverified_number_gets_no_answer(): void
    {
        Bus::fake();

        $this->admin->forceFill(['whatsapp_verified_at' => null])->save();

        $this->postJson('/ai/whatsapp/webhook', $this->payload('201001234567', 'سؤال'))
            ->assertOk();

        Bus::assertNothingDispatched();
    }

    public function test_a_linked_number_gets_an_answer(): void
    {
        Bus::fake();

        $this->postJson('/ai/whatsapp/webhook', $this->payload('201001234567', 'إيه الناقص؟'))
            ->assertOk();

        Bus::assertDispatched(AnswerWhatsAppMessage::class);
    }

    public function test_the_webhook_always_returns_ok(): void
    {
        // Meta بتعيد الإرسال على أي كود غير 200
        $this->postJson('/ai/whatsapp/webhook', ['entry' => []])->assertOk();
        $this->postJson('/ai/whatsapp/webhook', [])->assertOk();
        $this->postJson('/ai/whatsapp/webhook', $this->payload('2010', ''))->assertOk();
    }

    public function test_non_text_messages_are_ignored(): void
    {
        Bus::fake();

        $this->postJson('/ai/whatsapp/webhook', [
            'entry' => [['changes' => [['value' => ['messages' => [[
                'from' => '201001234567',
                'type' => 'image',
                'image' => ['id' => 'x'],
            ]]]]]]],
        ])->assertOk();

        Bus::assertNothingDispatched();
    }

    public function test_the_channel_is_off_without_keys(): void
    {
        Bus::fake();
        config()->set('notifications.whatsapp.token', null);

        $this->postJson('/ai/whatsapp/webhook', $this->payload('201001234567', 'سؤال'))
            ->assertOk();

        Bus::assertNothingDispatched();
    }

    public function test_it_rate_limits_per_user(): void
    {
        Bus::fake();
        config()->set('ai.rate_limits.chat', 1);

        $this->postJson('/ai/whatsapp/webhook', $this->payload('201001234567', 'أ'))->assertOk();
        $this->postJson('/ai/whatsapp/webhook', $this->payload('201001234567', 'ب'))->assertOk();

        Bus::assertDispatchedTimes(AnswerWhatsAppMessage::class, 1);
    }

    // ── الرد ──────────────────────────────────────────────────────

    public function test_the_job_answers_with_the_users_own_agent(): void
    {
        $fake = Prism::fake([
            TextResponseFake::make()->withText('عندك صنف واحد تحت حد الطلب.'),
        ]);

        (new AnswerWhatsAppMessage($this->admin->id, '201001234567', 'إيه الناقص؟'))
            ->handle(
                app(AiService::class),
                app(AgentRegistry::class),
                app(WhatsAppService::class),
            );

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $request */
            $request = $requests[0];

            // أدوات تبويب المخازن — نفس اللي على الشاشة
            $names = array_map(fn ($tool) => $tool->name(), $request->tools());
            $this->assertContains('low-stock-products', $names);

            $system = collect($request->systemPrompts())
                ->map(fn ($prompt) => $prompt->content)
                ->implode("\n");

            // رد واتساب مختصر، والقاعدة الذهبية موجودة
            $this->assertStringContainsString('قصير جدًا', $system);
            $this->assertStringContainsString('القاعدة الذهبية', $system);
        });

        /*
         * والرد اتبعت على واتساب. بنفحص البيانات المفكوكة مش النص
         * الخام — `json_encode` بيهرّب العربي لـ \uXXXX.
         */
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/messages')
            && str_contains($request->data()['text']['body'] ?? '', 'تحت حد الطلب'));
    }

    /** الصلاحيات زي الموقع: المندوب مابياخدش أدوات الإدارة */
    public function test_a_sales_user_gets_only_their_own_agents(): void
    {
        $sales = $this->salesUser();
        $sales->forceFill([
            'whatsapp_number' => '201005555555',
            'whatsapp_verified_at' => now(),
        ])->save();

        $fake = Prism::fake([TextResponseFake::make()->withText('تمام.')]);

        (new AnswerWhatsAppMessage($sales->id, '201005555555', 'مين دخل النظام؟'))
            ->handle(
                app(AiService::class),
                app(AgentRegistry::class),
                app(WhatsAppService::class),
            );

        $fake->assertRequest(function (array $requests): void {
            $names = array_map(fn ($tool) => $tool->name(), $requests[0]->tools());

            // أدوات سجل النشاط للإدارة بس
            $this->assertNotContains('last-login', $names);
            $this->assertNotContains('recent-activity', $names);
        });
    }

    public function test_an_ai_failure_is_reported_in_arabic(): void
    {
        FailingPrism::fake(new \RuntimeException('boom'));

        (new AnswerWhatsAppMessage($this->admin->id, '201001234567', 'سؤال'))
            ->handle(
                app(AiService::class),
                app(AgentRegistry::class),
                app(WhatsAppService::class),
            );

        Http::assertSent(fn ($request): bool => str_contains(
            $request->data()['text']['body'] ?? '',
            'المساعد مش راضي يرد دلوقتي',
        ));
    }

    // ── ربط الرقم ─────────────────────────────────────────────────

    public function test_a_number_must_be_verified_with_a_code(): void
    {
        $user = $this->accountant();

        $this->actingAs($user)
            ->post('/settings/whatsapp', ['whatsapp_number' => '٢٠١٠٠٧٧٧٧٧٧٧'])
            ->assertRedirect();

        $user->refresh();

        // الأرقام الهندية اتحوّلت، والربط لسه مش متأكّد
        $this->assertSame('201007777777', $user->whatsapp_number);
        $this->assertNull($user->whatsapp_verified_at);
        $this->assertNotNull($user->whatsapp_code);

        $this->actingAs($user)
            ->post('/settings/whatsapp/verify', ['code' => $user->whatsapp_code])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->whatsapp_verified_at);
        // الكود بيتمسح بعد الاستعمال
        $this->assertNull($user->fresh()->whatsapp_code);
    }

    public function test_a_wrong_code_is_refused(): void
    {
        $user = $this->accountant();

        $this->actingAs($user)->post('/settings/whatsapp', ['whatsapp_number' => '201007777777']);

        $this->actingAs($user)
            ->post('/settings/whatsapp/verify', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->whatsapp_verified_at);
    }

    public function test_an_expired_code_is_refused(): void
    {
        $user = $this->accountant();

        $this->actingAs($user)->post('/settings/whatsapp', ['whatsapp_number' => '201007777777']);

        $user->refresh();
        $code = $user->whatsapp_code;

        $user->forceFill(['whatsapp_code_expires_at' => now()->subMinute()])->save();

        $this->actingAs($user)
            ->post('/settings/whatsapp/verify', ['code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_a_number_cannot_be_linked_to_two_accounts(): void
    {
        $this->actingAs($this->accountant())
            ->post('/settings/whatsapp', ['whatsapp_number' => '201001234567'])
            ->assertSessionHasErrors('whatsapp_number');
    }

    public function test_a_number_can_be_removed(): void
    {
        $this->actingAs($this->admin)
            ->delete('/settings/whatsapp')
            ->assertRedirect();

        $this->assertNull($this->admin->fresh()->whatsapp_number);
        $this->assertNull($this->admin->fresh()->whatsapp_verified_at);
    }

    /**
     * شكل الرسالة الواردة زي ما Meta بتبعتها.
     *
     * @return array<string, mixed>
     */
    private function payload(string $from, string $text): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'messages' => [[
                            'from' => $from,
                            'id' => 'wamid.test',
                            'type' => 'text',
                            'text' => ['body' => $text],
                        ]],
                    ],
                ]],
            ]],
        ];
    }
}
