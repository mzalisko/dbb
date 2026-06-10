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

/**
 * v4 wizard. After Тип the manager picks an intent and the flow branches:
 *   edit    : Тип → Намір → Значення → Сайти → Дія → Підтвердити
 *   create  : Тип → Намір → Дані → Сайти → Підтвердити
 *   reserve : Тип → Намір → Основний → Сайти → Резерви → Підтвердити
 * It drives the existing audited apply* methods (+ one bulk add-reserve helper).
 */
class DataBrowserWizardTest extends TestCase
{
    use RefreshDatabase;

    private function siteForOwner(User $owner): Site
    {
        return Site::factory()->for(Client::factory()->for($owner))->create();
    }

    public function test_wizard_is_default_and_renders_each_edit_step(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->count(3)->create(['value' => '+SAME']);
        $id = (int) ContactEntry::where('value', '+SAME')->value('id');

        $c = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->assertSet('mode', 'wizard')
            ->assertSet('wizStep', 1)
            ->assertSee('Дії над даними')
            ->assertSee('Що міняємо');

        $c->set('wizStep', 2)->assertSee('Що зробити');
        $c->set('wizStep', 3)->assertSee('Які значення');
        $c->call('toggleWizValue', '+SAME')->set('wizStep', 4)->assertSee('Де саме');
        $c->call('toggleSelected', $id)->set('wizStep', 5)->assertSee('Яка дія');
        $c->call('setWizAction', 'replace')->set('wizStep', 6)->assertSee('останній перегляд');
    }

    public function test_wizard_renders_every_edit_action_detail(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->count(2)->create(['value' => '+SAME']);
        $id = (int) ContactEntry::where('value', '+SAME')->value('id');

        $c = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('toggleWizValue', '+SAME')
            ->call('toggleSelected', $id)
            ->set('wizStep', 5);

        foreach (['replace', 'substr', 'label', 'geo', 'state', 'move', 'duplicate', 'delete'] as $a) {
            $c->call('setWizAction', $a)->assertSet('wizAction', $a)->assertSee('Яка дія');
        }
    }

    public function test_edit_replace_touches_only_selected_occurrences(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $rows = ContactEntry::factory()->for($site)->phone()->count(4)->create(['value' => '+SAME']);
        $a = (int) $rows[0]->id;
        $b = (int) $rows[1]->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')->assertSet('wizStep', 2)        // intent (default edit)
            ->call('wizNext')->assertSet('wizStep', 3)        // value
            ->call('toggleWizValue', '+SAME')
            ->call('wizNext')->assertSet('wizStep', 4)        // sites
            ->call('toggleSelected', $a)
            ->call('toggleSelected', $b)
            ->call('wizNext')->assertSet('wizStep', 5)        // action
            ->call('setWizAction', 'replace')
            ->set('editValue', '+380700')
            ->call('wizNext')->assertSet('wizStep', 6)        // confirm
            ->call('wizConfirm')
            ->assertSet('wizStep', 1)
            ->assertSet('wizAction', '');

        $this->assertSame(2, ContactEntry::where('value', '+380700')->count());
        $this->assertSame(2, ContactEntry::where('value', '+SAME')->count());
    }

    public function test_create_flow_adds_entry_on_each_chosen_site(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $s1 = $this->siteForOwner($owner);
        $s2 = $this->siteForOwner($owner);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')->assertSet('wizStep', 2)        // intent
            ->call('setWizIntent', 'create')->assertSet('wizIntent', 'create')
            ->call('wizNext')->assertSet('wizStep', 3)        // data
            ->set('createValue', '+380 63 111 22 33')
            ->call('wizNext')->assertSet('wizStep', 4)        // sites
            ->call('toggleCreateSite', $s1->id)
            ->call('toggleCreateSite', $s2->id)
            ->call('wizNext')->assertSet('wizStep', 5)        // confirm
            ->call('wizConfirm')
            ->assertSet('wizStep', 1)
            ->assertSet('wizIntent', 'edit');

        $this->assertSame(2, ContactEntry::where('value', '+380 63 111 22 33')->count());
        $this->assertDatabaseHas('contact_entries', ['site_id' => $s1->id, 'value' => '+380 63 111 22 33']);
        $this->assertDatabaseHas('contact_entries', ['site_id' => $s2->id, 'value' => '+380 63 111 22 33']);
    }

