<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Site;
use App\Models\Client;
use App\Models\ContactEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseCSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_data_browser_renders(): void
    {
        $response = $this->actingAs($this->admin)->get('/data');
        $response->assertStatus(200);
        $response->assertSee('Браузер даних');
    }

    public function test_data_browser_defaults_to_a_single_type(): void
    {
        // The cross-type "Всі" tab was removed — the browser always shows one
        // entity type (default: phones) so bulk edits are never mixed-type.
        $response = $this->actingAs($this->admin)->get('/data');
        $response->assertStatus(200);
        $response->assertSee('Телефони');
        $response->assertSee('Месенджери');
        $response->assertSee('Ціни');
    }

    public function test_data_browser_type_filter(): void
    {
        $response = $this->actingAs($this->admin)->get('/data?typeFilter=phone');
        $response->assertStatus(200);
    }

    public function test_groups_page_renders(): void
    {
        $response = $this->actingAs($this->admin)->get('/groups');
        $response->assertStatus(200);
        $response->assertSee('Групи сайтів');
    }

    public function test_groups_page_with_group_sites(): void
    {
        $client = Client::factory()->create();
        Site::factory()->count(3)->create(['client_id' => $client->id, 'group' => 'production', 'group_color' => '#2E7D32']);
        $response = $this->actingAs($this->admin)->get('/groups');
        $response->assertStatus(200);
        $response->assertSee('production');
    }

    public function test_sites_index_group_filter(): void
    {
        $client = Client::factory()->create();
        Site::factory()->create(['client_id' => $client->id, 'group' => 'staging', 'name' => 'staging-site.com']);
        $response = $this->actingAs($this->admin)->get('/sites?groupFilter=staging');
        $response->assertStatus(200);
        $response->assertSee('staging-site.com');
    }

    public function test_phase_b_smoke_still_passes(): void
    {
        $response = $this->actingAs($this->admin)->get('/team');
        $response->assertStatus(200);
        $response->assertSee('Команда');
    }
}
