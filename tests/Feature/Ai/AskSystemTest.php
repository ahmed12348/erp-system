<?php

namespace Tests\Feature\Ai;

use App\Ai\ErpToolkit;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\Text\Request as TextRequest;
use Tests\Support\FailingPrism;
use Tests\Support\RoutesQuestions;
use Tests\Support\SequencedPrism;
use Tests\TestCase;

/**
 * «اسأل السيستم» — المحادثة بالكامل والـ API متزيّف.
 *
 * ولا اختبار هنا بيلمس شبكة: `Prism::fake()` بيبدّل المزوّد بنسخة
 * بترجّع ردود محضّرة وبتسجّل الطلبات، فنقدر نتأكد من الموديل
 * والتعليمات والأدوات اللي اتبعتت من غير مفتاح API.
 *
 * كل سؤال = نداءين: التوجيه الأول وبعده الإجابة. فالردود المحضّرة
 * بتيجي أزواج، والطلب اللي بنفحصه هو `$requests[1]`. التوجيه نفسه
 * ليه ملف لوحده — `RoutingTest`.
 */
class AskSystemTest extends TestCase
{
    use RefreshDatabase, RoutesQuestions;

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

    /**
     * شات واحد من غير تبويبات: اللوحة بتجيب المحادثة والأسئلة المقترحة
     * وبس. المجالات بقت ورا الستار — مش حاجة المستخدم بيختارها.
     */
    public function test_the_panel_loads_the_conversation_and_the_starters(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/ai/chat')
            ->assertOk()
            ->assertJson([
                'messages' => [],
                'enabled' => true,
            ])
            // مفيش تبويبات تتعرض
            ->assertJsonMissingPath('agents')
            ->assertJsonMissingPath('agent')
            ->assertJsonCount(6, 'starters');
    }

