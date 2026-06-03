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
 * The site data tab can reorder reserves, reorder primaries and promote a reserve
 * to primary. owen-it excludes `order`, so these used to be silent — DB-T02 logs
 * them through ActivityLogService so they surface in the unified AuditFeed.
 */
class EntryReorderLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function ownerSite(): array
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = Site::factory()->for(Client::factory()->for($owner))->create();

        return [$owner, $site];
    }

    public function test_promoting_a_reserve_logs_made_primary_and_shows_in_feed(): void
    {
        [$owner, $site] = $this->ownerSite();
        $primary = ContactEntry::factory()->for($site)->phone()->create(['value' => '+MAIN']);
        $reserve = ContactEntry::factory()->backup($primary)->create(['value' => '+SPARE']);

        Livewire::actingAs($owner)
            ->test(Show::class, ['site' => $site])
            ->call('promoteEntry', $reserve->id);

        $log = ActivityLog::where('action', 'entry.made_primary')->first();
        $this->assertNotNull($log);
        $this->assertSame($site->id, $log->properties['site_id'] ?? null);
        $this->assertSame('+MAIN', $log->properties['from'] ?? null);

        // Detached and standing on its own.
        $this->assertNull($reserve->fresh()->parent_id);

        // Surfaces in the site's activity feed under the semantic code.
        $feed = AuditFeed::collect(['site_id' => $site->id]);
        $this->assertTrue($feed->contains(fn ($e) => $e->actionCode === 'entry.made_primary'));
    }

    public function test_promoting_a_plain_primary_does_not_log(): void
    {
        [$owner, $site] = $this->ownerSite();
        $primary = ContactEntry::factory()->for($site)->phone()->create(['role' => 'primary']);

        Livewire::actingAs($owner)
            ->test(Show::class, ['site' => $site])
            ->call('promoteEntry', $primary->id);

        $this->assertSame(0, ActivityLog::where('action', 'entry.made_primary')->count());
    }

    public function test_reordering_reserves_logs_when_order_changes(): void
    {
        [$owner, $site] = $this->ownerSite();
        $primary = ContactEntry::factory()->for($site)->phone()->create();
        $r1 = ContactEntry::factory()->backup($primary)->create(['order' => 1, 'value' => '+R1']);
        $r2 = ContactEntry::factory()->backup($primary)->create(['order' => 2, 'value' => '+R2']);

        Livewire::actingAs($owner)
            ->test(Show::class, ['site' => $site])
            ->call('reorderBackups', $primary->id, [$r2->id, $r1->id]);

        $log = ActivityLog::where('action', 'entry.reordered')->first();
        $this->assertNotNull($log);
        $this->assertSame('reserves', $log->properties['scope'] ?? null);
        $this->assertSame($site->id, $log->properties['site_id'] ?? null);

        // Order actually applied.
        $this->assertSame(1, $r2->fresh()->order);
        $this->assertSame(2, $r1->fresh()->order);
    }

    public function test_reordering_reserves_into_same_order_does_not_log(): void
    {
        [$owner, $site] = $this->ownerSite();
        $primary = ContactEntry::factory()->for($site)->phone()->create();
        $r1 = ContactEntry::factory()->backup($primary)->create(['order' => 1]);
        $r2 = ContactEntry::factory()->backup($primary)->create(['order' => 2]);

        Livewire::actingAs($owner)
            ->test(Show::class, ['site' => $site])
            ->call('reorderBackups', $primary->id, [$r1->id, $r2->id]); // unchanged

        $this->assertSame(0, ActivityLog::where('action', 'entry.reordered')->count());
    }

    public function test_reordering_primaries_logs_with_primaries_scope(): void
    {
        [$owner, $site] = $this->ownerSite();
        $p1 = ContactEntry::factory()->for($site)->phone()->create(['order' => 1, 'value' => '+P1']);
        $p2 = ContactEntry::factory()->for($site)->phone()->create(['order' => 2, 'value' => '+P2']);

        Livewire::actingAs($owner)
            ->test(Show::class, ['site' => $site])
            ->call('reorderEntries', [$p2->id, $p1->id]);

        $log = ActivityLog::where('action', 'entry.reordered')->first();
        $this->assertNotNull($log);
        $this->assertSame('primaries', $log->properties['scope'] ?? null);
        $this->assertSame('phone', $log->properties['type'] ?? null);
    }
}
