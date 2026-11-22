<?php

namespace Tests\Feature\Ai;

use App\Ai\ErpToolkit;
use App\Mcp\Tools\LastLogin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Mcp\Request;
use Prism\Prism\Tool;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * أدوات سجل النشاط في المساعد.
 *
 * أهم حاجة هنا الصلاحيات: المدير بيسأل عن أي حد، وباقي الأدوار عن
 * نفسهم بس — حتى لو كتبوا اسم حد تاني في السؤال.
 */
class ActivityToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $this->sales = $this->salesUser();

        Warehouse::create(['name' => 'المخزن الرئيسي']);
        Category::create(['name' => 'أجهزة منزلية']);

        Activity::query()->delete();
    }

    public function test_the_three_tools_are_available_to_the_assistant(): void
    {
        $names = collect(app(ErpToolkit::class)->tools())
            ->map(fn (Tool $tool) => $tool->name())
            ->all();

        $this->assertContains('recent-activity', $names);
        $this->assertContains('last-login', $names);
        $this->assertContains('who-did', $names);
    }

    public function test_recent_activity_lists_what_happened(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'صنف جديد', 'sku' => 'N-1', 'unit' => 'قطعة', 'sale_price' => 50,
        ]);

        $output = $this->asUser($this->admin)->runTool('recent-activity');

        $this->assertStringContainsString('أضاف', $output);
        $this->assertStringContainsString('صنف', $output);
        $this->assertStringContainsString($this->admin->name, $output);
    }

    public function test_recent_activity_can_filter_by_entity(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'صنف', 'sku' => 'N-2', 'unit' => 'قطعة', 'sale_price' => 50,
        ]);

        $this->assertStringContainsString(
            'صنف',
            $this->asUser($this->admin)->runTool('recent-activity', ['entity' => 'product']),
        );

        $this->assertStringContainsString(
            'مفيش نشاط',
            $this->asUser($this->admin)->runTool('recent-activity', ['entity' => 'invoice']),
        );
    }

    /** الصلاحية: المبيعات بيشوف نشاطه هو بس */
    public function test_a_sales_user_only_sees_their_own_activity(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'صنف المدير', 'sku' => 'A-1', 'unit' => 'قطعة', 'sale_price' => 50,
        ]);

        $output = $this->asUser($this->sales)->runTool('recent-activity');

        // نشاط المدير مش بيبان له
        $this->assertStringContainsString('مفيش نشاط', $output);
    }

    public function test_a_sales_user_cannot_ask_about_someone_else(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'صنف المدير', 'sku' => 'A-2', 'unit' => 'قطعة', 'sale_price' => 50,
        ]);

        // بيسأل عن المدير بالاسم صراحةً — وبرضه مابيشوفش حاجة
        $output = $this->asUser($this->sales)
            ->runTool('recent-activity', ['user' => $this->admin->name]);

        $this->assertStringContainsString('مفيش نشاط', $output);
    }

    public function test_last_login_shows_when_each_user_came_in(): void
    {
        $user = User::factory()->create([
            'name' => 'سامي الاختبار',
            'email' => 'sami@erp.test',
            'password' => Hash::make('password-1234'),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '41.33.2.7'])
            ->post('/login', ['email' => 'sami@erp.test', 'password' => 'password-1234']);

        $output = $this->asUser($this->admin)->runTool('last-login');

        $this->assertStringContainsString('سامي الاختبار', $output);
        $this->assertStringContainsString('41.33.2.7', $output);

        // ومستخدم عمره ما دخل بيتقال عنه كده بدل ما يختفي
        $this->assertStringContainsString('عمره ما دخل', $output);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_last_login_for_a_sales_user_is_only_their_own(): void
    {
        $output = $this->asUser($this->sales)->runTool('last-login');

        $this->assertStringContainsString($this->sales->name, $output);
        $this->assertStringNotContainsString($this->admin->name, $output);
    }

    public function test_who_did_traces_one_record(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'خلاط', 'sku' => 'TR-1', 'unit' => 'قطعة', 'sale_price' => 50,
        ]);

        $product = Product::where('sku', 'TR-1')->sole();

        $this->actingAs($this->admin)->put("/products/{$product->id}", [
            'name' => 'خلاط', 'sku' => 'TR-1', 'unit' => 'قطعة', 'sale_price' => 75,
        ]);

        $output = $this->asUser($this->admin)->runTool('who-did', ['reference' => 'TR-1']);

        $this->assertStringContainsString('أضاف', $output);
        $this->assertStringContainsString('عدّل', $output);
        $this->assertStringContainsString($this->admin->name, $output);
        // وبيبيّن إيه اللي اتغيّر
        $this->assertStringContainsString('سعر البيع', $output);
    }

    public function test_who_did_says_so_when_nothing_matches(): void
    {
        $output = $this->asUser($this->admin)->runTool('who-did', ['reference' => 'MISSING-9']);

        $this->assertStringContainsString('مفيش نشاط مسجّل', $output);
    }

    public function test_the_tools_refuse_when_nobody_is_signed_in(): void
    {
        auth()->logout();

        $output = (string) app(LastLogin::class)
            ->handle(new Request([]))
            ->content();

        $this->assertStringContainsString('مش عارف إنت مين', $output);
    }

    /** بيسجّل الدخول عشان الأداة تشوف المستخدم الصح */
    private function asUser(User $user): self
    {
        $this->actingAs($user);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function runTool(string $name, array $arguments = []): string
    {
        $tool = collect(app(ErpToolkit::class)->tools())
            ->first(fn (Tool $tool): bool => $tool->name() === $name);

        $this->assertNotNull($tool, "الأداة {$name} مش موجودة.");

        return (string) $tool->handle(...$arguments);
    }
}
