<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Sites\Index as SitesIndex;
use App\Models\Client;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccessScopeTest extends TestCase
{
    use RefreshDatabase;

    /** A limited-scope manager who owns the client but only the 'allowed' group. */
    private function limitedManagerWithSites(): array
    {
        $manager = User::factory()->create([
            'role'         => 'manager',
            'access_scope' => 'limited',
            'group_access' => ['allowed'],
            'site_access'  => [],
        ]);
        $client = Client::factory()->for($manager)->create();
        $inScope = Site::factory()->for($client)->create(['name' => 'IN-SCOPE-SITE', 'group' => 'allowed']);
        $outScope = Site::factory()->for($client)->create(['name' => 'OUT-SCOPE-SITE', 'group' => 'forbidden']);

        return [$manager, $inScope, $outScope];
    }

    public function test_limited_scope_hides_out_of_scope_sites_in_the_list(): void
    {
        [$manager] = $this->limitedManagerWithSites();

        Livewire::actingAs($manager)
            ->test(SitesIndex::class)
            ->assertSee('IN-SCOPE-SITE')
            ->assertDontSee('OUT-SCOPE-SITE');
    }

    public function test_limited_scope_hides_out_of_scope_sites_on_the_dashboard(): void
    {
        [$manager] = $this->limitedManagerWithSites();

        Livewire::actingAs($manager)
            ->test(Dashboard::class)
            ->assertSee('IN-SCOPE-SITE')
            ->assertDontSee('OUT-SCOPE-SITE');
    }

    public function test_explicit_site_access_grants_a_single_site(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager', 'access_scope' => 'limited',
            'group_access' => [], 'site_access' => [],
        ]);
        $client = Client::factory()->for($manager)->create();
        $granted = Site::factory()->for($client)->create(['name' => 'GRANTED-SITE', 'group' => 'x']);
        Site::factory()->for($client)->create(['name' => 'DENIED-SITE', 'group' => 'x']);

        $manager->forceFill(['site_access' => [$granted->id]])->save();

        Livewire::actingAs($manager)
            ->test(SitesIndex::class)
            ->assertSee('GRANTED-SITE')
            ->assertDontSee('DENIED-SITE');
    }

    public function test_owner_sees_every_site(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($owner)->create();
        Site::factory()->for($client)->create(['name' => 'ALPHA-SITE']);
        Site::factory()->for($client)->create(['name' => 'BETA-SITE']);

        Livewire::actingAs($owner)
            ->test(SitesIndex::class)
            ->assertSee('ALPHA-SITE')
            ->assertSee('BETA-SITE');
    }
}
