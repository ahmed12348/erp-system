<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_sends_guests_to_the_login_screen(): void
    {
        $this->get(route('home'))
            ->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_the_root_sends_members_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertRedirect('/dashboard');
    }
}
