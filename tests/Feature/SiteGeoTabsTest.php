<?php

namespace Tests\Feature;

use App\Livewire\Sites\Index as SitesIndex;
use App\Livewire\Sites\Show;
use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\SiteGroup;
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

    public function test_site_settings_empty_queue_text_is_readable(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create();

        $this->actingAs($user)
            ->get("/sites/{$site->id}")
            ->assertStatus(200)
            ->assertSee('Черга порожня: немає активних телефонів.')
            ->assertDontSee('Р§РµСЂРіР°', false);
    }

    public function test_site_status_change_requires_confirmation(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create(['status' => 'active']);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('requestSetSiteStatus', 'maintenance')
            ->assertSet('confirmingAction', true)
            ->assertSet('confirmAction', 'set-site-status')
            ->assertSet('confirmSiteStatus', 'maintenance')
            ->call('confirmPendingAction')
            ->assertSet('confirmingAction', false);

        $this->assertSame('maintenance', $site->fresh()->status);
    }

    public function test_existing_site_can_be_assigned_to_group(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create(['group' => 'production', 'group_color' => '#2E7D32']);
        $group = SiteGroup::firstOrCreate(['name' => 'staging'], ['color' => '#d38a00']);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->set('siteGroupId', (string) $group->id)
            ->call('updateSiteGroup')
            ->assertSet('siteGroupId', (string) $group->id);

        $site->refresh();
        $this->assertSame('staging', $site->group);
        $this->assertSame($group->color, $site->group_color);
    }

    public function test_existing_site_can_be_assigned_to_group_from_dropdown(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create(['group' => 'production', 'group_color' => '#2E7D32']);
        $group = SiteGroup::firstOrCreate(['name' => 'staging'], ['color' => '#d38a00']);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('setSiteGroup', $group->id)
            ->assertSet('siteGroupId', (string) $group->id);

        $site->refresh();
        $this->assertSame('staging', $site->group);
        $this->assertSame($group->color, $site->group_color);
    }

    public function test_site_card_action_can_assign_group(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create(['group' => 'production', 'group_color' => '#2E7D32']);
        $group = SiteGroup::firstOrCreate(['name' => 'staging'], ['color' => '#d38a00']);

        Livewire::actingAs($user)
            ->test(SitesIndex::class)
            ->call('assignSiteGroup', $site->id, $group->id)
            ->assertDispatched('site-group-updated');

        $site->refresh();
        $this->assertSame('staging', $site->group);
        $this->assertSame($group->color, $site->group_color);
    }

    public function test_site_card_shows_active_prices_when_prices_category_is_enabled(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create(['data_categories' => ['phones', 'messengers', 'prices']]);
        ContactEntry::factory()->for($site)->price()->count(3)->create(['visible' => true, 'role' => 'primary']);
        ContactEntry::factory()->for($site)->price()->create(['visible' => false, 'role' => 'hidden']);

        Livewire::actingAs($user)
            ->test(SitesIndex::class)
            ->assertSee('Активні ціни')
            ->assertSee('цін');
    }

    public function test_site_card_hides_prices_metric_when_prices_category_is_disabled(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create(['data_categories' => ['phones', 'messengers']]);
        ContactEntry::factory()->for($site)->price()->count(3)->create(['visible' => true, 'role' => 'primary']);

        Livewire::actingAs($user)
            ->test(SitesIndex::class)
            ->assertDontSee('Активні ціни')
            ->assertDontSee('цін');
    }

    public function test_site_card_delete_requires_confirmation(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create();

        Livewire::actingAs($user)
            ->test(SitesIndex::class)
            ->call('requestDeleteSite', $site->id)
            ->assertSet('confirmDeleteSiteId', $site->id)
            ->assertSet('confirmDeleteSiteName', $site->name)
            ->call('confirmDeleteSite')
            ->assertHasErrors('confirmDeleteSiteTypedName')
            ->set('confirmDeleteSiteTypedName', $site->name)
            ->call('confirmDeleteSite')
            ->assertSet('confirmDeleteSiteId', null)
            ->assertSet('confirmDeleteSiteName', '');

        $this->assertSoftDeleted('sites', ['id' => $site->id]);
    }

    public function test_site_delete_requires_confirmation(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('requestDeleteSite')
            ->assertSet('confirmingAction', true)
            ->assertSet('confirmAction', 'delete-site')
            ->call('confirmPendingAction')
            ->assertSet('confirmingAction', true)
            ->set('confirmDeleteSiteName', $site->name)
            ->call('confirmPendingAction')
            ->assertRedirect(route('sites.index'));

        $this->assertSoftDeleted('sites', ['id' => $site->id]);
    }

    public function test_manual_failover_trigger_writes_journal_entry(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create();
        $phone = ContactEntry::factory()->for($site)->phone()->create(['value' => '+380991112233']);
        $backup = ContactEntry::factory()->backup($phone)->create(['value' => '+380992223344']);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('triggerFailover', $phone->id, $backup->id)
            ->assertSee('+380991112233')
            ->assertSee('+380992223344');

        $this->assertDatabaseHas('activity_log', [
            'action' => 'site.failover.triggered',
            'subject_type' => Site::class,
            'subject_id' => $site->id,
        ]);
    }

    public function test_manual_failover_restore_writes_journal_entry(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create();
        $phone = ContactEntry::factory()->for($site)->phone()->create(['value' => '+380991112233']);
        $backup = ContactEntry::factory()->backup($phone)->create(['value' => '+380992223344']);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('restoreFailover', $backup->id, $phone->id)
            ->assertSee('+380992223344')
            ->assertSee('+380991112233');

        $this->assertDatabaseHas('activity_log', [
            'action' => 'site.failover.restored',
            'subject_type' => Site::class,
            'subject_id' => $site->id,
        ]);
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

        // Entries are soft-deleted now (recoverable via Кошик / Undo).
        $this->assertSoftDeleted('contact_entries', ['id' => $phone->id]);
        $this->assertSoftDeleted('contact_entries', ['id' => $backup->id]);
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

    public function test_reserve_reads_geo_through_to_its_primary(): void
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

        // Read-through: the reserve stores neutral geo of its own…
        $this->assertNull($orphan->getRawOriginal('geo_tag'));
        $this->assertSame('all', $orphan->getRawOriginal('geo_mode'));
        // …but reports its primary's belonging, rule and visibility.
        $this->assertSame($parent->preview_geo_label, $orphan->preview_geo_label);
        $this->assertSame($parent->geo_label, $orphan->geo_label);
        $this->assertSame($parent->visibleForGeo('PL'), $orphan->visibleForGeo('PL'));
        $this->assertSame($parent->visibleForGeo('UA'), $orphan->visibleForGeo('UA'));
    }

    public function test_promoting_a_reserve_captures_inherited_geo(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($user)->create();
        $site = Site::factory()->for($client)->create();
        $parent = ContactEntry::factory()->for($site)->phone()->create([
            'geo_tag' => 'UA',
            'geo_mode' => 'except',
            'countries' => ['PL'],
        ]);
        $reserve = ContactEntry::factory()->backup($parent)->create([
            'geo_tag' => null,
            'geo_mode' => 'all',
            'countries' => null,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['site' => $site])
            ->call('promoteEntry', $reserve->id);

        $reserve->refresh();
        // Detached from its primary, the promoted entry keeps the geo it had.
        $this->assertNull($reserve->parent_id);
        $this->assertSame('primary', $reserve->role);
        $this->assertSame('UA', $reserve->geo_tag);
        $this->assertSame('except', $reserve->geo_mode);
        $this->assertSame(['PL'], $reserve->countries);
    }
}
