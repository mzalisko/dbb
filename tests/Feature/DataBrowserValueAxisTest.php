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
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

/**
 * The two-pane "by value" axis: the left rail lists distinct values, picking one
 * narrows the working set to its occurrences, and every bulk action then touches
 * only those — the headline goal "replace 1000 selectively, not everywhere".
 */
class DataBrowserValueAxisTest extends TestCase
{
    use RefreshDatabase;

    private function siteForOwner(User $owner): Site
    {
        return Site::factory()->for(Client::factory()->for($owner))->create();
    }

    public function test_value_axis_lists_distinct_values_with_counts(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->count(3)->create(['value' => '+SAME']);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+OTHER']);

        $groups = collect(Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->viewData('valueGroups'));

        $same = $groups->firstWhere('gkey', '+SAME');
        $other = $groups->firstWhere('gkey', '+OTHER');
        $this->assertNotNull($same);
        $this->assertSame(3, (int) $same->n);
        $this->assertSame(1, (int) $other->n);
    }

    public function test_picking_a_value_lets_you_replace_only_selected_occurrences(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $rows = ContactEntry::factory()->for($site)->phone()->count(5)->create(['value' => '+SAME']);
        $a = (int) $rows[0]->id;
        $b = (int) $rows[1]->id;

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('pickValue', '+SAME', '')
            ->call('toggleSelected', $a)
            ->call('toggleSelected', $b)
            ->call('openEdit', 'value')
            ->set('editValue', '+NEW')
            ->call('applyEdit');

        // Only the two ticked occurrences change; the other three keep +SAME.
        $this->assertSame(2, ContactEntry::where('value', '+NEW')->count());
        $this->assertSame(3, ContactEntry::where('value', '+SAME')->count());
    }

    public function test_select_all_matching_after_pick_covers_only_that_value(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $aIds = ContactEntry::factory()->for($site)->phone()->count(5)->create(['value' => '+A'])->pluck('id')->all();
        ContactEntry::factory()->for($site)->phone()->count(3)->create(['value' => '+B']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('pickValue', '+A', '')
            ->call('selectAllMatching')
            ->call('bulkDelete');

        $this->assertSame(0, ContactEntry::whereIn('id', $aIds)->count(), '+A occurrences deleted');
        $this->assertSame(3, ContactEntry::where('value', '+B')->count(), '+B untouched');
    }

    public function test_switching_axis_clears_the_pick(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+X']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('pickValue', '+X', '')
            ->assertSet('pickedValue', '+X')
            ->set('axis', 'site')
            ->assertSet('pickedValue', '');
    }

    public function test_price_axis_groups_by_amount_and_currency(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->price()->count(2)->create(['price' => 1000, 'currency' => 'UAH']);
        ContactEntry::factory()->for($site)->price()->create(['price' => 1000, 'currency' => 'EUR']);
        ContactEntry::factory()->for($site)->price()->create(['price' => 500, 'currency' => 'UAH']);

        $component = Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'price']);

        $groups = collect($component->viewData('valueGroups'));
        $uah1000 = $groups->first(fn ($g) => (int) $g->gkey === 1000 && $g->currency === 'UAH');
        $this->assertNotNull($uah1000);
        $this->assertSame(2, (int) $uah1000->n);

        // 1000 UAH and 1000 EUR are different groups — picking one excludes the other.
        $component->call('pickValue', '1000', 'UAH')
            ->assertViewHas('entries', fn ($e) => $e->total() === 2);
    }

    public function test_make_primary_detaches_reserve_captures_geo_and_logs_once(): void
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
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('makePrimary', $reserve->id);

        $reserve->refresh();
        $this->assertNull($reserve->parent_id);
        $this->assertSame('primary', $reserve->role);
        $this->assertSame('UA', $reserve->geo_tag);       // inherited geo captured
        $this->assertSame('except', $reserve->geo_mode);
        $this->assertSame(['PL'], $reserve->countries);

        // One clean semantic feed entry, no "entry.updated" audit noise.
        $this->assertSame(1, ActivityLog::where('action', 'entry.made_primary')->count());
        $this->assertSame(0, Audit::where('auditable_type', ContactEntry::class)
            ->where('auditable_id', $reserve->id)->where('event', 'updated')->count());
    }

    public function test_make_primary_is_blocked_without_update_rights(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        // A limited-scope user with no granted sites cannot touch this entry.
        $manager = User::factory()->create([
            'role' => 'manager', 'access_scope' => 'limited', 'group_access' => [], 'site_access' => [],
        ]);
        $site = $this->siteForOwner($owner);
        $primary = ContactEntry::factory()->for($site)->phone()->create();
        $reserve = ContactEntry::factory()->backup($primary)->create();

        Livewire::actingAs($manager)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->call('makePrimary', $reserve->id)
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');

        $this->assertSame('backup', $reserve->fresh()->role);
    }

    public function test_no_pick_shows_the_prompt_not_the_table(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+HIDDENUNTILPICKED']);

        Livewire::actingAs($owner)
            ->test(DataBrowser::class, ['mode' => 'browse', 'typeFilter' => 'phone'])
            ->assertSee('Оберіть значення у фільтрі')   // prompt shown before a pick
            ->assertSee('+HIDDENUNTILPICKED');          // value is listed in the «Значення ▾» dropdown
    }
}
