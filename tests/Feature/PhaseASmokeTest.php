<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseASmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_team_page_renders_with_ukrainian_labels(): void
    {
        $response = $this->actingAs($this->admin)->get('/team');
        $response->assertStatus(200);
        $response->assertSee('Команда');
    }

    public function test_team_page_shows_user_rows(): void
    {
        User::factory()->count(3)->create();
        $response = $this->actingAs($this->admin)->get('/team');
        $response->assertStatus(200);
        $response->assertSee($this->admin->name);
    }

    public function test_sites_index_renders(): void
    {
        $response = $this->actingAs($this->admin)->get('/sites');
        $response->assertStatus(200);
        $response->assertSee('Сайти');
    }

    public function test_app_layout_has_dark_mode_script(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('themeApp');
        $response->assertSee('db-theme');
    }

    public function test_activity_page_renders(): void
    {
        $response = $this->actingAs($this->admin)->get('/activity');
        $response->assertStatus(200);
    }

    public function test_settings_page_renders(): void
    {
        $response = $this->actingAs($this->admin)->get('/settings');
        $response->assertStatus(200);
    }

    public function test_data_page_renders(): void
    {
        $response = $this->actingAs($this->admin)->get('/data');
        $response->assertStatus(200);
    }

    public function test_groups_page_renders(): void
    {
        $response = $this->actingAs($this->admin)->get('/groups');
        $response->assertStatus(200);
    }
}
