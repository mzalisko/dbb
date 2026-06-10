<?php

namespace Nwdb\Tests\Feature;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Nwdb\Models\FeedPublication;
use Nwdb\Models\SitePlugin;
use Nwdb\WpFeed\Cipher;
use Nwdb\WpFeed\FeedManager;
use Tests\TestCase;

class FeedManagerTest extends TestCase
{
    use RefreshDatabase;

    private array $signPair;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('deaddrop');

        $this->signPair = Cipher::generateSigningKeypair();
        config([
            'nwdb.signing.public' => base64_encode($this->signPair['public']),
            'nwdb.signing.secret' => base64_encode($this->signPair['secret']),
        ]);
    }

    private function siteWithPhone(): Site
    {
        $site = Site::factory()->create();
        $site->contactEntries()->create([
            'type' => 'phone', 'value' => '+380501112233',
            'role' => 'primary', 'geo_mode' => 'all', 'visible' => true, 'order' => 1,
        ]);

        return $site;
    }

    public function test_connect_creates_draft_identity(): void
    {
        $plugin = app(FeedManager::class)->connect($this->siteWithPhone());

        $this->assertSame(SitePlugin::STATUS_DRAFT, $plugin->status);
        $this->assertNotEmpty($plugin->slug);
        $this->assertSame(32, strlen(base64_decode($plugin->sym_key)));
        $this->assertStringEndsWith('.bin', $plugin->feed_filename);
    }

    public function test_publish_writes_envelope_that_decrypts_to_payload(): void
    {
        $feeds = app(FeedManager::class);
        $plugin = $feeds->connect($this->siteWithPhone());

        $publication = $feeds->publish($plugin);

        $this->assertSame(FeedPublication::STATUS_PUBLISHED, $publication->status);
        Storage::disk('deaddrop')->assertExists($plugin->feedPath());

        $envelope = Storage::disk('deaddrop')->get($plugin->feedPath());
        $payload = json_decode((new Cipher())->open(
            $envelope, base64_decode($plugin->sym_key), $this->signPair['public']
        ), true);

        $this->assertSame('+380501112233', $payload['contacts']['phones'][0]['value']);
        $this->assertSame(SitePlugin::STATUS_ENABLED, $plugin->fresh()->status);
    }

    public function test_identical_payload_is_not_republished(): void
    {
        $feeds = app(FeedManager::class);
        $plugin = $feeds->connect($this->siteWithPhone());

        $first = $feeds->publish($plugin);
        $second = $feeds->publish($plugin->refresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $plugin->publications()->count());
    }

    public function test_changed_payload_bumps_version(): void
    {
        $feeds = app(FeedManager::class);
        $site = $this->siteWithPhone();
        $plugin = $feeds->connect($site);

        $first = $feeds->publish($plugin);
        $site->contactEntries()->create([
            'type' => 'messenger', 'kind' => 'telegram', 'value' => '@new',
            'role' => 'primary', 'geo_mode' => 'all', 'visible' => true, 'order' => 1,
        ]);
        $second = $feeds->publish($plugin->refresh());

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($first->version + 1, $second->version);
    }

    public function test_pause_removes_artifact_and_resume_republishes(): void
    {
        $feeds = app(FeedManager::class);
        $plugin = $feeds->connect($this->siteWithPhone());
        $feeds->publish($plugin);
        $path = $plugin->feedPath();

        $feeds->pause($plugin->refresh());
        $this->assertSame(SitePlugin::STATUS_PAUSED, $plugin->fresh()->status);
        Storage::disk('deaddrop')->assertMissing($path);

        $feeds->resume($plugin->fresh());
        $this->assertSame(SitePlugin::STATUS_ENABLED, $plugin->fresh()->status);
        Storage::disk('deaddrop')->assertExists($path);
    }

    public function test_rotate_keys_changes_identity_and_removes_old_artifact(): void
    {
        $feeds = app(FeedManager::class);
        $plugin = $feeds->connect($this->siteWithPhone());
        $feeds->publish($plugin);

        $oldPath = $plugin->feedPath();
        $old = $plugin->only(['slug', 'prefix', 'feed_token', 'sym_key']);

        $rotated = $feeds->rotateKeys($plugin->refresh());

        $this->assertNotSame($old['slug'], $rotated->slug);
        $this->assertNotSame($old['prefix'], $rotated->prefix);
        $this->assertNotSame($old['feed_token'], $rotated->feed_token);
        $this->assertNotSame($old['sym_key'], $rotated->sym_key);
        $this->assertSame(SitePlugin::STATUS_DRAFT, $rotated->status);
        $this->assertNotNull($rotated->keys_rotated_at);
        Storage::disk('deaddrop')->assertMissing($oldPath);
    }
}
