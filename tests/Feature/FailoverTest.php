<?php

namespace Tests\Feature;

use App\Livewire\Sites\Show;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\User;
use App\Services\AuditFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Failover is a FIXED priority list: the head (головний/базовий) number, then
 * reserves by order. The served number is the highest-priority one that is up.
 * A trigger marks a number down (the next live one serves); a restore brings it
 * back — and the base reclaims its spot the moment it recovers (not circular).
 */
class FailoverTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:Site,1:ContactEntry,2:array<int,ContactEntry>} */
    private function siteWithReserves(int $count = 2): array
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $site = Site::factory()->for(Client::factory()->for($owner))->create();

        $base = ContactEntry::factory()->for($site)->phone()
            ->create(['value' => '+BASE', 'role' => 'primary', 'parent_id' => null, 'order' => 0]);

        $reserves = [];
        for ($i = 1; $i <= $count; $i++) {
            $reserves[] = ContactEntry::factory()->for($site)->phone()
                ->create(['value' => "+R{$i}", 'role' => 'backup', 'parent_id' => $base->id, 'order' => $i]);
        }

        return [$site, $base, $reserves];
    }

    private function lastTo(string $action): ?string
    {
        // Order by id, not created_at — two events in one test share a timestamp.
        return ActivityLog::where('action', $action)->orderByDesc('id')->first()?->properties['to'] ?? null;
    }

    public function test_base_serves_until_it_fails(): void
    {
        [$site, $base, [$r1]] = $this->siteWithReserves();

        Livewire::test(Show::class, ['site' => $site])->call('triggerFailover', $base->id);

        $this->assertTrue($base->fresh()->failover_down, 'base is now down');
        $this->assertSame('+R1', $this->lastTo('site.failover.triggered'), 'next live number serves');
        // The structure never changes — base stays the primary, just down.
        $this->assertSame('primary', $base->fresh()->role);
        $this->assertNull($base->fresh()->parent_id);
    }

    public function test_base_reclaims_its_spot_when_restored(): void
    {
        [$site, $base, [$r1]] = $this->siteWithReserves();
        $c = Livewire::test(Show::class, ['site' => $site]);

        $c->call('triggerFailover', $base->id);
        $this->assertSame('+R1', $this->lastTo('site.failover.triggered'));

        // Base recovers → it outranks R1 and serves again immediately.
        $c->call('restoreFailover', $base->id);
        $this->assertFalse($base->fresh()->failover_down);
        $this->assertSame('+BASE', $this->lastTo('site.failover.restored'));
    }

    public function test_failover_follows_priority_not_a_circle(): void
    {
        [$site, $base, [$r1, $r2]] = $this->siteWithReserves(2);
        $c = Livewire::test(Show::class, ['site' => $site]);

        $c->call('triggerFailover', $base->id); // base down → R1 serves
        $c->call('triggerFailover', $r1->id);   // R1 down  → R2 serves
        $this->assertSame('+R2', $this->lastTo('site.failover.triggered'));

        // Restore the BASE while R1 is still down — base jumps straight back to serving.
        $c->call('restoreFailover', $base->id);
        $this->assertSame('+BASE', $this->lastTo('site.failover.restored'));
        $this->assertTrue($r1->fresh()->failover_down, 'R1 stays down');
    }

    public function test_both_trigger_and_restore_are_logged(): void
    {
        [$site, $base] = $this->siteWithReserves();
        $c = Livewire::test(Show::class, ['site' => $site]);

        $c->call('triggerFailover', $base->id);
        $c->call('restoreFailover', $base->id);

        $this->assertDatabaseHas('activity_log', ['action' => 'site.failover.triggered', 'subject_id' => $site->id]);
        $this->assertDatabaseHas('activity_log', ['action' => 'site.failover.restored', 'subject_id' => $site->id]);
    }

    public function test_failover_event_reads_as_a_working_number_transition(): void
    {
        [$site, $base, [$r1]] = $this->siteWithReserves();

        Livewire::test(Show::class, ['site' => $site])->call('triggerFailover', $base->id);

        $event = AuditFeed::collect(['site_id' => $site->id])
            ->firstWhere('actionCode', 'site.failover.triggered');
        $row = collect($event->humanChanges())->firstWhere('field', 'Робочий номер');

        $this->assertNotNull($row);
        $this->assertSame('+BASE', $row['old']);
        $this->assertSame('+R1', $row['new']);
    }

    public function test_queue_hides_numbers_without_reserves(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $site = Site::factory()->for(Client::factory()->for($owner))->create();

        $base = ContactEntry::factory()->for($site)->phone()->create(['value' => '+WITH', 'role' => 'primary', 'parent_id' => null]);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+RES', 'role' => 'backup', 'parent_id' => $base->id]);
        ContactEntry::factory()->for($site)->phone()->create(['value' => '+SOLO', 'role' => 'primary', 'parent_id' => null]);

        $html = Livewire::test(Show::class, ['site' => $site])->html();

        // Exactly one queue group (the +WITH base); the reserve-less +SOLO forms none.
        $this->assertSame(1, substr_count($html, 'class="set-qgroup"'));
        $this->assertStringContainsString('ГОЛОВНИЙ', $html);
    }

    public function test_failover_works_for_messengers_too(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $site = Site::factory()->for(Client::factory()->for($owner))->create();

        $base = ContactEntry::factory()->for($site)->messenger()
            ->create(['value' => '@base', 'role' => 'primary', 'parent_id' => null, 'order' => 0]);
        $reserve = ContactEntry::factory()->for($site)->messenger()
            ->create(['value' => '@reserve', 'role' => 'backup', 'parent_id' => $base->id, 'order' => 1]);

        Livewire::test(Show::class, ['site' => $site])->call('triggerFailover', $base->id);

        $this->assertTrue($base->fresh()->failover_down);
        $this->assertSame('@reserve', $this->lastTo('site.failover.triggered'));
    }

    public function test_stale_id_reports_an_error_without_throwing(): void
    {
        [$site] = $this->siteWithReserves();

        Livewire::test(Show::class, ['site' => $site])
            ->call('triggerFailover', 999991)
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'error');

        $this->assertFalse(ActivityLog::where('action', 'site.failover.triggered')->latest()->first()->properties['ok']);
    }

    // ── Cascade visibility tests ──

    public function test_hiding_primary_cascades_to_all_backups(): void
    {
        [$site, $base, [$r1, $r2]] = $this->siteWithReserves(2);

        // All start visible.
        $this->assertTrue($base->fresh()->visible);
        $this->assertTrue($r1->fresh()->visible);
        $this->assertTrue($r2->fresh()->visible);

        Livewire::test(Show::class, ['site' => $site])
            ->call('toggleEntryVisibility', $base->id);

        // All should be hidden now.
        $this->assertFalse($base->fresh()->visible, 'primary hidden');
        $this->assertFalse($r1->fresh()->visible, 'reserve 1 cascade-hidden');
        $this->assertFalse($r2->fresh()->visible, 'reserve 2 cascade-hidden');
    }

    public function test_showing_primary_cascades_to_all_backups(): void
    {
        [$site, $base, [$r1, $r2]] = $this->siteWithReserves(2);

        // Start all hidden.
        $base->update(['visible' => false]);
        $r1->update(['visible' => false]);
        $r2->update(['visible' => false]);

        Livewire::test(Show::class, ['site' => $site])
            ->call('toggleEntryVisibility', $base->id);

        // All should be visible now.
        $this->assertTrue($base->fresh()->visible, 'primary shown');
        $this->assertTrue($r1->fresh()->visible, 'reserve 1 cascade-shown');
        $this->assertTrue($r2->fresh()->visible, 'reserve 2 cascade-shown');
    }

    public function test_hiding_reserve_cascades_whole_group(): void
    {
        [$site, $base, [$r1, $r2]] = $this->siteWithReserves(2);

        // Toggle via a reserve entry — should resolve to head and cascade.
        Livewire::test(Show::class, ['site' => $site])
            ->call('toggleEntryVisibility', $r1->id);

        $this->assertFalse($base->fresh()->visible, 'primary cascade-hidden via reserve');
        $this->assertFalse($r1->fresh()->visible, 'reserve 1 hidden');
        $this->assertFalse($r2->fresh()->visible, 'reserve 2 cascade-hidden');
    }

    public function test_cascade_visibility_is_logged(): void
    {
        [$site, $base, [$r1]] = $this->siteWithReserves(1);

        Livewire::test(Show::class, ['site' => $site])
            ->call('toggleEntryVisibility', $base->id);

        $log = ActivityLog::where('action', 'entry.visibility.hidden')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->properties['cascade']);
        $this->assertSame(1, $log->properties['backup_count']);
    }

    public function test_cascade_visibility_for_messengers(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $site = Site::factory()->for(Client::factory()->for($owner))->create();

        $base = ContactEntry::factory()->for($site)->messenger()
            ->create(['value' => '@main', 'role' => 'primary', 'parent_id' => null, 'order' => 0]);
        $reserve = ContactEntry::factory()->for($site)->messenger()
            ->create(['value' => '@backup', 'role' => 'backup', 'parent_id' => $base->id, 'order' => 1]);

        Livewire::test(Show::class, ['site' => $site])
            ->call('toggleEntryVisibility', $base->id);

        $this->assertFalse($base->fresh()->visible, 'messenger primary hidden');
        $this->assertFalse($reserve->fresh()->visible, 'messenger reserve cascade-hidden');
    }

    public function test_hide_show_roundtrip_preserves_visibility(): void
    {
        [$site, $base, [$r1, $r2]] = $this->siteWithReserves(2);

        $c = Livewire::test(Show::class, ['site' => $site]);

        // Hide all.
        $c->call('toggleEntryVisibility', $base->id);
        $this->assertFalse($base->fresh()->visible);

        // Show all again.
        $c->call('toggleEntryVisibility', $base->id);
        $this->assertTrue($base->fresh()->visible, 'primary visible after roundtrip');
        $this->assertTrue($r1->fresh()->visible, 'reserve 1 visible after roundtrip');
        $this->assertTrue($r2->fresh()->visible, 'reserve 2 visible after roundtrip');
    }
}