    public function test_reserve_flow_attaches_new_reserves_to_each_selected_primary(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $s1 = $this->siteForOwner($owner);
        $s2 = $this->siteForOwner($owner);
        $p1 = ContactEntry::factory()->for($s1)->phone()->create(['value' => '+MAIN', 'role' => 'primary']);
        $p2 = ContactEntry::factory()->for($s2)->phone()->create(['value' => '+MAIN', 'role' => 'primary']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')->assertSet('wizStep', 2)        // intent
            ->call('setWizIntent', 'reserve')->assertSet('roleFilter', 'primary')
            ->call('wizNext')->assertSet('wizStep', 3)        // primary value
            ->call('pickValue', '+MAIN', '')
            ->call('wizNext')->assertSet('wizStep', 4)        // which primaries
            ->call('selectAllFiltered')
            ->call('wizNext')->assertSet('wizStep', 5)        // reserve numbers
            ->set('reserveNumbers', "+380701\n+380702")
            ->call('wizNext')->assertSet('wizStep', 6)        // confirm
            ->call('wizConfirm')
            ->assertSet('wizStep', 7)                         // lands on "order"
            ->assertSee('Порядок резервів')
            ->call('wizFinish')->assertSet('wizStep', 1);

        // Two new reserves under each of the two primaries = 4 backups.
        $this->assertSame(2, ContactEntry::where('parent_id', $p1->id)->where('role', 'backup')->count());
        $this->assertSame(2, ContactEntry::where('parent_id', $p2->id)->where('role', 'backup')->count());
        $this->assertSame(2, ContactEntry::where('value', '+380701')->count());
    }

    public function test_reserve_and_create_steps_render(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+MAIN', 'role' => 'primary']);

        $c = Livewire::actingAs($owner)->test(DataBrowser::class, ['typeFilter' => 'phone']);

        $c->call('setWizIntent', 'create');
        $c->set('wizStep', 3)->assertSee('Нові дані');
        $c->set('createValue', '+X')->set('wizStep', 4)->assertSee('На яких сайтах');

        $c->call('setWizIntent', 'reserve');
        $c->set('wizStep', 3)->assertSee('Який основний запис');
        $c->call('pickValue', '+MAIN', '')->set('wizStep', 4)->assertSee('До яких основних');
        $c->set('wizStep', 5)->assertSee('Резервні номери');
    }

    public function test_value_list_shows_primary_and_reserve_counts(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $primary = ContactEntry::factory()->for($site)->phone()->create(['value' => '+MAIN', 'role' => 'primary']);
        ContactEntry::factory()->backup($primary)->create(['value' => '+MAIN']);

        $g = collect(Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->viewData('valueGroups'))->firstWhere('gkey', '+MAIN');

        $this->assertNotNull($g);
        $this->assertSame(1, (int) $g->prim);   // one primary
        $this->assertSame(1, (int) $g->res);    // one reserve
    }

    public function test_wizard_blocks_advancing_without_value_or_selection(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+SAME']);

        $c = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')->assertSet('wizStep', 2)        // intent
            ->call('wizNext')->assertSet('wizStep', 3)        // value
            ->call('wizNext')->assertSet('wizStep', 3)        // no pick → stays
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');

        $c->call('toggleWizValue', '+SAME')
            ->call('wizNext')->assertSet('wizStep', 4)        // sites
            ->call('wizNext')->assertSet('wizStep', 4);       // no selection → stays
    }

    public function test_wizard_goto_is_backward_only(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->siteForOwner($owner);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizGoto', 5)->assertSet('wizStep', 1)     // can't skip forward
            ->call('wizNext')->assertSet('wizStep', 2)
            ->call('wizGoto', 1)->assertSet('wizStep', 1);    // back is fine
    }

    public function test_mode_toggle_switches_between_wizard_and_browse(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->siteForOwner($owner);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('toBrowse')->assertSet('mode', 'browse')
            ->call('toWizard')->assertSet('mode', 'wizard')->assertSet('wizStep', 1)->assertSet('wizIntent', 'edit');
    }

    public function test_edit_can_act_on_several_values_at_once(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->count(2)->create(['value' => '+A']);
        ContactEntry::factory()->for($site)->phone()->count(2)->create(['value' => '+B']);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+C']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')                                  // intent (edit)
            ->call('wizNext')->assertSet('wizStep', 3)         // value
            ->call('toggleWizValue', '+A')
            ->call('toggleWizValue', '+B')
            ->call('wizNext')->assertSet('wizStep', 4)         // sites
            ->assertViewHas('entries', fn ($e) => $e->total() === 4)  // A+B, not C
            ->call('selectAllFiltered')
            ->call('wizNext')->assertSet('wizStep', 5)         // action
            ->call('setWizAction', 'delete')
            ->call('wizNext')->assertSet('wizStep', 6)         // confirm
            ->call('wizConfirm');

        $this->assertSame(0, ContactEntry::where('value', '+A')->count());
        $this->assertSame(0, ContactEntry::where('value', '+B')->count());
        $this->assertSame(1, ContactEntry::where('value', '+C')->count(), '+C untouched');
    }

