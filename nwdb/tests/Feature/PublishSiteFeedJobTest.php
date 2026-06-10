<?php

namespace Nwdb\Tests\Feature;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Nwdb\Jobs\PublishSiteFeed;
use Nwdb\Models\SitePlugin;
use Nwdb\WpFeed\Cipher;
use Tests\TestCase;

class PublishSiteFeedJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('deaddrop');

        $pair = Cipher::generateSigningKeypair();
        config([
            'nwdb.signing.public' => base64_encode($pair['public']),
            'nwdb.signing.secret' => base64_encode($pair['secret']),
        ]);
    }

    private function entryAttributes(): array
    {
        return [
            'type' => 'phone', 'value' => '+380501112233',
            'role' => 'primary', 'geo_mode' => 'all', 'visible' => true, 'order' => 1,
        ];
    }

    public function test_contact_change_queues_delayed_publish_for_enabled_plugin(): void
    {
        $plugin = SitePlugin::factory()->enabled()->create();

        Queue::fake();
        $plugin->site->contactEntries()->create($this->entryAttributes());

        Queue::assertPushed(PublishSiteFeed::class, function (PublishSiteFeed $job) use ($plugin) {
            return $job->sitePluginId === $plugin->id && $job->delay !== null && $job->manual === false;
        });
    }

    public function test_contact_change_does_not_queue_for_draft_plugin(): void
    {
        $plugin = SitePlugin::factory()->create(); // draft

        Queue::fake();
        $plugin->site->contactEntries()->create($this->entryAttributes());

        Queue::assertNotPushed(PublishSiteFeed::class);
    }

    public function test_contact_change_on_site_without_plugin_queues_nothing(): void
    {
        $site = Site::factory()->create();

        Queue::fake();
        $site->contactEntries()->create($this->entryAttributes());

        Queue::assertNotPushed(PublishSiteFeed::class);
    }

    public function test_auto_job_noops_when_plugin_paused(): void
    {
        $plugin = SitePlugin::factory()->paused()->create();
        $plugin->site->contactEntries()->create($this->entryAttributes());

        (new PublishSiteFeed($plugin->id))->handle(app(\Nwdb\WpFeed\FeedManager::class));

        Storage::disk('deaddrop')->assertMissing($plugin->feedPath());
        $this->assertSame(0, $plugin->publications()->count());
    }

    public function test_manual_job_publishes_draft_plugin(): void
    {
        $plugin = SitePlugin::factory()->create(); // draft
        $plugin->site->contactEntries()->create($this->entryAttributes());

        (new PublishSiteFeed($plugin->id, manual: true))->handle(app(\Nwdb\WpFeed\FeedManager::class));

        Storage::disk('deaddrop')->assertExists($plugin->feedPath());
        $this->assertSame(SitePlugin::STATUS_ENABLED, $plugin->fresh()->status);
    }
}
