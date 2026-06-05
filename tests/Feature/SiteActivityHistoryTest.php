<?php

namespace Tests\Feature;

use App\Livewire\Sites\Show;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class SiteActivityHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_clear_only_selected_site_history(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($owner)->create();
        // Explicit status so the maintenance update below is always a real change
        // (the factory randomises status — otherwise no site.status.changed audit).
        $siteA = Site::factory()->for($client)->create(['name' => 'Site A', 'status' => 'active']);
        $siteB = Site::factory()->for($client)->create(['name' => 'Site B', 'status' => 'active']);
        $entryA = ContactEntry::factory()->for($siteA)->phone()->create(['label' => 'A']);
        $entryB = ContactEntry::factory()->for($siteB)->phone()->create(['label' => 'B']);

        $siteA->update(['status' => 'maintenance']);
        $entryA->update(['label' => 'A2']);
        $siteB->update(['status' => 'maintenance']);
        $entryB->update(['label' => 'B2']);

        ActivityLog::create([
            'action' => 'site.failover.triggered',
            'subject_type' => Site::class,
            'subject_id' => $siteA->id,
            'properties' => ['from' => '+1', 'to' => '+2'],
            'created_at' => now(),
        ]);
        ActivityLog::create([
            'action' => 'site.failover.triggered',
            'subject_type' => Site::class,
            'subject_id' => $siteB->id,
            'properties' => ['from' => '+3', 'to' => '+4'],
            'created_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(Show::class, ['site' => $siteA])
            ->call('clearSiteHistory')
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'success');

        $this->assertSame(0, Audit::where('auditable_type', Site::class)->where('auditable_id', $siteA->id)->count());
        $this->assertSame(0, Audit::where('auditable_type', ContactEntry::class)->where('auditable_id', $entryA->id)->count());
        $this->assertSame(0, ActivityLog::where('subject_type', Site::class)->where('subject_id', $siteA->id)->count());

        $this->assertGreaterThan(0, Audit::where('auditable_type', Site::class)->where('auditable_id', $siteB->id)->count());
        $this->assertGreaterThan(0, Audit::where('auditable_type', ContactEntry::class)->where('auditable_id', $entryB->id)->count());
        $this->assertSame(1, ActivityLog::where('subject_type', Site::class)->where('subject_id', $siteB->id)->count());
    }

    public function test_owner_can_clear_only_failover_history_for_site(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        // Explicit status so the maintenance update is always a real change (the
        // factory randomises status, which otherwise makes this assertion flaky).
        $site = Site::factory()->for(Client::factory()->for($owner))->create(['status' => 'active']);
        $entry = ContactEntry::factory()->for($site)->phone()->create(['label' => 'Primary']);

        $site->update(['status' => 'maintenance']);
        $site->update(['failover_enabled' => ! $site->failover_enabled]);
        $entry->update(['label' => 'Primary updated']);

        ActivityLog::create([
            'action' => 'site.failover.triggered',
            'subject_type' => Site::class,
            'subject_id' => $site->id,
            'properties' => ['from' => '+1', 'to' => '+2'],
            'created_at' => now(),
        ]);
        ActivityLog::create([
            'action' => 'entry.updated',
            'subject_type' => ContactEntry::class,
            'subject_id' => $entry->id,
            'properties' => ['site_id' => $site->id, 'old' => ['label' => 'Primary'], 'new' => ['label' => 'Primary updated']],
            'created_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(Show::class, ['site' => $site])
            ->call('clearSiteHistory', 'failover')
            ->assertDispatched('toast', fn ($e, $p) => ($p['type'] ?? null) === 'success');

        $feed = \App\Services\AuditFeed::collect(['site_id' => $site->id]);

        $this->assertSame(0, $feed->filter(fn ($event) => str_contains($event->actionCode, 'failover'))->count());
        $this->assertGreaterThan(0, $feed->filter(fn ($event) => $event->actionCode === 'site.status.changed')->count());
        $this->assertGreaterThan(0, $feed->filter(fn ($event) => $event->actionCode === 'entry.updated')->count());
        $this->assertSame(0, ActivityLog::where('action', 'site.failover.triggered')->count());
        $this->assertSame(1, ActivityLog::where('action', 'entry.updated')->count());
    }

    public function test_manager_cannot_clear_site_history(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $site = Site::factory()->for(Client::factory()->for($manager))->create();

        Livewire::actingAs($manager)
            ->test(Show::class, ['site' => $site])
            ->call('clearSiteHistory')
            ->assertForbidden();
    }
}
