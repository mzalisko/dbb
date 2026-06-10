<?php

namespace Nwdb\Livewire;

use App\Models\ActivityLog;
use App\Models\Site;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Nwdb\Jobs\PublishSiteFeed;
use Nwdb\Models\SitePlugin;
use Nwdb\WpFeed\FeedManager;

/**
 * Панель керування WP-плагіном сайту: підключення, завантаження ZIP,
 * публікація фіду, пауза/відновлення, ротація ключів.
 */
class PluginPanel extends Component
{
    public Site $site;
    public bool $confirmingRotate = false;

    public function mount(Site $site): void
    {
        $this->authorize('view', $site);
        $this->site = $site;
    }

    public function connect(FeedManager $feeds): void
    {
        $this->authorize('update', $this->site);

        if ($this->plugin()) {
            return;
        }

        $plugin = $feeds->connect($this->site);
        $this->log('plugin.connected', ['slug' => $plugin->slug]);
    }

    public function publishNow(): void
    {
        $this->authorize('update', $this->site);

        $plugin = $this->plugin();
        if (! $plugin || $plugin->status === SitePlugin::STATUS_PAUSED) {
            return;
        }

        PublishSiteFeed::dispatch($plugin->id, manual: true);
        $this->log('plugin.published', ['slug' => $plugin->slug]);
    }

    public function pause(FeedManager $feeds): void
    {
        $this->authorize('update', $this->site);

        $plugin = $this->plugin();
        if ($plugin && $plugin->isLive()) {
            $feeds->pause($plugin);
            $this->log('plugin.paused', ['slug' => $plugin->slug]);
        }
    }

    public function resume(): void
    {
        $this->authorize('update', $this->site);

        $plugin = $this->plugin();
        if ($plugin && $plugin->status === SitePlugin::STATUS_PAUSED) {
            $plugin->update(['status' => SitePlugin::STATUS_ENABLED]);
            PublishSiteFeed::dispatch($plugin->id, manual: true);
            $this->log('plugin.resumed', ['slug' => $plugin->slug]);
        }
    }

    public function rotateKeys(FeedManager $feeds): void
    {
        $this->authorize('update', $this->site);

        $plugin = $this->plugin();
        if ($plugin) {
            $old = $plugin->slug;
            $feeds->rotateKeys($plugin);
            $this->log('plugin.keys_rotated', ['old_slug' => $old, 'slug' => $plugin->fresh()->slug]);
        }

        $this->confirmingRotate = false;
    }

    public function downloadZip(): void
    {
        $this->authorize('update', $this->site);

        if (! $this->plugin()) {
            return;
        }

        $this->redirect(URL::temporarySignedRoute(
            'nwdb.plugin.download',
            now()->addMinutes(5),
            ['site' => $this->site->id],
        ));
    }

    private function plugin(): ?SitePlugin
    {
        return SitePlugin::where('site_id', $this->site->id)->first();
    }

    private function log(string $action, array $properties = []): void
    {
        ActivityLog::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => Site::class,
            'subject_id'   => $this->site->id,
            'properties'   => $properties,
            'ip_address'   => request()->ip(),
            'created_at'   => now(),
        ]);
    }

    public function render()
    {
        $plugin = SitePlugin::with('latestPublication')
            ->where('site_id', $this->site->id)
            ->first();

        return view('nwdb::plugin-panel', [
            'plugin' => $plugin,
            'publication' => $plugin?->latestPublication,
        ]);
    }
}
