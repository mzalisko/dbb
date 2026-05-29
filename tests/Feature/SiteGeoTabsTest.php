<?php

namespace Tests\Feature;

use App\Livewire\Sites\Show;
use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteGeoTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_geo_tab_removal_persists_for_site(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create(['geo_tabs' => ['UA', 'RU', 'BY']]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('removeGeoTab', 'UA')
            ->assertSet('geoTabs', ['RU', 'BY']);

        $this->assertSame(['RU', 'BY'], $site->fresh()->geo_tabs);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site->fresh()])
            ->assertSet('geoTabs', ['RU', 'BY']);
    }

    public function test_styled_delete_confirmation_removes_entry_and_backups(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create();
        $phone = ContactEntry::factory()->for($site)->phone()->create(['value' => '+380991112233']);
        $backup = ContactEntry::factory()->backup($phone)->create(['value' => '+380992223344']);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('requestDeleteEntry', $phone->id)
            ->assertSet('confirmingAction', true)
            ->assertSet('confirmAction', 'delete-entry')
            ->call('confirmPendingAction')
            ->assertSet('confirmingAction', false);

        $this->assertDatabaseMissing('contact_entries', ['id' => $phone->id]);
        $this->assertDatabaseMissing('contact_entries', ['id' => $backup->id]);
    }

    public function test_preview_geo_label_marks_tab_membership(): void
    {
        $tagged = ContactEntry::factory()->make([
            'geo_tag' => 'UA',
            'geo_mode' => 'all',
            'countries' => [],
        ]);

        $legacyOnly = ContactEntry::factory()->make([
            'geo_tag' => null,
            'geo_mode' => 'only',
            'countries' => ['PL'],
        ]);

        $unassigned = ContactEntry::factory()->make([
            'geo_tag' => null,
            'geo_mode' => 'all',
            'countries' => [],
        ]);

        $this->assertSame('UA', $tagged->preview_geo_label);
        $this->assertSame('PL', $legacyOnly->preview_geo_label);
        $this->assertNull($unassigned->preview_geo_label);
    }

    public function test_geo_tag_drives_visibility_when_countries_are_empty(): void
    {
        $taggedOnly = ContactEntry::factory()->make([
            'geo_tag' => 'PL',
            'geo_mode' => 'only',
            'countries' => [],
        ]);

        $this->assertTrue($taggedOnly->visibleForGeo('PL'));
        $this->assertFalse($taggedOnly->visibleForGeo('UA'));
        $this->assertStringContainsString('PL', $taggedOnly->geo_label);
    }

    public function test_world_overview_column_excludes_only_geo_contacts(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create(['geo_tabs' => ['UA', 'PL']]);

        ContactEntry::factory()->for($site)->phone()->create([
            'value' => '+48000000000',
            'geo_tag' => 'PL',
            'geo_mode' => 'only',
            'countries' => [],
        ]);
        ContactEntry::factory()->for($site)->phone()->create([
            'value' => '11111111111',
            'geo_mode' => 'all',
            'countries' => [],
        ]);
        ContactEntry::factory()->for($site)->phone()->create([
            'value' => '22222222222',
            'geo_mode' => 'except',
            'countries' => ['PL'],
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->assertSee('11111111111')
            ->assertSee('+48000000000');

        $component = Livewire::actingAs($user)->test(Show::class, ['site' => $site]);
        $overview = $component->viewData('overviewByGeo');

        $this->assertSame(['11111111111', '22222222222'], $overview['all']['phones']->pluck('value')->all());
        $this->assertSame(['11111111111', '22222222222'], $overview['UA']['phones']->pluck('value')->all());
        $this->assertSame(['+48000000000', '11111111111'], $overview['PL']['phones']->pluck('value')->all());
    }

    public function test_geo_rule_removal_is_confirmed_and_persisted(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create([
            'geo_tabs' => ['UA', 'RU', 'BY'],
            'geo_rules' => [['id' => 7, 'groups' => [['UA'], ['RU', 'BY']]]],
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('requestRemoveGeoRule', 7)
            ->assertSet('confirmingAction', true)
            ->assertSet('confirmAction', 'remove-geo-rule')
            ->call('confirmPendingAction')
            ->assertSet('geoRules', []);

        $this->assertSame([], $site->fresh()->geo_rules);
    }

    public function test_assigning_backup_inherits_parent_geo_membership(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create();
        $parent = ContactEntry::factory()->for($site)->phone()->create([
            'geo_tag' => 'UA',
            'geo_mode' => 'except',
            'countries' => ['PL'],
        ]);
        $orphan = ContactEntry::factory()->for($site)->phone()->create([
            'geo_tag' => null,
            'geo_mode' => 'all',
            'countries' => [],
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('assignAsBackup', $orphan->id, $parent->id);

        $orphan->refresh();
        $this->assertSame('backup', $orphan->role);
        $this->assertSame($parent->id, $orphan->parent_id);
        $this->assertSame('UA', $orphan->geo_tag);
        $this->assertSame('except', $orphan->geo_mode);
        $this->assertSame(['PL'], $orphan->countries);
    }
}
