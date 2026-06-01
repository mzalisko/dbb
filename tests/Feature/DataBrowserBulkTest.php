<?php

namespace Tests\Feature;

use App\Livewire\DataBrowser;
use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DataBrowserBulkTest extends TestCase
{
    use RefreshDatabase;

    private function siteForOwner(User $owner): Site
    {
        $client = Client::factory()->for($owner)->create();

        return Site::factory()->for($client)->create();
    }

    public function test_select_page_then_clear(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(3)
            ->create()->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->assertSet('selected', $ids)
            ->call('clearSelected')
            ->assertSet('selected', [])
            ->assertSet('selectAllMatching', false);
    }

    public function test_selection_persists_across_site_filter_but_type_change_clears_it(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $siteA = $this->siteForOwner($owner);
        $siteB = $this->siteForOwner($owner);
        $phoneA = ContactEntry::factory()->for($siteA)->phone()->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('toggleSelected', $phoneA->id)
            ->assertSet('selected', [$phoneA->id])
            ->call('selectAllMatching')
            ->assertSet('selectAllMatching', true)
            // Site is a scoping filter within a type → explicit pick survives.
            ->set('siteFilter', (string) $siteB->id)
            ->assertSet('selected', [$phoneA->id])
            ->assertSet('selectAllMatching', false)
            // Type is the entity context → switching it drops the whole selection.
            ->set('typeFilter', 'messenger')
            ->assertSet('selected', [])
            ->assertSet('selectAllMatching', false);
    }

    public function test_select_all_filtered_action_selects_all_matching_messengers(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->messenger('telegram')->count(8)->create();
        ContactEntry::factory()->for($site)->phone()->count(2)->create();

        $ids = ContactEntry::where('type', 'messenger')->limit(2)->pluck('id')->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'messenger'])
            ->call('selectPage', $ids)
            ->assertSet('selectAllMatching', false)
            ->call('selectAllFiltered')
            ->assertSet('selectAllMatching', true)
            ->assertSee('усі 8');
    }

    public function test_custom_messenger_kind_is_available_in_data_browser_filter(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->messenger('max')->create(['value' => '@max_support']);
        ContactEntry::factory()->for($site)->messenger('telegram')->create(['value' => '@telegram_support']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'messenger'])
            ->assertSee('Max')
            ->set('kindFilter', 'max')
            ->assertSee('@max_support')
            ->assertDontSee('@telegram_support');
    }

    public function test_bulk_delete_soft_deletes_selected_and_dispatches_undo(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(3)
            ->create()->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('bulkDelete')
            ->assertSet('selected', [])
            ->assertDispatched('toast', fn ($event, $params) => ($params['action'] ?? null) === 'bulkRestore'
                && $params['actionData']['ids'] === $ids);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('contact_entries', ['id' => $id]);
        }
    }

    public function test_bulk_restore_undoes_a_delete(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(3)
            ->create()->pluck('id')->map(fn ($i) => (int) $i)->all();
        ContactEntry::whereIn('id', $ids)->delete();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('bulkRestore', $ids);

        $this->assertSame(3, ContactEntry::whereIn('id', $ids)->count());
    }

    public function test_bulk_set_visible_hides_selected(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(2)
            ->create(['visible' => true])->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('bulkSetVisible', false)
            ->assertSet('selected', []);

        $this->assertSame(2, ContactEntry::whereIn('id', $ids)->where('visible', false)->count());
    }

    public function test_bulk_replace_value_sets_all_and_undo_restores_each(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        // Two different numbers on two different sites — the headline use case.
        $a = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+111']);
        $b = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+222']);

        $component = Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', [(int) $a->id, (int) $b->id])
            ->call('openEdit', 'value')
            ->assertSet('editingField', true)
            ->set('editValue', '+48 000')
            ->call('applyEdit')
            ->assertSet('editingField', false)
            ->assertSet('selected', [])
            ->assertDispatched('toast', fn ($e, $p) => ($p['action'] ?? null) === 'bulkRestoreField'
                && ($p['actionData']['field'] ?? null) === 'value');

        $this->assertSame('+48 000', $a->fresh()->value);
        $this->assertSame('+48 000', $b->fresh()->value);

        // Undo writes each row's *own* previous value back.
        $component->call('bulkRestoreField', [$a->id => '+111', $b->id => '+222'], 'value');
        $this->assertSame('+111', $a->fresh()->value);
        $this->assertSame('+222', $b->fresh()->value);
    }

    public function test_bulk_change_label_on_selected(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(2)
            ->create(['label' => 'old'])->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('openEdit', 'label')
            ->set('editValue', 'Підписка · Pro')
            ->call('applyEdit')
            ->assertSet('editingField', false);

        $this->assertSame(2, ContactEntry::whereIn('id', $ids)->where('label', 'Підписка · Pro')->count());
    }

    public function test_replace_value_requires_non_empty(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+1']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', [(int) $e->id])
            ->call('openEdit', 'value')
            ->set('editValue', '   ')
            ->call('applyEdit')
            ->assertSet('editingField', true) // stays open on validation error
            ->assertDispatched('toast', fn ($ev, $p) => ($p['type'] ?? null) === 'error');

        $this->assertSame('+1', $e->fresh()->value);
    }

    public function test_site_filter_scopes_select_all_matching(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $siteA = $this->siteForOwner($owner);
        $siteB = $this->siteForOwner($owner);
        $aIds = ContactEntry::factory()->for($siteA)->phone()->count(3)->create()->pluck('id')->all();
        ContactEntry::factory()->for($siteB)->phone()->count(4)->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('siteFilter', (string) $siteA->id)
            ->call('selectAllMatching')
            ->call('bulkDelete');

        $this->assertSame(0, ContactEntry::whereIn('id', $aIds)->count(), 'site A phones deleted');
        $this->assertSame(4, ContactEntry::where('site_id', $siteB->id)->count(), 'site B untouched');
    }

    public function test_export_returns_a_csv_download(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('export')
            ->assertFileDownloaded();
    }

    public function test_review_drawer_lists_selection_and_removing_trims_it(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(3)
            ->create()->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('openReview')
            ->assertSet('reviewingSelection', true)
            ->call('toggleSelected', $ids[0])  // remove one from within the review
            ->assertSet('selected', [$ids[1], $ids[2]])
            ->call('closeReview')
            ->assertSet('reviewingSelection', false);
    }

    public function test_replace_substring_only_changes_matching_rows_and_undo_restores(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $match = ContactEntry::factory()->for($site)->phone()->create(['value' => '+380501112233']);
        $other = ContactEntry::factory()->for($site)->phone()->create(['value' => '+48111222333']);

        $component = Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', [(int) $match->id, (int) $other->id])
            ->call('openReplace')
            ->set('findText', '+380')
            ->set('replaceText', '+48')
            ->call('applyReplace')
            ->assertSet('editingReplace', false)
            ->assertSet('selected', [])
            ->assertDispatched('toast', fn ($e, $p) => ($p['action'] ?? null) === 'bulkRestoreField');

        $this->assertSame('+48501112233', $match->fresh()->value); // +380 → +48
        $this->assertSame('+48111222333', $other->fresh()->value); // no +380 → untouched

        // Undo restores only the changed row to its exact previous value.
        $component->call('bulkRestoreField', [$match->id => '+380501112233'], 'value');
        $this->assertSame('+380501112233', $match->fresh()->value);
    }

    public function test_replace_substring_with_no_match_keeps_drawer_open(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+48999']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', [(int) $e->id])
            ->call('openReplace')
            ->set('findText', '+380')
            ->set('replaceText', '+48')
            ->call('applyReplace')
            ->assertSet('editingReplace', true) // nothing matched → stays open
            ->assertDispatched('toast', fn ($ev, $p) => ($p['type'] ?? null) === 'error');

        $this->assertSame('+48999', $e->fresh()->value);
    }

    public function test_replace_substring_requires_find_text(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+1']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', [(int) $e->id])
            ->call('openReplace')
            ->set('findText', '')
            ->call('applyReplace')
            ->assertSet('editingReplace', true)
            ->assertDispatched('toast', fn ($ev, $p) => ($p['type'] ?? null) === 'error');

        $this->assertSame('+1', $e->fresh()->value);
    }

    public function test_select_all_matching_acts_on_whole_filtered_set_not_just_page(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        // 20 phones spans two pages (15/page); 5 prices must survive the phone-only bulk delete.
        $phoneIds = ContactEntry::factory()->for($site)->phone()->count(20)->create()->pluck('id')->all();
        ContactEntry::factory()->for($site)->price()->count(5)->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('typeFilter', 'phone')
            ->call('selectAllMatching')
            ->assertSet('selectAllMatching', true)
            ->call('bulkDelete')
            ->assertSet('selectAllMatching', false);

        $this->assertSame(0, ContactEntry::whereIn('id', $phoneIds)->count(), 'all matching phones deleted');
        $this->assertSame(5, ContactEntry::where('type', 'price')->count(), 'prices untouched');
    }

    public function test_manager_bulk_delete_is_blocked_and_warns(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $manager = User::factory()->create(['role' => 'manager']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(3)
            ->create()->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($manager)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('bulkDelete')
            ->assertSet('selected', [])
            ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'error');

        $this->assertSame(3, ContactEntry::whereIn('id', $ids)->count(), 'nothing deleted');
    }

    /**
     * IDOR regression: a non-owner/admin user must only ever read entries they
     * own (site.client.user_id). The browser list, totalCount and the CSV
     * export all share the scoped bulkQuery().
     */
    public function test_manager_browse_and_count_exclude_unowned_entries(): void
    {
        $owner   = User::factory()->create(['role' => 'owner']);
        $manager = User::factory()->create(['role' => 'manager']);

        // Owner's entry — forbidden to the manager.
        ContactEntry::factory()->for($this->siteForOwner($owner))->phone()
            ->create(['value' => '+OWNER-SECRET']);

        // Manager's own entry — visible to the manager.
        $own = ContactEntry::factory()->for($this->siteForOwner($manager))->phone()
            ->create(['value' => '+MANAGER-OWN']);

        Livewire::actingAs($manager)
            ->test(DataBrowser::class)
            ->assertViewHas('totalCount', 1)
            ->assertViewHas('entries', fn ($entries) => $entries->total() === 1
                && (int) $entries->first()->id === (int) $own->id)
            ->assertDontSee('+OWNER-SECRET');
    }

    /**
     * IDOR regression: crafting another user's ids into the selection must not
     * leak them through the review/preview drawers (explicit-pick reads are
     * scoped too).
     */
    public function test_manager_cannot_read_unowned_entries_via_crafted_selection(): void
    {
        $owner   = User::factory()->create(['role' => 'owner']);
        $manager = User::factory()->create(['role' => 'manager']);

        $ownerEntry = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()
            ->create(['value' => '+OWNER-SECRET']);

        Livewire::actingAs($manager)
            ->test(DataBrowser::class)
            ->call('selectPage', [(int) $ownerEntry->id])
            ->call('openReview')
            ->assertSet('reviewingSelection', true)
            ->assertViewHas('reviewItems', fn ($items) => $items->isEmpty())
            ->assertDontSee('+OWNER-SECRET');
    }

    public function test_replace_value_is_blocked_for_mixed_type_selection(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $ids = [
            (int) ContactEntry::factory()->for($site)->phone()->create()->id,
            (int) ContactEntry::factory()->for($site)->messenger()->create()->id,
        ];

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('openEdit', 'value')
            ->assertSet('editingField', false) // value semantics differ per type → refused
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');
    }

    public function test_replace_substring_is_blocked_for_mixed_type_selection(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $ids = [
            (int) ContactEntry::factory()->for($site)->phone()->create()->id,
            (int) ContactEntry::factory()->for($site)->messenger()->create()->id,
        ];

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('openReplace')
            ->assertSet('editingReplace', false)
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');
    }

    public function test_label_edit_is_allowed_for_mixed_type_selection(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $ids = [
            (int) ContactEntry::factory()->for($site)->phone()->create()->id,
            (int) ContactEntry::factory()->for($site)->messenger()->create()->id,
        ];

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('openEdit', 'label')
            ->assertSet('editingField', true); // label is a free annotation, type-agnostic
    }

    public function test_bulk_role_hides_selected(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(2)
            ->create(['role' => 'primary', 'visible' => true])->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('openRole')
            ->assertSet('editingRole', true)
            ->set('roleValue', 'hidden')
            ->call('applyRole')
            ->assertSet('editingRole', false)
            ->assertDispatched('toast', fn ($e, $p) => ($p['action'] ?? null) === 'bulkRestoreRole');

        // hidden role and visibility move together (canonical role = primary|backup|hidden).
        $this->assertSame(2, ContactEntry::whereIn('id', $ids)->where('role', 'hidden')->where('visible', false)->count());
    }

    public function test_bulk_role_primary_detaches_reserve_and_keeps_geo(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $primary = ContactEntry::factory()->for($site)->phone()->create([
            'role' => 'primary', 'geo_tag' => 'UA', 'geo_mode' => 'except', 'countries' => ['PL'],
        ]);
        $reserve = ContactEntry::factory()->backup($primary)->create([
            'geo_tag' => null, 'geo_mode' => 'all', 'countries' => null,
        ]);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', [(int) $reserve->id])
            ->call('openRole')
            ->set('roleValue', 'primary')
            ->call('applyRole')
            ->assertSet('editingRole', false);

        $reserve->refresh();
        // Promoting a reserve detaches it and captures the geo it had inherited.
        $this->assertNull($reserve->parent_id);
        $this->assertSame('primary', $reserve->role);
        $this->assertSame('UA', $reserve->geo_tag);
        $this->assertSame('except', $reserve->geo_mode);
        $this->assertSame(['PL'], $reserve->countries);
    }

    public function test_bulk_role_rejects_backup_value(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['role' => 'primary']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', [(int) $e->id])
            ->call('openRole')
            ->set('roleValue', 'backup') // needs a parent → not a bulk role
            ->call('applyRole')
            ->assertSet('editingRole', true)
            ->assertDispatched('toast', fn ($ev, $p) => ($p['type'] ?? null) === 'error');

        $this->assertSame('primary', $e->fresh()->role);
    }

    public function test_role_filter_scopes_the_list(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $primary = ContactEntry::factory()->for($site)->phone()->create(['role' => 'primary', 'value' => '+PRIMARY']);
        $reserve = ContactEntry::factory()->backup($primary)->create(['value' => '+RESERVE']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('typeFilter', 'phone')
            ->set('roleFilter', 'backup')
            ->assertViewHas('entries', fn ($e) => $e->total() === 1 && (int) $e->first()->id === (int) $reserve->id)
            ->assertSee('+RESERVE')
            ->assertDontSee('+PRIMARY');
    }

    public function test_replace_value_is_blocked_for_mixed_messenger_kinds(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        // A Viber link is not a Telegram link — different entity, same type.
        $ids = [
            (int) ContactEntry::factory()->for($site)->messenger('telegram')->create()->id,
            (int) ContactEntry::factory()->for($site)->messenger('viber')->create()->id,
        ];

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('openEdit', 'value')
            ->assertSet('editingField', false)
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');
    }

    public function test_replace_value_is_allowed_for_single_messenger_kind(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $ids = ContactEntry::factory()->for($site)->messenger('telegram')->count(2)
            ->create()->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('openEdit', 'value')
            ->assertSet('editingField', true) // same kind → allowed
            ->set('editValue', '@newhandle')
            ->call('applyEdit')
            ->assertSet('editingField', false);

        $this->assertSame(2, ContactEntry::whereIn('id', $ids)->where('value', '@newhandle')->count());
    }

    public function test_kind_filter_scopes_select_all_matching(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $tgIds = ContactEntry::factory()->for($site)->messenger('telegram')->count(3)->create()->pluck('id')->all();
        ContactEntry::factory()->for($site)->messenger('viber')->count(2)->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('typeFilter', 'messenger')
            ->set('kindFilter', 'telegram')
            ->call('selectAllMatching')
            ->call('bulkDelete');

        $this->assertSame(0, ContactEntry::whereIn('id', $tgIds)->count(), 'telegrams deleted');
        $this->assertSame(2, ContactEntry::where('kind', 'viber')->count(), 'vibers untouched');
    }

    public function test_changing_kind_clears_selection_and_type_change_resets_kind(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $tg = ContactEntry::factory()->for($site)->messenger('telegram')->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('typeFilter', 'messenger')
            ->set('kindFilter', 'telegram')
            ->call('toggleSelected', $tg->id)
            ->assertSet('selected', [$tg->id])
            ->set('kindFilter', 'viber')      // kind = entity → selection cleared
            ->assertSet('selected', [])
            ->set('kindFilter', 'telegram')
            ->call('toggleSelected', $tg->id)
            ->set('typeFilter', 'phone')      // type change resets the kind sub-filter
            ->assertSet('kindFilter', '')
            ->assertSet('selected', []);
    }

    public function test_trash_mode_lists_only_trashed_entries(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+LIVE']);
        $dead = ContactEntry::factory()->for($site)->phone()->create(['value' => '+DEAD']);
        $dead->delete();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('trashed', true)
            ->assertViewHas('entries', fn ($e) => $e->total() === 1 && (int) $e->first()->id === (int) $dead->id)
            ->assertSee('+DEAD')
            ->assertDontSee('+LIVE');
    }

    public function test_restore_selected_from_trash(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $ids = ContactEntry::factory()->for($site)->phone()->count(2)->create()->pluck('id')->map(fn ($i) => (int) $i)->all();
        ContactEntry::whereIn('id', $ids)->delete();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('trashed', true)
            ->call('selectPage', $ids)
            ->call('restoreSelected')
            ->assertSet('selected', []);

        $this->assertSame(2, ContactEntry::whereIn('id', $ids)->count(), 'back to active');
    }

    public function test_purge_selected_permanently_deletes(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $ids = ContactEntry::factory()->for($site)->phone()->count(2)->create()->pluck('id')->map(fn ($i) => (int) $i)->all();
        ContactEntry::whereIn('id', $ids)->delete();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('trashed', true)
            ->call('selectPage', $ids)
            ->call('purgeSelected')
            ->assertSet('selected', []);

        $this->assertSame(0, ContactEntry::withTrashed()->whereIn('id', $ids)->count(), 'gone for good');
    }

    public function test_switching_to_trash_clears_selection(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('toggleSelected', $e->id)
            ->assertSet('selected', [$e->id])
            ->set('trashed', true)
            ->assertSet('selected', []);
    }

    public function test_review_in_trash_resolves_selected_trashed_entries(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+TRASHED']);
        $e->delete();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('trashed', true)
            ->call('selectPage', [(int) $e->id])
            ->call('openReview')
            ->assertViewHas('reviewItems', fn ($items) => $items->count() === 1
                && (int) $items->first()->id === (int) $e->id)
            ->assertSee('+TRASHED');
    }

    public function test_bulk_geo_only_sets_mode_and_countries(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(2)
            ->create(['geo_mode' => 'all', 'countries' => null])->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', $ids)
            ->call('openGeo')
            ->assertSet('editingGeo', true)
            ->set('geoMode', 'only')
            ->set('geoCountries', 'ua, pl')   // lowercase / spacing normalised
            ->call('applyGeo')
            ->assertSet('editingGeo', false)
            ->assertSet('selected', []);

        $e = ContactEntry::find($ids[0]);
        $this->assertSame('only', $e->geo_mode);
        $this->assertSame(['UA', 'PL'], $e->countries);
    }

    public function test_bulk_geo_all_clears_countries(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $id = (int) ContactEntry::factory()->for($this->siteForOwner($owner))->phone()
            ->create(['geo_mode' => 'only', 'countries' => ['UA']])->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', [$id])
            ->call('openGeo')
            ->set('geoMode', 'all')
            ->call('applyGeo')
            ->assertSet('editingGeo', false);

        $e = ContactEntry::find($id);
        $this->assertSame('all', $e->geo_mode);
        $this->assertNull($e->countries);
    }

    public function test_bulk_geo_requires_a_country_for_only(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $id = (int) ContactEntry::factory()->for($this->siteForOwner($owner))->phone()
            ->create(['geo_mode' => 'all'])->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', [$id])
            ->call('openGeo')
            ->set('geoMode', 'only')
            ->set('geoCountries', '')
            ->call('applyGeo')
            ->assertSet('editingGeo', true) // stays open on validation error
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');

        $this->assertSame('all', ContactEntry::find($id)->geo_mode);
    }

    public function test_bulk_geo_undo_restores_previous(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $entry = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()
            ->create(['geo_mode' => 'except', 'countries' => ['RU']]);

        $component = Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->call('selectPage', [(int) $entry->id])
            ->call('openGeo')
            ->set('geoMode', 'only')
            ->set('geoCountries', 'UA')
            ->call('applyGeo')
            ->assertDispatched('toast', fn ($e, $p) => ($p['action'] ?? null) === 'bulkRestoreGeo');

        $this->assertSame('only', $entry->fresh()->geo_mode);

        $component->call('bulkRestoreGeo', [$entry->id => ['geo_mode' => 'except', 'countries' => ['RU'], 'geo_tag' => null]]);
        $this->assertSame('except', $entry->fresh()->geo_mode);
        $this->assertSame(['RU'], $entry->fresh()->countries);
    }

    public function test_bulk_price_edit_changes_currency_and_undo_restores(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $a = ContactEntry::factory()->for($site)->price()->create(['currency' => 'EUR']);
        $b = ContactEntry::factory()->for($site)->price()->create(['currency' => 'USD']);

        $component = Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('typeFilter', 'price')
            ->call('selectPage', [(int) $a->id, (int) $b->id])
            ->call('openPriceEdit')
            ->assertSet('editingPrice', true)
            ->set('priceField', 'currency')
            ->set('priceValue', 'PLN')
            ->call('applyPriceEdit')
            ->assertSet('editingPrice', false)
            ->assertDispatched('toast', fn ($e, $p) => ($p['action'] ?? null) === 'bulkRestoreField'
                && ($p['actionData']['field'] ?? null) === 'currency');

        $this->assertSame('PLN', $a->fresh()->currency);
        $this->assertSame('PLN', $b->fresh()->currency);

        // Undo writes each row's own previous currency back.
        $component->call('bulkRestoreField', [$a->id => 'EUR', $b->id => 'USD'], 'currency');
        $this->assertSame('EUR', $a->fresh()->currency);
        $this->assertSame('USD', $b->fresh()->currency);
    }

    public function test_bulk_price_edit_clears_old_price_when_empty(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $id = (int) ContactEntry::factory()->for($site)->price()->create(['old_price' => 99])->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('typeFilter', 'price')
            ->call('selectPage', [$id])
            ->call('openPriceEdit')
            ->set('priceField', 'old_price')
            ->set('priceValue', '')   // empty numeric = clear
            ->call('applyPriceEdit')
            ->assertSet('editingPrice', false);

        $this->assertNull(ContactEntry::find($id)->old_price);
    }

    public function test_bulk_price_edit_rejects_unknown_currency(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $id = (int) ContactEntry::factory()->for($this->siteForOwner($owner))->price()->create(['currency' => 'EUR'])->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('typeFilter', 'price')
            ->call('selectPage', [$id])
            ->call('openPriceEdit')
            ->set('priceField', 'currency')
            ->set('priceValue', 'XYZ')
            ->call('applyPriceEdit')
            ->assertSet('editingPrice', true) // stays open on validation error
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');

        $this->assertSame('EUR', ContactEntry::find($id)->currency);
    }

    public function test_price_edit_is_unavailable_for_non_price_types(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class)
            ->set('typeFilter', 'phone')
            ->call('selectPage', [(int) $e->id])
            ->call('openPriceEdit')
            ->assertSet('editingPrice', false); // guarded — price fields only for the price type
    }
}
