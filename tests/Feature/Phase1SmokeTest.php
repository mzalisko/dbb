<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/dashboard');
    }

    public function test_login_page_returns_200(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertDontSee('@inertia');
    }

    public function test_login_page_has_no_inertia_markers(): void
    {
        $response = $this->get('/login');
        $response->assertDontSee('data-page=');
        $response->assertSee('<form', false);
    }

    public function test_register_page_returns_200(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_authenticated_user_sees_dashboard(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_guest_redirected_from_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_livewire_assets_present(): void
    {
        $response = $this->get('/login');
        $response->assertSee('livewire');
    }
}