    public function test_it_answers_a_question(): void
    {
        Prism::fake([
            $this->routedTo('inventory'),
            TextResponseFake::make()->withText('عندك 3 أصناف تحت حد الطلب.'),
        ]);

        $this->actingAs($this->admin())
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
        $user = $this->admin();

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
            $this->routedTo('sales'),
            TextResponseFake::make()->withText('رصيده 12,000 ج.م.'),
            $this->routedTo('sales'),
            TextResponseFake::make()->withText('آخر دفعة كانت من أسبوع.'),
        ]);

        $user = $this->admin();

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
            $this->routedTo('sales'),
            TextResponseFake::make()->withText('رصيده 12,000 ج.م.'),
            $this->routedTo('sales'),
            TextResponseFake::make()->withText('آخر دفعة كانت من أسبوع.'),
        ]);

        $user = $this->admin();

        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'رصيد أحمد كام؟']);
        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'وآخر دفعة؟']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $second */
            $second = $requests[3];

            // السؤال التاني لازم يشوف السؤال الأول ورده قبله
            $this->assertCount(3, $second->messages());

            // ونداء التوجيه عكسه: السؤال الجديد لوحده
            $this->assertCount(1, $requests[2]->messages());
        });
    }

    public function test_the_conversation_can_be_cleared(): void
    {
        Prism::fake([
            $this->routedTo('inventory'),
            TextResponseFake::make()->withText('تمام.'),
        ]);

        $user = $this->admin();

        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'سؤال']);
        $this->actingAs($user)->deleteJson('/ai/chat')->assertOk();

        $this->actingAs($user)
            ->getJson('/ai/chat')
            ->assertJsonCount(0, 'messages');
    }

    /**
     * المحادثة على الموديل الخفيف: بالقياس على السيرفر، النداء على
     * flash بياخد ~14 ثانية وعلى flash-lite أقل من ثانية، والرقم جاي
     * من الأداة مش من الموديل أصلاً.
     */
    public function test_chat_uses_the_light_model_by_default(): void
    {
        $fake = Prism::fake([$this->routedTo('inventory'), TextResponseFake::make()->withText('تمام.')]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $request */
            $request = $requests[1];

            $this->assertSame('gemini-3.5-flash-lite', $request->model());
        });
    }

    public function test_switching_the_provider_to_anthropic_uses_a_haiku_model(): void
    {
        // زي ما السيرفر هيعمل بالظبط: السطر ده في .env وبس
        $this->reloadAiConfigWith(['ERP_AI_PROVIDER' => 'anthropic']);

        $fake = Prism::fake([$this->routedTo('inventory'), TextResponseFake::make()->withText('تمام.')]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $request */
            $request = $requests[1];

            $this->assertSame('anthropic', $request->provider());
            $this->assertStringContainsString('haiku', $request->model());
        });
    }

    public function test_the_erp_rules_and_the_read_only_tools_are_sent(): void
    {
        $fake = Prism::fake([$this->routedTo('inventory'), TextResponseFake::make()->withText('تمام.')]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $request */
            $request = $requests[1];

            $names = array_map(fn ($tool) => $tool->name(), $request->tools());

            /*
             * أدوات المخازن بس — مش كل أدوات النظام. ده بيت القصيد من
             * التوجيه: مجموعة صغيرة مركّزة بدل سلّة فيها تمنتاشر أداة.
             */
            $this->assertEqualsCanonicalizing([
                'low-stock-products', 'stock-lookup', 'slow-moving-products',
                'fast-moving-products', 'forecast-needs',
            ], $names);

            $system = collect($request->systemPrompts())
                ->map(fn ($prompt) => $prompt->content)
                ->implode("\n");

            // أسلوب الرد المصري والقاعدة الذهبية
            $this->assertStringContainsString('عامية مصرية', $system);
            $this->assertStringContainsString('القاعدة الذهبية', $system);
            // وتخصص المجال اللي السؤال اتوجّه عليه — المخازن
            $this->assertStringContainsString('تخصصك المخزن', $system);
        });
    }

    /**
     * التعليمات بتتبعت مع كل جولة أدوات، فطولها بيتحوّل لوقت على
     * استضافة بتقطع بعد 30 ثانية. وصف كل أداة بيتبعت مع الأداة نفسها،
     * فمالوش لازمة يتكرّر هنا.
     */
    public function test_the_system_prompt_stays_short(): void
    {
        $fake = Prism::fake([$this->routedTo('inventory'), TextResponseFake::make()->withText('تمام.')]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $request */
            $request = $requests[1];

            $system = collect($request->systemPrompts())
                ->map(fn ($prompt) => $prompt->content)
                ->implode("\n");

            /*
             * السقف شايل قايمة الشاشات (٢٠٠ حرف تقريبًا). دفعناها عن
             * قصد: من غيرها الموديل بيخترع شاشات ويبعت المستخدم يدوّر
             * فيها، وده أغلى بكتير من التوكنز.
             */
            $this->assertLessThan(1800, mb_strlen($system));

            // الشرح الطويل بتاع سيرفر الـ MCP مابيتبعتش مع المحادثة
            $this->assertStringNotContainsString('سيرفر قراءة فقط', $system);
        });
    }

    /**
     * الاستضافة بتقطع التنفيذ بعد حوالي 30 ثانية، فكل نداء ليه مهلة
     * والطلب كله ليه ميزانية أقصر من كده.
     */
    public function test_each_call_gets_a_twenty_second_timeout(): void
    {
        $fake = Prism::fake([$this->routedTo('inventory'), TextResponseFake::make()->withText('تمام.')]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $request */
            $request = $requests[1];

            $this->assertSame(20, $request->clientOptions()['timeout']);
        });
    }

    public function test_chat_asks_for_the_lowest_thinking_level(): void
    {
        $fake = Prism::fake([$this->routedTo('inventory'), TextResponseFake::make()->withText('تمام.')]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $request */
            $request = $requests[1];

            $this->assertSame('low', $request->providerOptions('thinkingLevel'));
        });
    }

    /**
     * الجولة الواحدة بتتدار من `AiService` مش من Prism، عشان نقدر
     * نحسب الوقت الفاضل قبل كل جولة.
     */
    public function test_the_tool_loop_is_driven_one_step_at_a_time(): void
    {
        $fake = Prism::fake([$this->routedTo('inventory'), TextResponseFake::make()->withText('تمام.')]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $request */
            $request = $requests[1];

            $this->assertSame(1, $request->maxSteps());
        });
    }

    public function test_it_gives_up_with_an_arabic_message_when_the_budget_runs_out(): void
    {
        // ميزانية صفر — مفيش وقت يكفي ولا نداء واحد
        config()->set('ai.budget', 0);

        Prism::fake([TextResponseFake::make()->withText('تمام.')]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertStatus(503)
            ->assertJson([
                'reason' => 'timeout',
                'message' => 'المساعد أخد وقت أطول من اللازم. جرّب تاني، أو اسأل سؤال أبسط شوية.',
            ]);
    }

    public function test_a_connection_timeout_becomes_the_friendly_timeout_message(): void
    {
        FailingPrism::fake(
            new ConnectionException('cURL error 28: Operation timed out')
        );

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertStatus(503)
            ->assertJson(['reason' => 'timeout']);
    }

    public function test_an_overloaded_provider_is_retried_then_falls_back(): void
    {
        // لازم يبقى فيه بديل مختلف عن الأساسي عشان الرجوع يبان
        config()->set('ai.models.chat', 'gemini-3.5-flash');

        $fake = SequencedPrism::fake([
            // التوجيه بيعدّي عادي
            $this->routedTo('inventory'),
            // الأساسي مزحوم مرتين
            new PrismProviderOverloadedException('Gemini overloaded'),
            new PrismProviderOverloadedException('Gemini overloaded'),
            // البديل بيرد
            TextResponseFake::make()->withText('رد من الموديل البديل.'),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertOk()
            ->assertJsonPath('reply.content', 'رد من الموديل البديل.');

        // التوجيه على الخفيف، وبعده الإجابة بترجع للخفيف بعد محاولتين
        $this->assertSame([
            'gemini-3.5-flash-lite',
            'gemini-3.5-flash',
            'gemini-3.5-flash',
            'gemini-3.5-flash-lite',
        ], $fake->models());
    }

    /**
     * الموديل اللي اتأخر مرة هيتأخر تاني، فإعادة المحاولة عليه بتاكل
     * الميزانية من غير فايدة — بنقفز للبديل الأخف على طول.
     */
    public function test_a_slow_model_falls_back_without_retrying_itself(): void
    {
        // الأساسي بطيء، فبنخلّيه flash والبديل flash-lite
        config()->set('ai.models.chat', 'gemini-3.5-flash');

        $fake = SequencedPrism::fake([
            $this->routedTo('inventory'),
            new ConnectionException('cURL error 28: Operation timed out'),
            TextResponseFake::make()->withText('رد من الموديل الأخف.'),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertOk()
            ->assertJsonPath('reply.content', 'رد من الموديل الأخف.');

        // محاولتين للإجابة بس — من غير إعادة محاولة على الموديل البطيء
        $this->assertSame([
            'gemini-3.5-flash-lite',
            'gemini-3.5-flash',
            'gemini-3.5-flash-lite',
        ], $fake->models());
    }

    public function test_a_timeout_does_not_fall_back_when_the_budget_is_gone(): void
    {
        config()->set('ai.models.chat', 'gemini-3.5-flash');

        /*
         * الميزانية 4.5 ثانية، والنداء الأول بياكل 0.6 منها — فيفضل
         * 3.9، أقل من الحد الأدنى اللي يستاهل نداء جديد (4).
         */
        config()->set('ai.budget', 4.5);

        $fake = SequencedPrism::fake([
            new ConnectionException('cURL error 28: Operation timed out'),
            TextResponseFake::make()->withText('ما يوصلش هنا.'),
        ], delayMs: 600);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertStatus(503)
            ->assertJson(['reason' => 'timeout']);

        // جرّب الأساسي بس — مفيش وقت للبديل
        $this->assertSame(['gemini-3.5-flash'], $fake->models());
    }

    public function test_a_missing_model_falls_back_instead_of_failing(): void
    {
        config()->set('ai.models.chat', 'gemini-3.5-flash');

        $fake = SequencedPrism::fake([
            $this->routedTo('inventory'),
            // زي ما بيحصل للحسابات الجديدة لما الموديل مش متاح
            new PrismException('Gemini Error: [404] models/x is not found'),
            new PrismException('Gemini Error: [404] models/x is not found'),
            TextResponseFake::make()->withText('رد من البديل.'),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertOk();

        $this->assertSame('gemini-3.5-flash-lite', $fake->models()[3]);
    }

    public function test_a_quota_error_is_not_retried(): void
    {
        $fake = SequencedPrism::fake([
            $this->routedTo('inventory'),
            PrismRateLimitedException::make([]),
            TextResponseFake::make()->withText('ما يوصلش هنا.'),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertStatus(503)
            ->assertJson(['reason' => 'rate_limited']);

        // التوجيه ومحاولة إجابة واحدة — الحصة خلصت فإعادة المحاولة مالهاش لازمة
        $this->assertCount(2, $fake->models());
    }

    public function test_ai_failures_are_logged_at_error_level(): void
    {
        FailingPrism::fake(new \RuntimeException('boom'));

        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'AI request failed'
                && $context['operation'] === 'chat'
                && $context['provider'] === 'gemini');

        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertStatus(503);
    }

    public function test_a_provider_failure_becomes_a_friendly_arabic_message(): void
    {
        FailingPrism::fake(PrismRateLimitedException::make([]));

        $this->actingAs($this->admin())
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

        $this->actingAs($this->admin())
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

        $user = $this->admin();

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

        $user = $this->admin();

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
            $this->routedTo('inventory'),
            TextResponseFake::make()->withText('واحد.'),
            $this->routedTo('inventory'),
            TextResponseFake::make()->withText('اتنين.'),
        ]);

        $user = $this->admin();

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