    public function test_reserve_presets_sites_and_defers_sync_until_finish(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $s1 = $this->siteForOwner($owner);
        $s2 = $this->siteForOwner($owner);
        ContactEntry::factory()->for($s1)->phone()->create(['value' => '+MAIN', 'role' => 'primary']);
        ContactEntry::factory()->for($s2)->phone()->create(['value' => '+MAIN', 'role' => 'primary']);

        $c = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')
            ->call('setWizIntent', 'reserve')
            ->call('wizNext')                                  // value
            ->call('pickValue', '+MAIN', '')
            ->call('wizNext')                                  // → sites (auto-select all)
            ->assertSet('wizStep', 4)
            ->assertSet('selectAllMatching', true);            // #1 pre-selected

        $c->call('wizNext')                                    // resnums
            ->set('reserveNumbers', '+380700')
            ->call('wizNext')                                  // confirm
            ->call('wizConfirm')                               // apply → order; sync staged
            ->assertSet('wizStep', 7);

        $this->assertNotEmpty($c->get('wizPendingSites'), 'sync staged, not pushed yet');

        $c->call('wizFinish')->assertSet('wizStep', 1);
        $this->assertEmpty($c->get('wizPendingSites'), 'sync flushed on Готово');
    }

    public function test_cancel_all_drops_staged_sync_and_resets(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+MAIN', 'role' => 'primary']);

        $c = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')
            ->call('setWizIntent', 'reserve')
            ->call('wizNext')
            ->call('pickValue', '+MAIN', '')
            ->call('wizNext')                                  // sites (auto-selected)
            ->call('wizNext')                                  // resnums
            ->set('reserveNumbers', '+380700')
            ->call('wizNext')
            ->call('wizConfirm')->assertSet('wizStep', 7);     // staged

        $c->call('wizCancel')
            ->assertSet('wizStep', 1)
            ->assertSet('wizIntent', 'edit')
            ->assertSet('wizValues', []);
        $this->assertEmpty($c->get('wizPendingSites'), 'staged sync dropped on cancel');
    }

    public function test_phone_value_cannot_be_text_on_create(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $s1 = $this->siteForOwner($owner);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')
            ->call('setWizIntent', 'create')
            ->call('wizNext')                                  // data
            ->set('createValue', 'привіт текст')
            ->call('wizNext')->assertSet('wizStep', 4)         // csites
            ->call('toggleCreateSite', $s1->id)
            ->call('wizNext')->assertSet('wizStep', 5)         // confirm
            ->call('wizConfirm')
            ->assertSet('wizStep', 3)                          // bounced back to "Дані"
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');

        $this->assertSame(0, ContactEntry::where('value', 'привіт текст')->count());
    }

