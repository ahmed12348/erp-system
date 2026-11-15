<?php

namespace Tests\Feature\Erp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\Registrar;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * المسارات اللي claude.ai بيعتمد عليها عشان يربط الـ custom connector:
 * الاكتشاف (.well-known)، تسجيل العميل التلقائي، وشاشة الموافقة.
 */
class McpOAuthTest extends TestCase
{
    use RefreshDatabase;

    private const CLAUDE_REDIRECT = 'https://claude.ai/api/mcp/auth_callback';

    public function test_the_protected_resource_metadata_points_at_this_server(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource/mcp/erp')
            ->assertOk()
            ->assertJsonPath('resource', url('/mcp/erp'))
            ->assertJsonPath('authorization_servers.0', url('/'))
            ->assertJsonPath('scopes_supported.0', Registrar::OAUTH_SCOPE);
    }

    public function test_the_authorization_server_metadata_lists_the_oauth_endpoints(): void
    {
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('issuer', url('/'))
            ->assertJsonPath('authorization_endpoint', url('/oauth/authorize'))
            ->assertJsonPath('token_endpoint', url('/oauth/token'))
            ->assertJsonPath('registration_endpoint', url('/oauth/register'))
            // PKCE لازم يكون مدعوم — claude.ai بيستخدمه
            ->assertJsonPath('code_challenge_methods_supported.0', 'S256')
            ->assertJsonPath('grant_types_supported.0', 'authorization_code');
    }

    /** claude.ai بيسجّل نفسه لوحده (RFC 7591) قبل ما يبدأ تسجيل الدخول */
    public function test_claude_can_register_itself_as_a_client(): void
    {
        $response = $this->postJson('/oauth/register', [
            'client_name' => 'Claude',
            'redirect_uris' => [self::CLAUDE_REDIRECT],
            'client_uri' => 'https://claude.ai',
        ])->assertCreated();

        $this->assertSame([self::CLAUDE_REDIRECT], $response->json('redirect_uris'));
        $this->assertSame(Registrar::OAUTH_SCOPE, $response->json('scope'));
        $this->assertSame('none', $response->json('token_endpoint_auth_method'));
        $this->assertContains('authorization_code', $response->json('grant_types'));

        $client = Passport::client()->newQuery()->findOrFail($response->json('client_id'));

        // public client = من غير client_secret، فالـ PKCE هو اللي بيأمّن التبادل
        $this->assertFalse($client->confidential(), 'العميل لازم يكون public عشان PKCE.');
        $this->assertContains('authorization_code', $client->grant_types);
        $this->assertSame([self::CLAUDE_REDIRECT], $client->redirect_uris);
    }

    /** نطاقات تانية مرفوضة — القايمة في config/mcp.php */
    public function test_a_client_on_another_domain_is_rejected(): void
    {
        $this->postJson('/oauth/register', [
            'client_name' => 'Somebody Else',
            'redirect_uris' => ['https://not-claude.example.com/callback'],
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_redirect_uri');
    }

    /** شاشة الموافقة عربية RTL — دي الشاشة الوحيدة اللي المستخدم بيشوفها */
    public function test_the_consent_screen_is_arabic_and_rtl(): void
    {
        $user = User::factory()->create();

        $clientId = $this->postJson('/oauth/register', [
            'client_name' => 'Claude',
            'redirect_uris' => [self::CLAUDE_REDIRECT],
        ])->json('client_id');

        $response = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => self::CLAUDE_REDIRECT,
            'response_type' => 'code',
            'scope' => Registrar::OAUTH_SCOPE,
            'state' => 'xyz',
            'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'code_challenge_method' => 'S256',
        ]))->assertOk();

        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('اسمح بالوصول', false);
        $response->assertSee('رفض', false);
        $response->assertSee('قراءة بس', false);
        $response->assertSee($user->email, false);
    }

    /** التوكن الشخصي لـ Claude Code لسه شغال على نفس الجارد */
    public function test_a_personal_access_token_still_authenticates(): void
    {
        $this->artisan('erp:mcp-setup')->assertSuccessful();

        $user = User::factory()->create(['email' => 'admin@erp.test']);

        $this->artisan('erp:mcp-token', ['email' => $user->email])->assertSuccessful();

        $token = $user->tokens()->firstOrFail();

        $this->assertSame('mcp', $token->name);
        $this->assertContains(Registrar::OAUTH_SCOPE, $token->scopes);
        $this->assertFalse($token->revoked);
    }

    /** تشغيل الإعداد مرتين ما يعملش عميل تاني */
    public function test_the_setup_command_is_idempotent(): void
    {
        $this->artisan('erp:mcp-setup')->assertSuccessful();
        $before = Passport::client()->newQuery()->count();

        $this->artisan('erp:mcp-setup')->assertSuccessful();

        $this->assertSame($before, Passport::client()->newQuery()->count());
    }
}
