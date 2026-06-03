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
 * A manual failover must actually swap the active/reserve roles (so it survives a
 * reload), record both numbers, and be re-runnable without 404-ing on a now-stale
 * role lookup.
 */
class FailoverTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:Site,1:ContactEntry,2:ContactEntry} */
    private function siteWithReserve(): array
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $site = Site::factory()->for(Client::factory()->for($owner))->create();

        $primary = ContactEntry::factory()->for($site)->phone()
            ->create(['value' => '+48 PRIMARY', 'role' => 'primary', 'parent_id' => null]);
        $reserve = ContactEntry::factory()->for($site)->phone()
            ->create(['value' => '+48 RESERVE', 'role' => 'backup', 'parent_id' => $primary->id]);

        return [$site, $primary, $reserve];
    }

    public function test_manual_trigger_persists_the_role_swap(): void
    {
        [$site, $primary, $reserve] = $this->siteWithReserve();

        Livewire::test(Show::class, ['site' => $site])
            ->call('triggerFailover', $primary->id, $reserve->id);

        // Reserve is now the active number; the failed primary parks under it.
        $this->assertSame('primary', $reserve->fresh()->role);
        $this->assertNull($reserve->fresh()->parent_id);
        $this->assertSame('backup', $primary->fresh()->role);
        $this->assertSame($reserve->id, $primary->fresh()->parent_id);
    }

    public function test_failover_records_both_numbers(): void
    {
        [$site, $primary, $reserve] = $this->siteWithReserve();

        Livewire::test(Show::class, ['site' => $site])
            ->call('triggerFailover', $primary->id, $reserve->id);

        $log = ActivityLog::where('action', 'site.failover.triggered')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame('+48 PRIMARY', $log->properties['from']);
        $this->assertSame('+48 RESERVE', $log->properties['to']);
        $this->assertSame($site->id, $log->subject_id);
    }

    public function test_failover_event_reads_as_a_number_transition(): void
    {
        [$site, $primary, $reserve] = $this->siteWithReserve();

        Livewire::test(Show::class, ['site' => $site])
            ->call('triggerFailover', $primary->id, $reserve->id);

        $event = AuditFeed::collect(['site_id' => $site->id])
            ->firstWhere('actionCode', 'site.failover.triggered');

        $this->assertNotNull($event);
        $rows = $event->humanChanges();
        $row = collect($rows)->firstWhere('field', 'Активний номер');

        $this->assertNotNull($row, 'failover must show the active-number transition');
        $this->assertSame('+48 PRIMARY', $row['old']);
        $this->assertSame('+48 RESERVE', $row['new']);
    }

    public function test_repeated_trigger_does_not_404(): void
    {
        [$site, $primary, $reserve] = $this->siteWithReserve();

        $component = Livewire::test(Show::class, ['site' => $site])
            ->call('triggerFailover', $primary->id, $reserve->id);

        // Second run swaps back. The old code 404'd here because $primary is no
        // longer role=primary; the resilient lookup must just succeed.
        $component->call('triggerFailover', $reserve->id, $primary->id);

        $this->assertSame('primary', $primary->fresh()->role);
        $this->assertSame('backup', $reserve->fresh()->role);
        $this->assertSame(2, ActivityLog::where('action', 'site.failover.triggered')->count());
    }

    public function test_stale_ids_report_an_error_without_throwing(): void
    {
        [$site] = $this->siteWithReserve();

        Livewire::test(Show::class, ['site' => $site])
            ->call('triggerFailover', 999991, 999992)
            ->assertDispatched('toast', fn ($event, $params) => ($params['type'] ?? null) === 'error');

        $log = ActivityLog::where('action', 'site.failover.triggered')->latest()->first();
        $this->assertNotNull($log);
        $this->assertFalse($log->properties['ok']);
    }
}
