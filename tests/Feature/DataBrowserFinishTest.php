<?php

namespace Tests\Feature;

use App\Livewire\DataBrowser;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Closing the Data Browser phase: step-by-step confirm on the price and
 * find-replace drawers, the "за сайтом" failover-group view with ↑↓ reorder,
 * and the social/address types flowing through the same two-pane.
 */
class DataBrowserFinishTest extends TestCase
{
    use RefreshDatabase;

    private function siteForOwner(User $owner, array $categories = null): Site
    {
        $site = Site::factory()->for(Client::factory()->for($owner))->create();
        if ($categories !== null) {
            $site->forceFill(['data_categories' => $categories])->save();
        }

        return $site;
    }

    // ── DB-T04b: price & replace are two-step (enter → confirm) ───────────

    public function test_price_edit_two_step_confirm_applies(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->price()->count(2)
            ->create(['currency' => 'EUR'])->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'price'])
            ->call('selectPage', $ids)
            ->call('openPriceEdit')
            ->assertSet('priceStep', 1)
            ->set('priceField', 'currency')
            ->set('priceValue', 'PLN')
            ->call('priceConfirm')
            ->assertSet('priceStep', 2)
            ->call('priceBack')
            ->assertSet('priceStep', 1)
            ->call('priceConfirm')
            ->call('applyPriceEdit')
            ->assertSet('editingPrice', false);

        $this->assertSame(2, ContactEntry::whereIn('id', $ids)->where('currency', 'PLN')->count());
    }

    public function test_price_confirm_rejects_bad_currency_and_stays_on_step_one(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $id = (int) ContactEntry::factory()->for($this->siteForOwner($owner))->price()->create(['currency' => 'EUR'])->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'price'])
            ->call('selectPage', [$id])
            ->call('openPriceEdit')
            ->set('priceField', 'currency')
            ->set('priceValue', 'XYZ')
            ->call('priceConfirm')
            ->assertSet('priceStep', 1)
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');
    }

    public function test_replace_two_step_confirm_applies(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $m = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+380501112233']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse'])
            ->call('selectPage', [(int) $m->id])
            ->call('openReplace')
            ->assertSet('replaceStep', 1)
            ->set('findText', '+380')
            ->set('replaceText', '+48')
            ->call('replaceConfirm')
            ->assertSet('replaceStep', 2)
            ->call('applyReplace')
            ->assertSet('editingReplace', false);

        $this->assertSame('+48501112233', $m->fresh()->value);
    }

    public function test_replace_confirm_requires_find_text(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse'])
            ->call('selectPage', [(int) $e->id])
            ->call('openReplace')
            ->set('findText', '')
            ->call('replaceConfirm')
            ->assertSet('replaceStep', 1)
            ->assertDispatched('toast', fn ($e2, $p) => ($p['type'] ?? null) === 'error');
    }

    // ── DB-T05: "за сайтом" failover groups + ↑↓ reorder ─────────────────

    public function test_site_axis_lists_failover_groups_with_reserves(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $p = ContactEntry::factory()->for($site)->phone()->create(['value' => '+MAIN']);
        ContactEntry::factory()->backup($p)->create(['value' => '+R1', 'order' => 1]);
        ContactEntry::factory()->backup($p)->create(['value' => '+R2', 'order' => 2]);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->set('axis', 'site')
            ->set('siteFilter', (string) $site->id)
            ->assertSee('+MAIN')
            ->assertSee('+R1')
            ->assertSee('+R2')
            ->assertSee('резерв');
    }

    public function test_switching_to_site_axis_auto_picks_a_site_so_groups_show_at_once(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+MAIN']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->assertSet('siteFilter', '')
            ->set('axis', 'site')
            ->assertSet('siteFilter', (string) $site->id) // auto-picked — no extra click
            ->assertSee('+MAIN');                          // failover view shows immediately
    }

    public function test_reorder_reserve_up_swaps_order_and_logs_once(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $p = ContactEntry::factory()->for($site)->phone()->create();
        $r1 = ContactEntry::factory()->backup($p)->create(['order' => 1]);
        $r2 = ContactEntry::factory()->backup($p)->create(['order' => 2]);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->set('axis', 'site')
            ->set('siteFilter', (string) $site->id)
            ->call('reorderReserve', $r2->id, 'up');

        $this->assertSame(1, $r2->fresh()->order);
        $this->assertSame(2, $r1->fresh()->order);

        $log = ActivityLog::where('action', 'entry.reordered')->first();
        $this->assertNotNull($log);
        $this->assertSame('reserves', $log->properties['scope'] ?? null);
    }

    public function test_reorder_reserve_at_the_edge_is_a_noop_without_a_log(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $p = ContactEntry::factory()->for($site)->phone()->create();
        $r1 = ContactEntry::factory()->backup($p)->create(['order' => 1]);
        ContactEntry::factory()->backup($p)->create(['order' => 2]);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse'])
            ->call('reorderReserve', $r1->id, 'up'); // already first

        $this->assertSame(1, $r1->fresh()->order);
        $this->assertSame(0, ActivityLog::where('action', 'entry.reordered')->count());
    }

    public function test_manager_cannot_reorder_an_unowned_reserve(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $manager = User::factory()->create(['role' => 'manager', 'access_scope' => 'limited', 'site_access' => []]);
        $site = $this->siteForOwner($owner);
        $p = ContactEntry::factory()->for($site)->phone()->create();
        $r1 = ContactEntry::factory()->backup($p)->create(['order' => 1]);
        $r2 = ContactEntry::factory()->backup($p)->create(['order' => 2]);

        Livewire::actingAs($manager)
            ->test(DataBrowser::class, ['mode' => 'browse'])
            ->call('reorderReserve', $r2->id, 'up')
            ->assertDispatched('toast', fn ($e, $pp) => ($pp['type'] ?? null) === 'error');

        $this->assertSame(1, $r1->fresh()->order); // untouched
        $this->assertSame(0, ActivityLog::where('action', 'entry.reordered')->count());
    }

    // ── Left rail filter + "add reserves to a primary" ──────────────────

    public function test_left_rail_role_filter_narrows_value_groups_to_reserves(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $primary = ContactEntry::factory()->for($site)->phone()->create(['value' => '+MAIN']);
        ContactEntry::factory()->backup($primary)->create(['value' => '+RES']);

        $groups = collect(Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->set('roleFilter', 'backup')
            ->viewData('valueGroups'));

        $this->assertTrue($groups->contains(fn ($g) => $g->gkey === '+RES'));
        $this->assertFalse($groups->contains(fn ($g) => $g->gkey === '+MAIN'));
    }

    public function test_add_reserve_creates_typed_backups_for_a_primary(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $primary = ContactEntry::factory()->for($site)->phone()->create(['value' => '+MAIN']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('openAddReserveFor', $primary->id)
            ->assertSet('addingReserve', true)
            ->set('reserveNumbers', "+R1\n+R2")
            ->call('applyAddReserve')
            ->assertSet('addingReserve', false)
            ->assertDispatched('toast', fn ($e, $p) => ($p['action'] ?? null) === 'bulkPurgeCreated');

        $backups = ContactEntry::where('parent_id', $primary->id)->where('role', 'backup')->pluck('value')->all();
        $this->assertCount(2, $backups);
        $this->assertContains('+R1', $backups);
        $this->assertContains('+R2', $backups);
    }

    public function test_add_reserve_bottom_bar_targets_the_single_selected_primary(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $primary = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+MAIN', 'role' => 'primary']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('selectPage', [(int) $primary->id])
            ->call('openAddReserve')
            ->assertSet('addingReserve', true)
            ->assertSet('reserveParentId', $primary->id)
            ->assertSet('attaching', false);
    }

    public function test_add_reserve_requires_at_least_one_number(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $primary = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('openAddReserveFor', $primary->id)
            ->set('reserveNumbers', "  \n ")
            ->call('applyAddReserve')
            ->assertSet('addingReserve', true)
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');
    }

    // ── Inline cell editing (spreadsheet-style direct edit) ─────────────

    public function test_inline_update_changes_a_single_value(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+OLD']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('inlineUpdate', $e->id, 'value', '+NEW')
            ->assertDispatched('toast', fn ($ev, $p) => ($p['type'] ?? null) === 'success');

        $this->assertSame('+NEW', $e->fresh()->value);
    }

    public function test_inline_update_label_blank_clears_it(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['label' => 'old']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse'])
            ->call('inlineUpdate', $e->id, 'label', '   ');

        $this->assertNull($e->fresh()->label);
    }

    public function test_inline_update_rejects_empty_value(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+KEEP']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse'])
            ->call('inlineUpdate', $e->id, 'value', '  ')
            ->assertDispatched('toast', fn ($ev, $p) => ($p['type'] ?? null) === 'error');

        $this->assertSame('+KEEP', $e->fresh()->value);
    }

    public function test_inline_update_silently_denies_an_unowned_entry(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $manager = User::factory()->create(['role' => 'manager', 'access_scope' => 'limited', 'site_access' => []]);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+SECRET']);

        Livewire::actingAs($manager)
            ->test(DataBrowser::class, ['mode' => 'browse'])
            ->call('inlineUpdate', $e->id, 'value', '+HACK');

        $this->assertSame('+SECRET', $e->fresh()->value); // out of scope → no-op, no leak
    }

    // ── Generic "change any field" (set / clear / find-replace) ──────────

    public function test_generic_set_changes_a_field_with_undo(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(2)
            ->create(['label' => 'old'])->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('selectPage', $ids)
            ->call('openGeneric')
            ->assertSet('editingGeneric', true)
            ->set('genField', 'label')
            ->set('genOp', 'set')
            ->set('genValue', 'NEW')
            ->call('genericConfirm')
            ->assertSet('genStep', 2)
            ->call('applyGeneric')
            ->assertSet('editingGeneric', false)
            ->assertDispatched('toast', fn ($e, $p) => ($p['action'] ?? null) === 'bulkRestoreField' && ($p['actionData']['field'] ?? null) === 'label');

        $this->assertSame(2, ContactEntry::whereIn('id', $ids)->where('label', 'NEW')->count());
    }

    public function test_generic_clear_nulls_a_price_field(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $id = (int) ContactEntry::factory()->for($this->siteForOwner($owner))->price()->create(['old_price' => 99])->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'price'])
            ->call('selectPage', [$id])
            ->call('openGeneric')
            ->set('genField', 'old_price')
            ->set('genOp', 'clear')
            ->call('genericConfirm')
            ->call('applyGeneric');

        $this->assertNull(ContactEntry::find($id)->old_price);
    }

    public function test_generic_replace_changes_only_matching_rows(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $m = ContactEntry::factory()->for($site)->phone()->create(['value' => '+380501112233']);
        $o = ContactEntry::factory()->for($site)->phone()->create(['value' => '+48111222333']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('selectPage', [(int) $m->id, (int) $o->id])
            ->call('openGeneric')
            ->set('genField', 'value')
            ->set('genOp', 'replace')
            ->set('genFind', '+380')
            ->set('genValue', '+48')
            ->call('genericConfirm')
            ->call('applyGeneric');

        $this->assertSame('+48501112233', $m->fresh()->value);
        $this->assertSame('+48111222333', $o->fresh()->value); // no +380 → untouched
    }

    public function test_generic_field_switch_snaps_an_invalid_operation(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('selectPage', [(int) $e->id])
            ->call('openGeneric')
            ->set('genField', 'label')
            ->set('genOp', 'clear')
            ->set('genField', 'value')   // value has no "clear" → snaps to "set"
            ->assertSet('genOp', 'set');
    }

    public function test_generic_set_currency_validates(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $id = (int) ContactEntry::factory()->for($this->siteForOwner($owner))->price()->create(['currency' => 'EUR'])->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'price'])
            ->call('selectPage', [$id])
            ->call('openGeneric')
            ->set('genField', 'currency')
            ->set('genOp', 'set')
            ->set('genValue', 'XYZ')
            ->call('genericConfirm')
            ->assertSet('genStep', 1)   // blocked on validation
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');
    }

    // ── Column sorting (click a header) ─────────────────────────────────

    public function test_sort_by_value_orders_occurrences(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+BBB']);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+AAA']);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+CCC']);

        $entries = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('sortBy', 'value')
            ->assertSet('sortField', 'value')
            ->assertSet('sortDir', 'asc')
            ->viewData('entries');

        $this->assertSame(['+AAA', '+BBB', '+CCC'], collect($entries->items())->pluck('value')->all());
    }

    public function test_sort_same_column_toggles_direction(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('sortBy', 'value')->assertSet('sortDir', 'asc')
            ->call('sortBy', 'value')->assertSet('sortDir', 'desc')
            ->call('sortBy', 'label')->assertSet('sortField', 'label')->assertSet('sortDir', 'asc');
    }

    public function test_sort_by_site_orders_by_site_name(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($owner)->create();
        $zeta = Site::factory()->for($client)->create(['name' => 'zeta']);
        $alpha = Site::factory()->for($client)->create(['name' => 'alpha']);
        ContactEntry::factory()->for($zeta)->phone()->create(['value' => '+Z']);
        ContactEntry::factory()->for($alpha)->phone()->create(['value' => '+A']);

        $entries = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('sortBy', 'site')
            ->viewData('entries');

        $this->assertSame('+A', collect($entries->items())->first()->value); // alpha site sorts first
    }

    // ── Inline price editing ────────────────────────────────────────────

    public function test_inline_update_changes_a_price_amount(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->price()->create(['price' => 100]);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'price'])
            ->call('inlineUpdate', $e->id, 'price', '149.5')
            ->assertDispatched('toast', fn ($ev, $p) => ($p['type'] ?? null) === 'success');

        $this->assertSame(149.5, (float) $e->fresh()->price);
    }

    public function test_inline_update_price_rejects_non_numeric(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->price()->create(['price' => 100]);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'price'])
            ->call('inlineUpdate', $e->id, 'price', 'abc')
            ->assertDispatched('toast', fn ($ev, $p) => ($p['type'] ?? null) === 'error');

        $this->assertSame(100.0, (float) $e->fresh()->price);
    }

    public function test_inline_update_is_undoable(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $e = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create(['value' => '+OLD']);

        $component = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('inlineUpdate', $e->id, 'value', '+NEW')
            ->assertDispatched('toast', fn ($ev, $p) => ($p['action'] ?? null) === 'bulkRestoreField'
                && ($p['actionData']['field'] ?? null) === 'value');

        $this->assertSame('+NEW', $e->fresh()->value);

        $component->call('bulkRestoreField', [$e->id => '+OLD'], 'value');
        $this->assertSame('+OLD', $e->fresh()->value); // undo restores
    }

    public function test_context_header_shows_count_and_picked_value(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(3)->create(['value' => '+SAME']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->assertSee('записів')          // count label in the context header
            ->call('pickValue', '+SAME', '')
            ->assertSee('значення')         // picked-value chip
            ->assertSee('+SAME');
    }

    public function test_groups_occurrences_by_site_by_default(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($owner)->create();
        $zeta = Site::factory()->for($client)->create(['name' => 'zeta']);
        $alpha = Site::factory()->for($client)->create(['name' => 'alpha']);
        ContactEntry::factory()->for($zeta)->phone()->create(['value' => '+SAME']);
        ContactEntry::factory()->for($alpha)->phone()->create(['value' => '+SAME']);

        $component = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->assertSet('groupBySite', true)
            ->call('pickValue', '+SAME', '');

        // grouped → ordered by site name, alpha first
        $entries = $component->viewData('entries');
        $this->assertSame($alpha->id, (int) collect($entries->items())->first()->site_id);
        $component->assertSee('alpha')->assertSee('zeta'); // group headers
    }

    // ── Bulk-change wizard (Jira-style) ─────────────────────────────────

    public function test_bulk_wizard_routes_to_a_chosen_operation(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(2)
            ->create()->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('selectPage', $ids)
            ->call('openBulkWizard')
            ->assertSet('bulkWizard', true)
            ->call('wizardTo', 'geo')
            ->assertSet('bulkWizard', false)
            ->assertSet('editingGeo', true); // routed into the geo flow
    }

    public function test_bulk_wizard_delete_soft_deletes_selected(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ids = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->count(2)
            ->create()->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse'])
            ->call('selectPage', $ids)
            ->call('wizardTo', 'delete')
            ->assertSet('bulkWizard', false);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('contact_entries', ['id' => $id]);
        }
    }

    // ── Stale data: entries on deleted sites must not surface ────────────

    public function test_entries_on_deleted_sites_are_hidden(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($owner)->create();
        $live = Site::factory()->for($client)->create();
        $dead = Site::factory()->for($client)->create();
        ContactEntry::factory()->for($live)->phone()->create(['value' => '+LIVE']);
        ContactEntry::factory()->for($dead)->phone()->create(['value' => '+DEADSITE']);
        $dead->delete(); // soft-delete the site — its entries stay in the table

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->assertViewHas('totalCount', 1)   // only the live site's entry counts
            ->assertSee('+LIVE')
            ->assertDontSee('+DEADSITE');
    }

    // ── DB-T07: social & address flow through the same two-pane ──────────

    public function test_social_type_value_axis_lists_links(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner, ['phones', 'messengers', 'socials']);
        ContactEntry::factory()->for($site)->social('instagram')->create(['value' => 'https://insta/acme']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'social'])
            ->assertSet('typeFilter', 'social')
            ->assertSee('https://insta/acme');
    }

    public function test_address_type_value_axis_lists_addresses(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner, ['phones', 'messengers', 'addresses']);
        ContactEntry::factory()->for($site)->address()->create(['value' => 'Kyiv, Khreshchatyk 1']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'address'])
            ->assertSet('typeFilter', 'address')
            ->assertSee('Kyiv, Khreshchatyk 1');
    }
}
