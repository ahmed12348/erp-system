<?php

namespace Tests\Feature\Erp;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AliasService;
use App\Services\InvoiceScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\TestCase;

/**
 * الأسماء البديلة — ذاكرة النظام لخط إيد العملاء.
 *
 * ده أهم جزء في تحسين المسح: بدل ما المستخدم يصحّح نفس الصنف كل
 * أسبوع، أول تصحيح بيتحفظ والمسح الجاي بيعرفه.
 */
class ProductAliasTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Customer $customer;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('prism.providers.gemini.api_key', 'test-key');
        config()->set('ai.enabled', true);
        config()->set('ai.provider', 'gemini');

        Storage::fake('local');

        Warehouse::create(['name' => 'المخزن الرئيسي']);
        $category = Category::create(['name' => 'أجهزة منزلية']);

        $this->admin = $this->admin();

        $this->customer = Customer::create([
            'name' => 'وليد عبد العظيم',
            'price_tier' => 'sale',
        ]);

        $this->product = Product::create([
            'sku' => 'HA-9001',
            'name' => 'كبولي مجوز',
            'category_id' => $category->id,
            'unit' => 'كرتونة',
            'purchase_price' => 100,
            'sale_price' => 180,
            'min_stock' => 5,
        ]);
    }

    public function test_an_alias_is_matched_before_anything_else(): void
    {
        app(AliasService::class)->rememberProduct($this->product, 'كبولى محوز');

        $this->fakeExtraction([
            'party_name' => null,
            'items' => [
                ['code' => null, 'name' => 'كبولى محوز', 'quantity' => 2, 'unit_price' => 150],
            ],
        ]);

        $this->scan();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        $this->assertSame($this->product->id, $payload['form']['items'][0]['product_id']);
        $this->assertSame(InvoiceScanService::MATCH_EXACT, $payload['review']['items'][0]['confidence']);
        $this->assertSame('alias', $payload['review']['items'][0]['via']);
    }

    public function test_a_customer_alias_matches_the_party(): void
    {
        app(AliasService::class)->rememberCustomer($this->customer, 'وليد الثلاتيني');

        $this->fakeExtraction([
            'party_name' => 'وليد الثلاتيني',
            'items' => [],
        ]);

        $this->scan();

        $payload = Invoice::query()->drafts()->sole()->scan_payload;

        $this->assertSame($this->customer->id, $payload['form']['customer_id']);
        $this->assertSame('alias', $payload['review']['party']['via']);
    }

    public function test_an_alias_is_normalised_so_small_spelling_shifts_still_match(): void
    {
        app(AliasService::class)->rememberProduct($this->product, 'كبولى محوز');

        // نفس الاسم بس بهمزة وتطويل
        $matched = app(AliasService::class)->matchProduct('كبولي محـوز');

        $this->assertNotNull($matched);
        $this->assertSame($this->product->id, $matched->id);
    }

    public function test_an_alias_equal_to_the_real_name_is_not_stored(): void
    {
        $alias = app(AliasService::class)->rememberProduct($this->product, 'كبولي مجوز');

        $this->assertNull($alias);
        $this->assertDatabaseCount('product_aliases', 0);
    }

    public function test_reassigning_an_alias_moves_it_instead_of_duplicating(): void
    {
        $other = Product::create([
            'sku' => 'HA-9002',
            'name' => 'كبولي فردي',
            'unit' => 'كرتونة',
            'purchase_price' => 50,
            'sale_price' => 90,
            'min_stock' => 5,
        ]);

        $service = app(AliasService::class);

        $service->rememberProduct($this->product, 'كبولى محوز');
        $service->rememberProduct($other, 'كبولى محوز');

        // اسم واحد ما ينفعش يودّي لصنفين
        $this->assertDatabaseCount('product_aliases', 1);
        $this->assertSame($other->id, $service->matchProduct('كبولى محوز')?->id);
    }

    public function test_the_product_page_lists_its_aliases(): void
    {
        app(AliasService::class)->rememberProduct($this->product, 'كبولى محوز', $this->admin);

        $this->actingAs($this->admin)
            ->get("/inventory/{$this->product->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('aliases.0.alias', 'كبولى محوز')
                ->where('aliases.0.addedBy', $this->admin->name)
                ->etc()
            );
    }

    public function test_an_alias_can_be_added_by_hand(): void
    {
        $this->actingAs($this->admin)
            ->post("/products/{$this->product->id}/aliases", ['alias' => 'كبولى ٢ جوز'])
            ->assertRedirect();

        $this->assertDatabaseHas('product_aliases', [
            'product_id' => $this->product->id,
            'alias' => 'كبولى ٢ جوز',
        ]);
    }

    public function test_an_alias_can_be_removed(): void
    {
        $alias = app(AliasService::class)->rememberProduct($this->product, 'كبولى محوز');

        $this->actingAs($this->admin)
            ->delete("/product-aliases/{$alias->id}")
            ->assertRedirect();

        $this->assertDatabaseCount('product_aliases', 0);
    }

    public function test_sales_users_cannot_manage_aliases(): void
    {
        $sales = $this->salesUser();

        $this->actingAs($sales)
            ->post("/products/{$this->product->id}/aliases", ['alias' => 'اسم'])
            ->assertForbidden();

        $alias = ProductAlias::create([
            'product_id' => $this->product->id,
            'alias' => 'كبولى محوز',
        ]);

        $this->actingAs($sales)
            ->delete("/product-aliases/{$alias->id}")
            ->assertForbidden();
    }

    public function test_an_empty_alias_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post("/products/{$this->product->id}/aliases", ['alias' => '  '])
            ->assertSessionHasErrors('alias');
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    private function fakeExtraction(array $structured): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured($structured),
        ]);
    }

    private function scan(): void
    {
        $this->actingAs($this->admin)->post('/ai/scan-invoice', [
            'file' => UploadedFile::fake()->create('invoice.jpg', 40, 'image/jpeg'),
            'type' => 'sale',
        ])->assertOk();
    }
}
