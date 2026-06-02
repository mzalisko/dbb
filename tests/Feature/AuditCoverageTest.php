<?php

namespace Tests\Feature;

use App\Livewire\Groups\Index as GroupsIndex;
use App\Livewire\Sites\Show;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\SiteGroup;
use App\Models\User;
use App\Services\BulkActionService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AuditCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_is_logged_as_critical_without_the_password(): void
    {
        event(new Failed('web', null, ['email' => 'x@x.test', 'password' => 'topsecret']));

        $row = ActivityLog::where('action', 'auth.login_failed')->first();
        $this->assertNotNull($row);
        $this->assertSame(2, $row->severity); // critical
        $this->assertSame('x@x.test', $row->properties['email'] ?? null);
        $this->assertStringNotContainsString('topsecret', json_encode($row->properties), 'never log the password');
    }

    public function test_deleting_a_group_logs_reassignment_and_owen_it_delete(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $client = Client::factory()->for($owner)->create();
        SiteGroup::create(['name' => 'prod', 'color' => '#000']);
        Site::factory()->for($client)->create(['group' => 'prod']);

        Livewire::actingAs($owner)->test(GroupsIndex::class)->call('deleteGroup', 'prod');

        $this->assertDatabaseHas('activity_log', ['action' => 'group.sites.reassigned']);
        $this->assertDatabaseHas('audits', ['event' => 'deleted', 'auditable_type' => SiteGroup::class]);
    }

    public function test_deleting_an_entry_with_reserves_logs_the_cascade(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = Site::factory()->for(Client::factory()->for($owner))->create();
        $phone = ContactEntry::factory()->for($site)->phone()->create();
        ContactEntry::factory()->backup($phone)->create();

        Livewire::actingAs($owner)->test(Show::class, ['site' => $site])
            ->call('requestDeleteEntry', $phone->id)
            ->call('confirmPendingAction');

        $cascade = ActivityLog::where('action', 'entry.deleted')->get()
            ->first(fn ($l) => ($l->properties['cascade'] ?? null) === 'backups');
        $this->assertNotNull($cascade, 'backups removal must leave a trail');
    }

    public function test_bulk_delete_writes_one_summary_not_per_row_audits(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = Site::factory()->for(Client::factory()->for($owner))->create();
        $ids = ContactEntry::factory()->for($site)->phone()->count(5)->create()
            ->pluck('id')->map(fn ($i) => (int) $i)->all();

        $this->actingAs($owner);
        $auditsBefore = DB::table('audits')->count();
        $logsBefore = ActivityLog::count();

        $result = BulkActionService::apply(
            ContactEntry::class, $ids, 'delete', fn ($e) => $e->delete(),
            auditAction: 'entry.bulk.deleted',
        );

        $this->assertSame(5, $result['done']);
        $this->assertNotNull($result['batch_id']);
        $this->assertSame(1, ActivityLog::count() - $logsBefore, 'exactly one summary row');
        $this->assertSame(0, DB::table('audits')->count() - $auditsBefore, 'no per-row audits during bulk');
    }

    public function test_logs_page_shows_semantic_labels_and_diff_drawer(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $site = Site::factory()->for(Client::factory()->for($owner))->create();
        $entry = ContactEntry::factory()->for($site)->phone()->create(['label' => 'old']);
        $entry->update(['label' => 'new']);

        $updated = \App\Services\AuditFeed::collect([])->firstWhere('actionCode', 'entry.updated');

        Livewire::test(\App\Livewire\ActivityLog::class)
            ->assertStatus(200)
            ->assertSee('Запис змінено')                 // semantic label, not raw "entry updated"
            ->call('openDetail', 'audit', $updated->id)
            ->assertSet('detail.actionCode', 'entry.updated')
            ->assertSee('label')                          // the changed field
            ->assertSee('old')                            // before
            ->assertSee('new');                           // after
    }
}
