<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Client;
use App\Models\Site;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_clients(): void
    {
        $user = User::factory()->create();
        Client::factory(3)->for($user)->create();

        $this->assertCount(3, $user->clients);
    }

    public function test_client_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $this->assertEquals($user->id, $client->user->id);
    }

    public function test_client_has_many_sites(): void
    {
        $client = Client::factory()
            ->for(User::factory())
            ->has(Site::factory()->count(2))
            ->create();

        $this->assertCount(2, $client->sites);
    }

    public function test_client_active_sites_scope(): void
    {
        $client = Client::factory()->for(User::factory())->create();
        Site::factory()->for($client)->create(['status' => 'active']);
        Site::factory()->for($client)->create(['status' => 'offline']);

        $this->assertCount(1, $client->activeSites);
    }

    public function test_site_belongs_to_client(): void
    {
        $client = Client::factory()->for(User::factory())->create();
        $site = Site::factory()->for($client)->create();

        $this->assertEquals($client->id, $site->client->id);
    }

    public function test_site_status_color_attribute(): void
    {
        $client = Client::factory()->for(User::factory())->create();

        $active = Site::factory()->for($client)->create(['status' => 'active']);
        $this->assertEquals('ok', $active->status_color);

        $maintenance = Site::factory()->for($client)->create(['status' => 'maintenance']);
        $this->assertEquals('warn', $maintenance->status_color);

        $offline = Site::factory()->for($client)->create(['status' => 'offline']);
        $this->assertEquals('bad', $offline->status_color);
    }

    public function test_client_soft_deletes(): void
    {
        $client = Client::factory()->for(User::factory())->create();
        $client->delete();

        $this->assertSoftDeleted($client);
    }

    public function test_site_soft_deletes(): void
    {
        $client = Client::factory()->for(User::factory())->create();
        $site = Site::factory()->for($client)->create();
        $site->delete();

        $this->assertSoftDeleted($site);
    }

    public function test_user_is_admin(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        $this->assertTrue($owner->isAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($member->isAdmin());
    }

    public function test_user_is_owner(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($owner->isOwner());
        $this->assertFalse($admin->isOwner());
    }

    public function test_client_initials(): void
    {
        $client = Client::factory()->for(User::factory())
            ->create(['company_name' => 'Acme Corp']);

        $this->assertEquals('AC', $client->initials);
    }

    public function test_client_initials_single_word(): void
    {
        $client = Client::factory()->for(User::factory())
            ->create(['company_name' => 'Google']);

        $this->assertEquals('G', $client->initials);
    }

    public function test_activity_log_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $log = ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'created_at' => now(),
        ]);

        $this->assertEquals($user->id, $log->user->id);
    }

    public function test_activity_log_polymorphic_subject(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $log = ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'subject_type' => Client::class,
            'subject_id' => $client->id,
            'created_at' => now(),
        ]);

        $this->assertInstanceOf(Client::class, $log->subject);
        $this->assertEquals($client->id, $log->subject->id);
    }

    public function test_activity_log_properties_cast(): void
    {
        $user = User::factory()->create();
        $log = ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'updated',
            'properties' => ['old' => ['name' => 'A'], 'new' => ['name' => 'B']],
            'created_at' => now(),
        ]);

        $log->refresh();
        $this->assertIsArray($log->properties);
        $this->assertEquals('A', $log->properties['old']['name']);
    }

    public function test_user_has_many_activity_logs(): void
    {
        $user = User::factory()->create();
        ActivityLog::create(['user_id' => $user->id, 'action' => 'login', 'created_at' => now()]);
        ActivityLog::create(['user_id' => $user->id, 'action' => 'logout', 'created_at' => now()]);

        $this->assertCount(2, $user->activityLogs);
    }

    public function test_cascade_delete_client_sites(): void
    {
        $client = Client::factory()
            ->for(User::factory())
            ->has(Site::factory()->count(3))
            ->create();

        $clientId = $client->id;
        $client->forceDelete();

        $this->assertEquals(0, Site::where('client_id', $clientId)->count());
    }
}
