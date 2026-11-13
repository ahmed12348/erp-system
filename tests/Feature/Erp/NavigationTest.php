<?php

namespace Tests\Feature\Erp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /** كل قسم في القائمة الجانبية لازم يرد 200 — مفيش 404 في أي حتة */
    public function test_every_sidebar_section_responds(): void
    {
        $user = User::factory()->create();

        $sections = [
            '/dashboard' => 'لوحة التحكم',
            '/sales' => 'المبيعات',
            '/purchases' => 'المشتريات',
            '/inventory' => 'المخزون',
            '/customers' => 'التجار',
            '/suppliers' => 'الموردون',
            '/treasury' => 'الخزينة',
            '/reports' => 'التقارير',
        ];

        foreach ($sections as $url => $title) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('ComingSoon')
                    ->where('title', $title)
                );
        }
    }

    public function test_sections_require_authentication(): void
    {
        $this->get('/sales')->assertRedirect('/login');
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
