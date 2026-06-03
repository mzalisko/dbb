<?php

namespace Tests\Feature;

use App\Livewire\ActivityLog;
use App\Livewire\Dashboard;
use App\Models\ActivityLog as ActivityLogModel;
use App\Models\Client;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeletedSiteRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_prompts_and_restores_deleted_site(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = Site::factory()->for(Client::factory()->for($owner))->create(['name' => 'Archived Site']);
        $site->delete();

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->call('requestRestoreSite', $site->id)
            ->assertSet('restoreSiteId', $site->id)
            ->assertSet('restoreSiteName', 'Archived Site')
            ->assertSee('Відновити сайт')
            ->call('confirmRestoreSite')
            ->assertRedirect(route('sites.show', $site->id));

        $this->assertFalse(Site::withTrashed()->findOrFail($site->id)->trashed());
    }

    public function test_logs_page_prompts_and_restores_deleted_site(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = Site::factory()->for(Client::factory()->for($owner))->create(['name' => 'Log Archived Site']);
        $site->delete();

        Livewire::actingAs($owner)
            ->test(ActivityLog::class)
            ->call('requestRestoreSite', $site->id)
            ->assertSet('restoreSiteId', $site->id)
            ->assertSet('restoreSiteName', 'Log Archived Site')
            ->call('confirmRestoreSite')
            ->assertRedirect(route('sites.show', $site->id));

        $this->assertFalse(Site::withTrashed()->findOrFail($site->id)->trashed());
    }

    public function test_dashboard_system_logs_are_paginated(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $site = Site::factory()->for(Client::factory()->for($owner))->create();

        foreach (range(1, 8) as $i) {
            ActivityLogModel::create([
                'action' => 'site.failover.triggered',
                'subject_type' => Site::class,
                'subject_id' => $site->id,
                'properties' => ['from' => '+1', 'to' => '+2'],
                'created_at' => now()->subSeconds($i),
            ]);
        }

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSeeHtml('class="pager"');
    }

    public function test_manager_cannot_restore_deleted_site(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $site = Site::factory()->for(Client::factory()->for($manager))->create([
            'name' => 'Locked Archived Site',
        ]);
        $site->delete();

        Livewire::actingAs($manager)
            ->test(Dashboard::class)
            ->call('requestRestoreSite', $site->id)
            ->assertForbidden();
    }
}
