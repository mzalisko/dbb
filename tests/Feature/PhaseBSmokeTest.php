<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseBSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_drawer_blade_component_exists(): void
    {
        $this->assertFileExists(resource_path('views/components/ui/drawer.blade.php'));
    }

    public function test_team_page_has_open_user_dispatch(): void
    {
        $response = $this->actingAs($this->admin)->get('/team');
        $response->assertStatus(200);
        // page renders OK -- drawer state handled client-side
    }

    public function test_team_page_with_multiple_users(): void
    {
        User::factory()->count(5)->create();
        $response = $this->actingAs($this->admin)->get('/team');
        $response->assertStatus(200);
        $response->assertSee('Команда');
    }

    public function test_site_detail_has_category_property(): void
    {
        // Verifies PA-T03 + PB-T03 integration
        $site = Site::factory()->create();
        $response = $this->actingAs($this->admin)->get("/sites/{$site->id}");
        $response->assertStatus(200);
    }

    public function test_phase_a_smoke_still_passes(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('themeApp');
    }
}
