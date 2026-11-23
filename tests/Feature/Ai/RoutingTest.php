<?php

namespace Tests\Feature\Ai;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\Text\Request as TextRequest;
use Tests\Support\RoutesQuestions;
use Tests\Support\SequencedPrism;
use Tests\TestCase;

/**
 * توجيه السؤال لمجاله — من غير ما المستخدم يختار تبويب.
 *
 * اللوحة بقت شات واحد. قبل كل إجابة بنعمل نداء تصنيف صغير على الموديل
 * الخفيف، وبنبعت أدوات المجال اللي طلع منه بس.
 *
 * بيت القصيد في الملف ده إن **التوجيه عمره ما بيوقّع السؤال**: يفشل،
 * يرد كلام مش مفهوم، أو الوقت يضيق عليه — المستخدم بياخد رده في كل
 * الحالات، من آخر مجال اتكلم فيه أو من المجموعة الافتراضية.
 */
class RoutingTest extends TestCase
{
    use RefreshDatabase, RoutesQuestions;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('prism.providers.gemini.api_key', 'test-key');
        config()->set('ai.enabled', true);
        config()->set('ai.provider', 'gemini');

        RateLimiter::clear('ai-chat');
    }

    // ── التصنيف ───────────────────────────────────────────────────

    public function test_a_question_is_classified_before_it_is_answered(): void
    {
        $fake = Prism::fake([
            $this->routedTo('inventory'),
            TextResponseFake::make()->withText('عندك 3 أصناف تحت حد الطلب.'),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'إيه الأصناف اللي قربت تخلص؟'])
            ->assertOk()
            ->assertJsonPath('reply.content', 'عندك 3 أصناف تحت حد الطلب.')
            ->assertJsonPath('domains', ['inventory']);

        $fake->assertRequest(function (array $requests): void {
            $this->assertCount(2, $requests, 'المفروض نداءين: توجيه وإجابة.');

            /** @var TextRequest $routing */
            $routing = $requests[0];

            // نداء التوجيه من غير أدوات خالص — ده كل حكاية رخصه
            $this->assertSame([], $routing->tools());
            $this->assertStringContainsString(
                'صنّف',
                $this->systemOf($routing),
            );

            /** @var TextRequest $answer */
            $answer = $requests[1];
            $names = array_map(fn ($tool) => $tool->name(), $answer->tools());

            // أدوات المخازن بس — مش الـ 18 كلهم
            $this->assertEqualsCanonicalizing([
                'low-stock-products', 'stock-lookup', 'slow-moving-products',
                'fast-moving-products', 'forecast-needs',
            ], $names);
        });
    }

    /** السؤال اللي على أكتر من مجال بياخد أدوات المجالات كلها */
    public function test_a_cross_domain_question_gets_every_needed_toolset(): void
    {
        $fake = Prism::fake([
            $this->routedTo('sales', 'inventory'),
            TextResponseFake::make()->withText('تمام.'),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', [
                'message' => 'مين أكتر تاجر عليه فلوس وإيه الأصناف اللي قربت تخلص؟',
            ])
            ->assertOk()
            ->assertJsonPath('domains', ['inventory', 'sales']);

        $fake->assertRequest(function (array $requests): void {
            $names = array_map(fn ($tool) => $tool->name(), $requests[1]->tools());

            $this->assertContains('top-debtors', $names);
            $this->assertContains('low-stock-products', $names);
        });
    }

    /**
     * السؤال اللي بيلمس أكتر من مجال بياخد سطر من كل مجال مش تعليماته
     * كاملة — التعليمات بتتبعت مع كل جولة أدوات، وأربع مجموعات كاملة
     * بتتحوّل لوقت على استضافة بتقطع بعد 30 ثانية.
     */
    public function test_a_cross_domain_prompt_uses_briefs_not_full_instructions(): void
    {
        $fake = Prism::fake([
            $this->routedTo('sales', 'inventory'),
            TextResponseFake::make()->withText('تمام.'),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال على الاتنين']);

        $fake->assertRequest(function (array $requests): void {
            $system = $this->systemOf($requests[1]);

            $this->assertStringContainsString('بيلمس أكتر من مجال', $system);
            // القواعد الأساسية لسه موجودة
            $this->assertStringContainsString('عمولة المندوب على المحصّل', $system);
            $this->assertStringContainsString('القاعدة الذهبية', $system);
            // ومع كده فضل قصير
            $this->assertLessThan(1800, mb_strlen($system));
        });
    }

    // ── شبكة الأمان ───────────────────────────────────────────────

    public function test_an_unclear_question_falls_back_to_the_compact_default_set(): void
    {
        $fake = Prism::fake([
            $this->unclearRouting(),
            TextResponseFake::make()->withText('وضّح سؤالك شوية.'),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'إيه الأخبار؟'])
            ->assertOk()
            ->assertJsonPath('domains', ['general']);

        $fake->assertRequest(function (array $requests): void {
            $names = array_map(fn ($tool) => $tool->name(), $requests[1]->tools());

            $this->assertEqualsCanonicalizing([
                'low-stock-products', 'sales-summary', 'top-debtors',
                'invoice-lookup', 'company-overview',
            ], $names);
        });
    }

    public function test_a_failed_routing_call_still_answers(): void
    {
        $fake = SequencedPrism::fake([
            new \RuntimeException('routing exploded'),
            TextResponseFake::make()->withText('رد عادي.'),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertOk()
            ->assertJsonPath('reply.content', 'رد عادي.');

        // محاولة توجيه واحدة بس — فشله مش يستاهل إعادة محاولة
        $this->assertCount(2, $fake->models());
    }

    /**
     * فشل التوجيه `warning` مش `error`.
     *
     * على السيرفر `LOG_LEVEL=error`، والمستخدم أصلاً خد رده — فلو
     * سجّلناه كعطل كنا هنملا اللوج بحاجات محدش حسّ بيها.
     */
    public function test_a_routing_failure_is_a_warning_not_an_error(): void
    {
        SequencedPrism::fake([
            new \RuntimeException('routing exploded'),
            TextResponseFake::make()->withText('رد عادي.'),
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'AI request failed'
                && $context['operation'] === 'route');

        Log::shouldReceive('error')->never();

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertOk();
    }

    /**
     * الميزانية ضيّقة؟ الإجابة أهم من دقة التوجيه.
     */
    public function test_routing_is_skipped_when_the_budget_is_tight(): void
    {
        // أقل من `routing.reserve` (10 ثواني)
        config()->set('ai.budget', 9);

        $fake = Prism::fake([TextResponseFake::make()->withText('رد.')]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال'])
            ->assertOk()
            ->assertJsonPath('domains', ['general']);

        $fake->assertRequest(function (array $requests): void {
            $this->assertCount(1, $requests, 'مكانش المفروض ينادي التوجيه.');
        });
    }

    // ── سؤال المتابعة ─────────────────────────────────────────────

    public function test_a_follow_up_tells_the_router_what_came_before(): void
    {
        $fake = Prism::fake([
            $this->routedTo('sales'),
            TextResponseFake::make()->withText('رصيده 12,000 ج.م.'),
            $this->routedTo('sales'),
            TextResponseFake::make()->withText('آخر دفعة من أسبوع.'),
        ]);

        $user = $this->admin();

        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'رصيد أحمد كام؟']);
        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'وآخر دفعة؟']);

        $fake->assertRequest(function (array $requests): void {
            // أول توجيه مالوش ماضي
            $this->assertStringNotContainsString(
                'السؤال اللي فات',
                $this->systemOf($requests[0]),
            );

            // والتاني بياخد المجال اللي فات
            $second = $this->systemOf($requests[2]);

            $this->assertStringContainsString('السؤال اللي فات كان على: sales', $second);
            $this->assertStringContainsString('رُد بنفس المجال', $second);
        });
    }

    /** «وده؟» لوحده مالوش معنى — بيفضل في مجال اللي قبله */
    public function test_an_unclear_follow_up_stays_in_the_previous_domain(): void
    {
        $fake = Prism::fake([
            $this->routedTo('sales'),
            TextResponseFake::make()->withText('رصيده 12,000 ج.م.'),
            $this->unclearRouting(),
            TextResponseFake::make()->withText('وآخر دفعة من أسبوع.'),
        ]);

        $user = $this->admin();

        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'رصيد أحمد كام؟']);

        $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'وده؟'])
            ->assertOk()
            // رجع لمجال المبيعات، مش للمجموعة الافتراضية
            ->assertJsonPath('domains', ['sales']);

        $fake->assertRequest(function (array $requests): void {
            $names = array_map(fn ($tool) => $tool->name(), $requests[3]->tools());

            $this->assertContains('trader-balance', $names);
        });
    }

    /** المجموعة الافتراضية مش مجال — ما تتفكرش للسؤال اللي بعده */
    public function test_the_default_set_is_not_remembered(): void
    {
        $fake = Prism::fake([
            $this->unclearRouting(),
            TextResponseFake::make()->withText('وضّح.'),
            $this->unclearRouting(),
            TextResponseFake::make()->withText('وضّح تاني.'),
        ]);

        $user = $this->admin();

        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'إيه الأخبار؟']);
        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'وبعدين؟']);

        $fake->assertRequest(function (array $requests): void {
            $this->assertStringNotContainsString(
                'السؤال اللي فات',
                $this->systemOf($requests[2]),
            );
        });
    }

    public function test_clearing_the_conversation_forgets_the_domain(): void
    {
        $fake = Prism::fake([
            $this->routedTo('sales'),
            TextResponseFake::make()->withText('رصيده 12,000 ج.م.'),
            $this->routedTo('inventory'),
            TextResponseFake::make()->withText('تمام.'),
        ]);

        $user = $this->admin();

        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'رصيد أحمد كام؟']);
        $this->actingAs($user)->deleteJson('/ai/chat')->assertOk();
        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'سؤال جديد']);

        $fake->assertRequest(function (array $requests): void {
            $this->assertStringNotContainsString(
                'السؤال اللي فات',
                $this->systemOf($requests[2]),
            );
        });
    }

    // ── الصلاحيات ─────────────────────────────────────────────────

    /** الموجّه رد بمجال الدور مايوصلهوش — بيتشال في صمت */
    public function test_a_domain_the_role_cannot_use_is_ignored(): void
    {
        $fake = Prism::fake([
            $this->routedTo('admin'),
            TextResponseFake::make()->withText('تمام.'),
        ]);

        $this->actingAs($this->salesUser())
            ->postJson('/ai/chat', ['message' => 'مين دخل النظام؟'])
            ->assertOk()
            // مش مجال الإدارة — رجع للمجموعة الافتراضية
            ->assertJsonPath('domains', ['general']);

        $fake->assertRequest(function (array $requests): void {
            $names = array_map(fn ($tool) => $tool->name(), $requests[1]->tools());

            $this->assertNotContains('last-login', $names);
            $this->assertNotContains('recent-activity', $names);
        });
    }

    /**
     * المجموعة الافتراضية نفسها بتتصفّى على الدور.
     *
     * فيها `company-overview` و`invoice-lookup`، والمندوب مش بيوصلهم
     * من الشات العادي — فمش هياخدهم من هنا كمان.
     */
    public function test_the_default_set_is_filtered_by_role(): void
    {
        $fake = Prism::fake([
            $this->unclearRouting(),
            TextResponseFake::make()->withText('تمام.'),
        ]);

        $this->actingAs($this->salesUser())
            ->postJson('/ai/chat', ['message' => 'إيه الأخبار؟'])
            ->assertOk();

        $fake->assertRequest(function (array $requests): void {
            $names = array_map(fn ($tool) => $tool->name(), $requests[1]->tools());

            $this->assertEqualsCanonicalizing(
                ['low-stock-products', 'sales-summary', 'top-debtors'],
                $names,
            );
        });
    }

    /** المجال الممنوع مابيتعرضش على الموجّه أصلاً */
    public function test_the_routing_prompt_hides_domains_the_role_cannot_use(): void
    {
        $fake = Prism::fake([
            $this->routedTo('sales'),
            TextResponseFake::make()->withText('تمام.'),
        ]);

        $this->actingAs($this->salesUser())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            $system = $this->systemOf($requests[0]);

            $this->assertStringContainsString('inventory:', $system);
            $this->assertStringContainsString('sales:', $system);
            $this->assertStringNotContainsString('purchasing:', $system);
            $this->assertStringNotContainsString('admin:', $system);
        });
    }

    // ── تكلفة نداء التوجيه ────────────────────────────────────────

    public function test_the_routing_call_is_as_cheap_as_we_can_make_it(): void
    {
        config()->set('ai.models.chat', 'gemini-3.5-flash');

        $fake = Prism::fake([
            $this->routedTo('inventory'),
            TextResponseFake::make()->withText('تمام.'),
        ]);

        $this->actingAs($this->admin())
            ->postJson('/ai/chat', ['message' => 'سؤال']);

        $fake->assertRequest(function (array $requests): void {
            /** @var TextRequest $routing */
            $routing = $requests[0];

            // الموديل الخفيف، مش موديل الإجابة
            $this->assertSame('gemini-3.5-flash-lite', $routing->model());
            $this->assertSame([], $routing->tools());
            $this->assertSame(32, $routing->maxTokens());
            // سقف أقصر من سقف الإجابة
            $this->assertSame(6, $routing->clientOptions()['timeout']);
            // وتعليمات قصيرة
            $this->assertLessThan(700, mb_strlen($this->systemOf($routing)));
        });
    }

    private function systemOf(TextRequest $request): string
    {
        return collect($request->systemPrompts())
            ->map(fn ($prompt) => $prompt->content)
            ->implode("\n");
    }
}
