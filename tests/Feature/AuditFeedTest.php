<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\AuditFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditFeedTest extends TestCase
{
    use RefreshDatabase;

    private function ownerSite(): array
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $site = Site::factory()->for(Client::factory()->for($owner))->create(['status' => 'active']);

        return [$owner, $site];
    }

    public function test_model_create_surfaces_as_entry_created_with_site(): void
    {
        [, $site] = $this->ownerSite();
        ContactEntry::factory()->for($site)->phone()->create();

        $created = AuditFeed::collect([])->firstWhere('actionCode', 'entry.created');

        $this->assertNotNull($created);
        $this->assertSame($site->id, $created->siteId);
        $this->assertSame('audit', $created->source);
    }

    public function test_update_diff_shows_only_changed_fields(): void
    {
        [, $site] = $this->ownerSite();
        $entry = ContactEntry::factory()->for($site)->phone()->create(['label' => 'old', 'value' => '+1']);
        $entry->update(['label' => 'new']); // only label changes

        $updated = AuditFeed::collect([])->firstWhere('actionCode', 'entry.updated');
        $this->assertNotNull($updated);

        $changes = collect($updated->changes());
        $this->assertTrue($changes->contains('field', 'label'));
        $this->assertFalse($changes->contains('field', 'value'), 'unchanged value must not appear');

        $label = $changes->firstWhere('field', 'label');
        $this->assertSame('old', $label['old']);
        $this->assertSame('new', $label['new']);
    }

    public function test_site_mixed_key_update_falls_back_to_settings(): void
    {
        [, $site] = $this->ownerSite();
        $site->update(['name' => $site->name.'X', 'status' => 'maintenance']); // 2 groups → generic

        $code = AuditFeed::collect(['site_id' => $site->id])->first()?->actionCode;
        $this->assertSame('site.settings.updated', $code);
    }

    public function test_site_status_only_update_is_status_changed(): void
    {
        [, $site] = $this->ownerSite();
        $site->update(['status' => 'maintenance']); // status-only

        $code = AuditFeed::collect(['site_id' => $site->id])->first()?->actionCode;
        $this->assertSame('site.status.changed', $code);
    }

    public function test_site_geo_only_update_is_geo_updated(): void
    {
        [, $site] = $this->ownerSite();
        $site->update(['geo_tabs' => ['UA', 'PL', 'DE']]); // geo-only

        $code = AuditFeed::collect(['site_id' => $site->id])->first()?->actionCode;
        $this->assertSame('site.geo.updated', $code);
    }

    public function test_activity_log_events_merge_into_the_feed(): void
    {
        [$owner] = $this->ownerSite();
        ActivityLogService::log('auth.login', user: $owner);

        $events = AuditFeed::collect(['domain' => 'auth']);
        $this->assertTrue($events->contains('actionCode', 'auth.login'));
        $this->assertSame('activity', $events->firstWhere('actionCode', 'auth.login')->source);
    }

    public function test_severity_and_domain_filters_apply(): void
    {
        [$owner] = $this->ownerSite();
        ActivityLogService::log('auth.login_failed', user: $owner); // critical

        $critical = AuditFeed::collect(['severity' => 2]);
        $this->assertTrue($critical->contains('actionCode', 'auth.login_failed'));
        $this->assertTrue($critical->every(fn ($e) => $e->severity === 2));
    }

    public function test_counts_group_by_domain_and_severity_in_one_pass(): void
    {
        [$owner, $site] = $this->ownerSite();
        ContactEntry::factory()->for($site)->phone()->create();
        ActivityLogService::log('auth.login_failed', user: $owner);

        $counts = AuditFeed::counts([]);
        $this->assertArrayHasKey('entry', $counts['byDomain']);
        $this->assertArrayHasKey('auth', $counts['byDomain']);
        $this->assertGreaterThan(0, $counts['total']);
    }
}
