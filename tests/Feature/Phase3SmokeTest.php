<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3SmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'owner',
            'organization_name' => 'Test Agency',
        ]);

        $this->member = User::factory()->create([
            'role' => 'member',
        ]);
    }

    // --- Page render tests ---

    public function test_dashboard_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Dashboard');
    }

    public function test_clients_index_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get('/clients')
            ->assertStatus(200);
    }

    public function test_clients_show_returns_200(): void
    {
        $client = Client::factory()->for($this->admin)->create();

        $this->actingAs($this->admin)
            ->get("/clients/{$client->id}")
            ->assertStatus(200)
            ->assertSee($client->company_name);
    }

    public function test_sites_index_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get('/sites')
            ->assertStatus(200);
    }

    public function test_sites_show_returns_200(): void
    {
        $client = Client::factory()->for($this->admin)->create();
        $site = Site::factory()->for($client)->create();

        $this->actingAs($this->admin)
            ->get("/sites/{$site->id}")
            ->assertStatus(200)
            ->assertSee($site->name);
    }

    public function test_team_page_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get('/team')
            ->assertStatus(200);
    }

    public function test_activity_page_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get('/activity')
            ->assertStatus(200);
    }

    public function test_settings_page_returns_200(): void
    {
        $this->actingAs($this->admin)
            ->get('/settings')
            ->assertStatus(200);
    }

    // --- Auth guard tests ---

    public function test_guests_redirected_from_all_pages(): void
    {
        $pages = ['/dashboard', '/clients', '/sites', '/team', '/activity', '/settings'];
        foreach ($pages as $page) {
            $this->get($page)->assertRedirect('/login');
        }
    }

    // --- Policy tests ---

    public function test_member_cannot_see_other_users_client(): void
    {
        $client = Client::factory()->for($this->admin)->create();

        $this->actingAs($this->member)
            ->get("/clients/{$client->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_see_any_client(): void
    {
        $client = Client::factory()->for($this->member)->create();

        $this->actingAs($this->admin)
            ->get("/clients/{$client->id}")
            ->assertStatus(200);
    }

    public function test_member_can_see_own_client(): void
    {
        $client = Client::factory()->for($this->member)->create();

        $this->actingAs($this->member)
            ->get("/clients/{$client->id}")
            ->assertStatus(200);
    }

    public function test_member_cannot_see_other_users_site(): void
    {
        $client = Client::factory()->for($this->admin)->create();
        $site = Site::factory()->for($client)->create();

        $this->actingAs($this->member)
            ->get("/sites/{$site->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_see_any_site(): void
    {
        $client = Client::factory()->for($this->member)->create();
        $site = Site::factory()->for($client)->create();

        $this->actingAs($this->admin)
            ->get("/sites/{$site->id}")
            ->assertStatus(200);
    }

    // --- Dashboard stats ---

    public function test_dashboard_shows_client_count(): void
    {
        Client::factory(3)->for($this->admin)->create();

        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertSee('3');
    }

    // --- Navigation ---

    public function test_sidebar_has_navigation_links(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $content = $response->getContent();

        $this->assertStringContainsString('Dashboard', $content);
        $this->assertStringContainsString('Clients', $content);
        $this->assertStringContainsString('Sites', $content);
        $this->assertStringContainsString('Team', $content);
        $this->assertStringContainsString('Settings', $content);
    }

    // --- Livewire component tests ---

    public function test_dashboard_uses_livewire(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertSeeLivewire('dashboard');
    }

    public function test_clients_index_uses_livewire(): void
    {
        $response = $this->actingAs($this->admin)->get('/clients');
        $response->assertSeeLivewire('clients.index');
    }

    public function test_sites_index_uses_livewire(): void
    {
        $response = $this->actingAs($this->admin)->get('/sites');
        $response->assertSeeLivewire('sites.index');
    }
}
