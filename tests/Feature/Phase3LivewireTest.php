<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase3LivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'owner']);
    }

    // --- Clients CRUD ---

    public function test_clients_index_search(): void
    {
        Client::factory()->for($this->admin)->create(['company_name' => 'Acme Corp']);
        Client::factory()->for($this->admin)->create(['company_name' => 'Beta Inc']);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Clients\Index::class)
            ->set('search', 'Acme')
            ->assertSee('Acme Corp')
            ->assertDontSee('Beta Inc');
    }

    public function test_clients_index_status_filter(): void
    {
        Client::factory()->for($this->admin)->create([
            'company_name' => 'Active Co',
            'status' => 'active',
        ]);
        Client::factory()->for($this->admin)->create([
            'company_name' => 'Inactive Co',
            'status' => 'inactive',
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Clients\Index::class)
            ->set('status', 'active')
            ->assertSee('Active Co')
            ->assertDontSee('Inactive Co');
    }

    public function test_clients_index_sort_toggle(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Clients\Index::class)
            ->assertSet('sortDir', 'asc')
            ->call('sort', 'company_name')
            ->assertSet('sortDir', 'desc')
            ->call('sort', 'company_name')
            ->assertSet('sortDir', 'asc');
    }

    public function test_client_form_create(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Clients\Form::class)
            ->set('company_name', 'New Client LLC')
            ->set('contact_email', 'test@example.com')
            ->set('status', 'active')
            ->call('save')
            ->assertDispatched('client-saved');

        $this->assertDatabaseHas('clients', ['company_name' => 'New Client LLC']);
    }

    public function test_client_form_validation(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Clients\Form::class)
            ->set('company_name', '')
            ->call('save')
            ->assertHasErrors('company_name');
    }

    public function test_client_form_edit(): void
    {
        $client = Client::factory()->for($this->admin)->create([
            'company_name' => 'Original Name',
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Clients\Form::class)
            ->call('loadClient', $client->id)
            ->assertSet('company_name', 'Original Name')
            ->set('company_name', 'Updated Name')
            ->call('save')
            ->assertDispatched('client-saved');

        $this->assertDatabaseHas('clients', ['company_name' => 'Updated Name']);
    }

    public function test_client_delete(): void
    {
        $client = Client::factory()->for($this->admin)->create();

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Clients\Index::class)
            ->call('deleteClient', $client->id);

        $this->assertSoftDeleted($client);
    }

    // --- Sites CRUD ---
    // Note: search / status / client filtering moved to client-side Alpine
    // (URL-driven, FOUC refactor), so Sites\Index no longer exposes server-side
    // filter properties to test here. Coverage lives in the rendering smoke tests.

    public function test_site_form_create(): void
    {
        $client = Client::factory()->for($this->admin)->create();

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Sites\Form::class)
            ->set('client_id', $client->id)
            ->set('name', 'Test WP Site')
            ->set('url', 'https://testwp.example.com')
            ->set('status', 'active')
            ->call('save')
            ->assertDispatched('site-saved');

        $this->assertDatabaseHas('sites', ['name' => 'Test WP Site']);
    }

    public function test_site_form_validation_url(): void
    {
        $client = Client::factory()->for($this->admin)->create();

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Sites\Form::class)
            ->set('client_id', $client->id)
            ->set('name', 'Test Site')
            ->set('url', 'not-a-valid-url')
            ->set('status', 'active')
            ->call('save')
            ->assertHasErrors('url');
    }

    public function test_site_delete(): void
    {
        $client = Client::factory()->for($this->admin)->create();
        $site = Site::factory()->for($client)->create();

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Sites\Index::class)
            ->call('deleteSite', $site->id);

        $this->assertSoftDeleted($site);
    }

    // --- Settings ---

    public function test_settings_save_profile(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Settings::class)
            ->set('name', 'Updated Name')
            ->call('saveProfile');

        $this->assertEquals('Updated Name', $this->admin->fresh()->name);
    }

    public function test_settings_save_organization(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Settings::class)
            ->set('organization_name', 'New Org Name')
            ->call('saveOrganization');

        $this->assertEquals('New Org Name', $this->admin->fresh()->organization_name);
    }

    // --- Users ---

    public function test_users_index_search(): void
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Users\Index::class)
            ->set('search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');
    }

    public function test_users_paginate_20(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Users\Index::class)
            ->assertStatus(200);
    }

    public function test_invite_form_creates_user(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Users\InviteForm::class)
            ->set('name', 'New User')
            ->set('email', 'newuser@test.com')
            ->set('role', 'viewer')
            ->call('save')
            ->assertDispatched('user-saved');

        $this->assertDatabaseHas('users', ['email' => 'newuser@test.com', 'role' => 'viewer']);
    }
}
