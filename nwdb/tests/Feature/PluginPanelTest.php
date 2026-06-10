<?php

namespace Nwdb\Tests\Feature;

use App\Models\Client;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Nwdb\Jobs\PublishSiteFeed;
use Nwdb\Livewire\PluginPanel;
use Nwdb\Models\SitePlugin;
use Nwdb\WpFeed\Cipher;
use Tests\TestCase;

class PluginPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('deaddrop');

        $pair = Cipher::generateSigningKeypair();
        config([
            'nwdb.signing.public' => base64_encode($pair['public']),
            'nwdb.signing.secret' => base64_encode($pair['secret']),
        ]);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_connect_creates_site_plugin_and_logs_activity(): void
    {
        $site = Site::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(PluginPanel::class, ['site' => $site])
            ->call('connect');

        $plugin = SitePlugin::where('site_id', $site->id)->first();
        $this->assertNotNull($plugin);
        $this->assertSame(SitePlugin::STATUS_DRAFT, $plugin->status);

        $this->assertDatabaseHas('activity_log', [
            'action' => 'plugin.connected',
            'subject_id' => $site->id,
        ]);
    }

    public function test_connect_is_idempotent(): void
    {
        $plugin = SitePlugin::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(PluginPanel::class, ['site' => $plugin->site])
            ->call('connect');

        $this->assertSame(1, SitePlugin::where('site_id', $plugin->site_id)->count());
    }

    public function test_publish_now_dispatches_manual_job(): void
    {
        $plugin = SitePlugin::factory()->enabled()->create();

        Queue::fake();
        Livewire::actingAs($this->admin)
            ->test(PluginPanel::class, ['site' => $plugin->site])
            ->call('publishNow');

        Queue::assertPushed(PublishSiteFeed::class, fn ($job) => $job->manual === true);
    }

    public function test_pause_and_resume_flow(): void
    {
        $plugin = SitePlugin::factory()->enabled()->create();

        $panel = Livewire::actingAs($this->admin)->test(PluginPanel::class, ['site' => $plugin->site]);

        $panel->call('pause');
        $this->assertSame(SitePlugin::STATUS_PAUSED, $plugin->fresh()->status);

        $panel->call('resume');
        $this->assertSame(SitePlugin::STATUS_ENABLED, $plugin->fresh()->status);
    }

    public function test_rotate_keys_changes_identity(): void
    {
        $plugin = SitePlugin::factory()->enabled()->create();
        $oldSlug = $plugin->slug;

        Livewire::actingAs($this->admin)
            ->test(PluginPanel::class, ['site' => $plugin->site])
            ->call('rotateKeys');

        $this->assertNotSame($oldSlug, $plugin->fresh()->slug);
        $this->assertDatabaseHas('activity_log', ['action' => 'plugin.keys_rotated']);
    }

    public function test_download_redirects_to_signed_url(): void
    {
        $plugin = SitePlugin::factory()->enabled()->create();

        $panel = Livewire::actingAs($this->admin)
            ->test(PluginPanel::class, ['site' => $plugin->site])
            ->call('downloadZip');

        $redirect = $panel->effects['redirect'] ?? null;
        $this->assertNotNull($redirect);
        $this->assertStringContainsString("/sites/{$plugin->site_id}/plugin/download", $redirect);
        $this->assertStringContainsString('signature=', $redirect);

        // Підписаний URL віддає валідний ZIP
        $response = $this->actingAs($this->admin)->get($redirect);
        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');
    }

    public function test_member_cannot_manage_foreign_site(): void
    {
        $owner = User::factory()->create(['role' => 'member']);
        $stranger = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['user_id' => $owner->id]);
        $site = Site::factory()->create(['client_id' => $client->id]);

        // mount() авторизує 'view' — чужий member отримує 403 ще до дій
        Livewire::actingAs($stranger)
            ->test(PluginPanel::class, ['site' => $site])
            ->assertForbidden();

        $this->assertNull(SitePlugin::where('site_id', $site->id)->first());
    }
}