    public function test_phone_reserve_numbers_cannot_be_text(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $p = ContactEntry::factory()->for($site)->phone()->create(['value' => '+MAIN', 'role' => 'primary']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')
            ->call('setWizIntent', 'reserve')
            ->call('wizNext')
            ->call('pickValue', '+MAIN', '')
            ->call('wizNext')                                  // sites (auto-selected)
            ->call('wizNext')                                  // resnums
            ->set('reserveNumbers', "+380701\nне номер")
            ->call('wizNext')                                  // confirm
            ->call('wizConfirm')
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');

        // One line is text → all-or-nothing, no reserves created.
        $this->assertSame(0, ContactEntry::where('parent_id', $p->id)->count());
    }

    public function test_price_value_writes_are_sanitized_like_the_site_drawer(): void
    {
        // Styled markup is a feature (whitelist), but broken/styled-attr HTML and
        // script must be cleaned on the browser's write path too.
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $e = ContactEntry::factory()->for($site)->price()->create(['value' => '1000', 'price' => null]);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'price'])
            ->call('inlineUpdate', $e->id, 'value', '2000 <span style="x" onclick="hack()">EUR</span><script>bad()</script>');

        $this->assertSame('2000 <span>EUR</span>', $e->fresh()->value, 'style/onclick/script stripped, whitelisted tag kept');
    }

    public function test_edit_price_is_single_select_and_finds_its_occurrences(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->price()->count(2)->create(['price' => 1000, 'currency' => 'UAH']);
        ContactEntry::factory()->for($site)->price()->create(['price' => 500, 'currency' => 'UAH']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'price'])
            ->call('wizNext')                                  // intent (edit)
            ->call('wizNext')->assertSet('wizStep', 3)         // value
            ->call('pickValue', '1000', 'UAH')                 // single-select price
            ->call('wizNext')->assertSet('wizStep', 4)         // sites
            ->assertViewHas('entries', fn ($e) => $e->total() === 2); // the two 1000 UAH found
    }

    public function test_wiz_export_downloads_the_selected_scope(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $ids = ContactEntry::factory()->for($site)->phone()->count(2)->create(['value' => '+SAME'])
            ->pluck('id')->map(fn ($i) => (int) $i)->all();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('toggleWizValue', '+SAME')
            ->call('selectPage', $ids)
            ->call('wizExport')
            ->assertFileDownloaded();
    }

    public function test_state_hidden_cascades_to_the_whole_set(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $primary = ContactEntry::factory()->for($site)->phone()->create(['role' => 'primary', 'visible' => true]);
        $backup = ContactEntry::factory()->backup($primary)->create(['visible' => true]);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('selectPage', [(int) $primary->id])
            ->set('roleValue', 'hidden')
            ->call('applyRole');

        $this->assertFalse((bool) $primary->fresh()->visible);
        $this->assertFalse((bool) $backup->fresh()->visible, 'reserve hidden together with its primary');
    }

    public function test_state_down_switches_to_reserve(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $primary = ContactEntry::factory()->for($site)->phone()->create(['role' => 'primary']);
        ContactEntry::factory()->backup($primary)->create();

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('selectPage', [(int) $primary->id])
            ->set('roleValue', 'down')
            ->call('applyRole');

        $this->assertTrue((bool) $primary->fresh()->failover_down, 'primary marked down → reserve serves');
    }

    public function test_multi_action_changes_value_and_state_in_one_pass(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->count(2)
            ->create(['value' => '+SAME', 'role' => 'primary', 'visible' => true]);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')                                  // intent (edit)
            ->call('wizNext')->assertSet('wizStep', 3)         // value
            ->call('toggleWizValue', '+SAME')
            ->call('wizNext')->assertSet('wizStep', 4)         // sites
            ->call('selectAllFiltered')
            ->call('wizNext')->assertSet('wizStep', 5)         // action
            ->call('setWizAction', 'changes')
            ->call('toggleChg', 'value')
            ->call('toggleChg', 'state')
            ->set('editValue', '+380999')
            ->set('roleValue', 'hidden')
            ->call('wizNext')->assertSet('wizStep', 6)         // confirm
            ->call('wizConfirm');

        // value AND state changed together, in one pass.
        $this->assertSame(2, ContactEntry::where('value', '+380999')
            ->where('role', 'hidden')->where('visible', false)->count());
    }

    public function test_multi_action_requires_at_least_one_field(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $id = (int) ContactEntry::factory()->for($site)->phone()->create(['value' => '+SAME'])->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('toggleWizValue', '+SAME')
            ->call('toggleSelected', $id)
            ->set('wizStep', 5)
            ->call('setWizAction', 'changes')
            ->call('wizConfirm')   // no field toggled
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');

        $this->assertSame('+SAME', ContactEntry::find($id)->value, 'nothing changed');
    }
}
