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
 * v4 full-screen wizard: Тип → Значення → Сайти → Дія → Підтвердити.
 * It drives the existing audited apply* methods, so here we test the flow
 * (navigation, guards) and that each step + action detail renders.
 */
class DataBrowserWizardTest extends TestCase
{
    use RefreshDatabase;

    private function siteForOwner(User $owner): Site
    {
        return Site::factory()->for(Client::factory()->for($owner))->create();
    }

    public function test_wizard_is_the_default_mode_and_renders_every_step(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->count(4)->create(['value' => '+SAME']);

        $c = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->assertSet('mode', 'wizard')
            ->assertSet('wizStep', 1)
            ->assertSee('Дії над даними')
            ->assertSee('Що міняємо');

        $c->set('wizStep', 2)->assertSee('Яке значення');
        $c->call('pickValue', '+SAME', '')->set('wizStep', 3)->assertSee('Де саме');
        $first = (int) ContactEntry::where('value', '+SAME')->value('id');
        $c->call('toggleSelected', $first)->set('wizStep', 4)->assertSee('Яка дія');
        $c->call('setWizAction', 'replace')->set('wizStep', 5)->assertSee('Підтвердити');
    }

    public function test_wizard_renders_every_action_detail_without_error(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->count(2)->create(['value' => '+SAME']);
        $id = (int) ContactEntry::where('value', '+SAME')->value('id');

        $c = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('pickValue', '+SAME', '')
            ->call('toggleSelected', $id)
            ->set('wizStep', 4);

        foreach (['replace', 'substr', 'label', 'geo', 'state', 'move', 'duplicate', 'delete'] as $a) {
            $c->call('setWizAction', $a)->assertSet('wizAction', $a)->assertSee('Яка дія');
        }
    }

    public function test_wizard_replace_touches_only_selected_occurrences(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $rows = ContactEntry::factory()->for($site)->phone()->count(4)->create(['value' => '+SAME']);
        $a = (int) $rows[0]->id;
        $b = (int) $rows[1]->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')->assertSet('wizStep', 2)
            ->call('pickValue', '+SAME', '')
            ->call('wizNext')->assertSet('wizStep', 3)
            ->call('toggleSelected', $a)
            ->call('toggleSelected', $b)
            ->call('wizNext')->assertSet('wizStep', 4)
            ->call('setWizAction', 'replace')
            ->set('editValue', '+NEW')
            ->call('wizNext')->assertSet('wizStep', 5)
            ->call('wizConfirm')
            ->assertSet('wizStep', 1)        // success restarts the wizard
            ->assertSet('wizAction', '');

        $this->assertSame(2, ContactEntry::where('value', '+NEW')->count());
        $this->assertSame(2, ContactEntry::where('value', '+SAME')->count());
    }

    public function test_wizard_delete_flow_soft_deletes_selected(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $rows = ContactEntry::factory()->for($site)->phone()->count(3)->create(['value' => '+SAME']);
        $a = (int) $rows[0]->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('pickValue', '+SAME', '')
            ->call('toggleSelected', $a)
            ->set('wizStep', 5)
            ->call('setWizAction', 'delete')
            ->call('wizConfirm');

        $this->assertSoftDeleted('contact_entries', ['id' => $a]);
        $this->assertSame(2, ContactEntry::where('value', '+SAME')->count());
    }

    public function test_wizard_blocks_advancing_without_value_or_selection(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+SAME']);

        // Step 2 without a pick → stays on 2 with an error toast.
        $c = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizNext')->assertSet('wizStep', 2)
            ->call('wizNext')->assertSet('wizStep', 2)
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');

        // Pick, advance to 3, but select nothing → stays on 3.
        $c->call('pickValue', '+SAME', '')
            ->call('wizNext')->assertSet('wizStep', 3)
            ->call('wizNext')->assertSet('wizStep', 3);
    }

    public function test_wizard_goto_cannot_skip_prerequisites(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+SAME']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('wizGoto', 4)->assertSet('wizStep', 2)   // no value yet → clamp to 2
            ->call('pickValue', '+SAME', '')
            ->call('wizGoto', 4)->assertSet('wizStep', 3);   // value but no selection → clamp to 3
    }

    public function test_mode_toggle_switches_between_wizard_and_browse(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->siteForOwner($owner);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['typeFilter' => 'phone'])
            ->call('toBrowse')->assertSet('mode', 'browse')
            ->call('toWizard')->assertSet('mode', 'wizard')->assertSet('wizStep', 1);
    }
}
