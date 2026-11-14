<?php

namespace Tests\Feature\Erp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /** كل قسم في القائمة الجانبية — المهم إنه يرد 200، مش أنهي صفحة بالظبط */
    public const SIDEBAR_SECTIONS = [
        '/dashboard',
        '/sales',
        '/purchases',
        '/inventory',
        '/customers',
        '/suppliers',
        '/treasury',
        '/reports',
    ];

    public function test_every_sidebar_section_responds(): void
    {
        $user = User::factory()->create();

        foreach (self::SIDEBAR_SECTIONS as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk();
        }
    }

    public function test_sections_require_authentication(): void
    {
        foreach (self::SIDEBAR_SECTIONS as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    /** بعد تسجيل الدخول المفروض نروح على لوحة التحكم مباشرة */
    public function test_login_lands_on_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');
    }

    /** الصفحة بتترسم RTL وبالخط العربي */
    public function test_root_template_is_rendered_right_to_left(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
    }
}
