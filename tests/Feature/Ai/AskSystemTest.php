<?php

namespace Tests\Feature\Ai;

use App\Ai\ErpToolkit;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\Text\Request as TextRequest;
use Tests\Support\FailingPrism;
use Tests\TestCase;

/**
 * «اسأل السيستم» — المحادثة بالكامل والـ API متزيّف.
 *
 * ولا اختبار هنا بيلمس شبكة: `Prism::fake()` بيبدّل المزوّد بنسخة
 * بترجّع ردود محضّرة وبتسجّل الطلبات، فنقدر نتأكد من الموديل
 * والتعليمات والأدوات اللي اتبعتت من غير مفتاح API.
 */
class AskSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // المفتاح لازم يبقى موجود عشان الخدمة تعتبر المساعد متظبّط
        config()->set('prism.providers.gemini.api_key', 'test-key');
        config()->set('prism.providers.anthropic.api_key', 'test-key');
        config()->set('ai.enabled', true);
        config()->set('ai.provider', 'gemini');

        RateLimiter::clear('ai-chat');
    }

    public function test_guests_cannot_use_the_assistant(): void
    {
        $this->postJson('/ai/chat', ['message' => 'عامل إيه؟'])
            ->assertUnauthorized();

        $this->getJson('/ai/chat')->assertUnauthorized();
    }

    public function test_the_panel_loads_the_starter_questions(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/ai/chat')
            ->assertOk()
            ->assertJson([
                'messages' => [],
                'enabled' => true,
            ])
            ->assertJsonCount(4, 'starters');
    }

    public function test_it_answers_a_question(): void
    {
        Prism::fake([
            TextResponseFake::make()->withText('عندك 3 أصناف تحت حد الطلب.'),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/ai/chat', ['message' => 'إيه الأصناف اللي قربت تخلص؟'])
            ->assertOk()
            ->assertJson([
                'reply' => [
                    'role' => 'assistant',
                    'content' => 'عندك 3 أصناف تحت حد الطلب.',
                ],
            ]);
    }

    public function test_the_question_is_rejected_when_empty_or_too_long(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => ''])
            ->assertJsonValidationErrors('message');

        $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => str_repeat('ا', 1001)])
            ->assertJsonValidationErrors('message');
    }

    public function test_the_conversation_is_kept_for_the_session(): void
    {
        Prism::fake([
            TextResponseFake::make()->withText('رصيده 12,000 ج.م.'),
            TextResponseFake::make()->withText('آخر دفعة كانت من أسبوع.'),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'رصيد أحمد كام؟']);
        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'وآخر دفعة؟']);

        $this->actingAs($user)
            ->getJson('/ai/chat')
            ->assertOk()
            ->assertJsonCount(4, 'messages')
            ->assertJsonPath('messages.0.content', 'رصيد أحمد كام؟')
            ->assertJsonPath('messages.1.content', 'رصيده 12,000 ج.م.')
            ->assertJsonPath('messages.3.content', 'آخر دفعة كانت من أسبوع.');
    }

    public function test_the_follow_up_question_carries_the_earlier_messages(): void
    {
        $fake = Prism::fake([
            TextResponseFake::make()->withText('رصيده 12,000 ج.م.'),
            TextResponseFake::make()->withText('آخر دفعة كانت من أسبوع.'),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'رصيد أحمد كام؟']);
        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'وآخر دفعة؟']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $second */
            $second = $requests[1];

            // السؤال التاني لازم يشوف السؤال الأول ورده قبله
            $this->assertCount(3, $second->messages());
        });
    }

    public function test_the_conversation_can_be_cleared(): void
    {
        Prism::fake([TextResponseFake::make()->withText('تمام.')]);

        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'سؤال']);
        $this->actingAs($user)->deleteJson('/ai/chat')->assertOk();

        $this->actingAs($user)
            ->getJson('/ai/chat')
            ->assertJsonCount(0, 'messages');
    }

    public function test_it_uses_the_gemini_model_by_default(): void
    {
        $fake = Prism::fake([TextResponseFake::make()->withText('تمام.')]);

        $this->actingAs(User::factory()->create())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $request */
            $request = $requests[0];

            $this->assertSame('gemini-2.5-flash', $request->model());
        });
    }

    public function test_switching_the_provider_to_anthropic_uses_a_haiku_model(): void
    {
        // زي ما السيرفر هيعمل بالظبط: السطر ده في .env وبس
        $this->reloadAiConfigWith(['ERP_AI_PROVIDER' => 'anthropic']);

        $fake = Prism::fake([TextResponseFake::make()->withText('تمام.')]);

        $this->actingAs(User::factory()->create())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $request */
            $request = $requests[0];

            $this->assertSame('anthropic', $request->provider());
            $this->assertStringContainsString('haiku', $request->model());
        });
    }

    public function test_the_erp_rules_and_the_read_only_tools_are_sent(): void
    {
        $fake = Prism::fake([TextResponseFake::make()->withText('تمام.')]);

        $this->actingAs(User::factory()->create())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $request */
            $request = $requests[0];

            $names = array_map(fn ($tool) => $tool->name(), $request->tools());

            // نفس أدوات سيرفر الـ MCP بالظبط
            $this->assertEqualsCanonicalizing([
                'low-stock-products', 'trader-balance', 'top-debtors',
                'sales-summary', 'top-products', 'stock-lookup', 'invoice-lookup',
            ], $names);

            $system = collect($request->systemPrompts())
                ->map(fn ($prompt) => $prompt->content)
                ->implode("\n");

            // قواعد قراءة الأرقام جاية من ErpServer
            $this->assertStringContainsString('رصيد التاجر موجب', $system);
            // وأسلوب الرد المصري
            $this->assertStringContainsString('بالعامية المصرية', $system);
        });
    }

    public function test_a_provider_failure_becomes_a_friendly_arabic_message(): void
    {
        FailingPrism::fake(PrismRateLimitedException::make([]));

        $this->actingAs(User::factory()->create())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertStatus(503)
            ->assertJson(['reason' => 'rate_limited'])
            ->assertJsonPath(
                'message',
                'تجاوزنا حد الاستخدام المسموح للمساعد دلوقتي. استنى شوية وجرّب تاني.',
            );
    }

    public function test_a_quota_error_tells_the_user_to_call_the_admin(): void
    {
        FailingPrism::fake(new \RuntimeException(
            'You exceeded your current quota, please check your plan and billing details.'
        ));

        $this->actingAs(User::factory()->create())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertStatus(503)
            ->assertJson([
                'reason' => 'quota',
                'message' => 'خلصت الحصة المجانية للمساعد. كلّم المدير يراجع إعدادات الـ API.',
            ]);
    }

    public function test_a_failed_answer_does_not_leave_a_dangling_question(): void
    {
        FailingPrism::fake(new \RuntimeException('boom'));

        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'سؤال وقع'])
            ->assertStatus(503);

        // السؤال اتحفظ عشان المستخدم يشوفه، لكن من غير رد مزيّف
        $this->actingAs($user)
            ->getJson('/ai/chat')
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.role', 'user');
    }

    public function test_the_assistant_is_off_without_an_api_key(): void
    {
        config()->set('prism.providers.gemini.api_key', '');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/ai/chat')
            ->assertJson(['enabled' => false]);

        $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertStatus(503)
            ->assertJson(['reason' => 'not_configured']);
    }

    public function test_it_rate_limits_each_user(): void
    {
        config()->set('ai.rate_limits.chat', 2);

        Prism::fake([
            TextResponseFake::make()->withText('واحد.'),
            TextResponseFake::make()->withText('اتنين.'),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'س'])->assertOk();
        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'س'])->assertOk();

        $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'س'])
            ->assertStatus(429)
            ->assertJson([
                'reason' => 'throttled',
                'message' => 'أسئلة كتير ورا بعض. استنى دقيقة وجرّب تاني.',
            ]);
    }

    public function test_the_bridged_tools_return_real_data_from_the_database(): void
    {
        $warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $category = Category::create(['name' => 'أجهزة منزلية']);

        $product = Product::create([
            'sku' => 'HA-9001',
            'name' => 'خلاط كهربائي',
            'category_id' => $category->id,
            'unit' => 'قطعة',
            'purchase_price' => 100,
            'sale_price' => 180,
            'min_stock' => 50,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 10,
            'unit_cost' => 100,
            'date' => now()->toDateString(),
        ]);

        $tools = collect(app(ErpToolkit::class)->tools())
            ->keyBy(fn ($tool) => $tool->name());

        $output = $tools->get('low-stock-products')->handle(limit: 20);

        // نفس مخرجات أداة الـ MCP — بأرقام لاتينية ونص عربي
        $this->assertStringContainsString('خلاط كهربائي', (string) $output);
        $this->assertStringContainsString('HA-9001', (string) $output);
        $this->assertStringContainsString('40', (string) $output);
    }

    /**
     * بيعيد بناء إعدادات `config/ai.php` بمتغيّرات بيئة مختلفة — بيثبت
     * إن تبديل المزوّد من `.env` لوحده كفاية.
     *
     * @param  array<string, string>  $env
     */
    private function reloadAiConfigWith(array $env): void
    {
        foreach ($env as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        config()->set('ai', require base_path('config/ai.php'));

        foreach (array_keys($env) as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }
}
