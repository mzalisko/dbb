<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\User;
use App\Services\BulkActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function siteForOwner(User $owner): Site
    {
        $client = Client::factory()->for($owner)->create();
        return Site::factory()->for($client)->create();
    }

    public function test_contact_entry_soft_deletes_and_restores(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $entry = ContactEntry::factory()->for($this->siteForOwner($owner))->phone()->create();

        $entry->delete();

        $this->assertSoftDeleted('contact_entries', ['id' => $entry->id]);
        $this->assertSame(0, ContactEntry::whereKey($entry->id)->count(), 'trashed excluded by default');
        $this->assertSame(1, ContactEntry::withTrashed()->whereKey($entry->id)->count());

        $entry->restore();
        $this->assertSame(1, ContactEntry::whereKey($entry->id)->count());
    }

    public function test_bulk_action_applies_to_authorized_rows(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = $this->siteForOwner($owner);
        $ids = ContactEntry::factory()->for($site)->phone()->count(3)->create()->pluck('id')->all();

        $this->actingAs($owner);
        $result = BulkActionService::apply(ContactEntry::class, $ids, 'delete', fn ($e) => $e->delete());

        $this->assertSame(3, $result['done']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(0, ContactEntry::whereIn('id', $ids)->count());
    }

    public function test_bulk_action_skips_unauthorized_rows(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $manager = User::factory()->create(['role' => 'manager']);
        $site = $this->siteForOwner($owner);
        $ids = ContactEntry::factory()->for($site)->phone()->count(3)->create()->pluck('id')->all();

        // ContactEntryPolicy::delete allows owner/admin only → manager is skipped.
        $this->actingAs($manager);
        $result = BulkActionService::apply(ContactEntry::class, $ids, 'delete', fn ($e) => $e->delete());

        $this->assertSame(0, $result['done']);
        $this->assertSame(3, $result['skipped']);
        $this->assertSame(3, ContactEntry::whereIn('id', $ids)->count(), 'nothing deleted');
    }
}
