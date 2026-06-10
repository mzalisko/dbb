<?php

namespace Tests\Feature;

use App\Livewire\Sites\Index as SitesIndex;
use App\Livewire\Sites\Show;
use App\Models\Client;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteCloneRenameTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_can_be_renamed_without_changing_identity_access_or_key(): void
    {
        $user = User::factory()->create([
            'role' => 'manager',
            'access_scope' => 'limited',
            'group_access' => [],
            'site_access' => [],
        ]);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create([
            'name' => 'Old Name',
            'api_key' => 'db_live_same_key',
        ]);
        $user->forceFill(['site_access' => [$site->id]])->save();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->set('siteName', 'New Name')
            ->call('updateSiteName')
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'success');

        $site->refresh();
        $this->assertSame('New Name', $site->name);
        $this->assertSame('db_live_same_key', $site->api_key);
        $this->assertTrue($user->fresh()->canAccessSite($site));
    }

    public function test_limited_user_gets_id_access_to_generated_key_clone(): void
    {
        $user = User::factory()->create([
            'role' => 'manager',
            'access_scope' => 'limited',
            'group_access' => [],
            'site_access' => [],
        ]);
        $client = Client::factory()->for($user)->create();
        $source = Site::factory()->for($client)->create([
            'name' => 'Source',
            'group' => 'source-group',
            'api_key' => 'db_live_source_key',
        ]);
        $user->forceFill(['site_access' => [$source->id]])->save();

        Livewire::actingAs($user)
            ->test(SitesIndex::class)
            ->call('requestCloneSite', $source->id)
            ->set('cloneName', 'Independent Copy')
            ->set('cloneCopyGroup', false)
            ->call('confirmCloneSite')
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'success');

        $clone = Site::where('name', 'Independent Copy')->firstOrFail();
        $freshUser = $user->fresh();

        $this->assertNotSame($source->id, $clone->id);
        $this->assertSame('maintenance', $clone->status);
        $this->assertNull($clone->group);
        $this->assertNotSame($source->api_key, $clone->api_key);
        $this->assertContains($clone->id, $freshUser->site_access);
        $this->assertTrue($freshUser->canAccessSite($clone));

        Livewire::actingAs($freshUser)
            ->test(Show::class, ['site' => $clone])
            ->set('siteName', 'Renamed Copy')
            ->call('updateSiteName');

        $this->assertTrue($freshUser->fresh()->canAccessSite($clone->fresh()));
    }
}
