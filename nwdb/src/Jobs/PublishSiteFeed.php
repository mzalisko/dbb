<?php

namespace Nwdb\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nwdb\Models\SitePlugin;
use Nwdb\WpFeed\FeedManager;

/**
 * Публікація фіду одного сайту. Унікальність по site_plugin_id +
 * delay при автотригері дебаунсить серію правок у одну публікацію.
 */
class PublishSiteFeed implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 60;

    public function __construct(
        public readonly int $sitePluginId,
        public readonly bool $manual = false,
    ) {
    }

    public function uniqueId(): string
    {
        return (string) $this->sitePluginId;
    }

    public function handle(FeedManager $feeds): void
    {
        $plugin = SitePlugin::find($this->sitePluginId);

        if (! $plugin || $plugin->status === SitePlugin::STATUS_PAUSED) {
            return;
        }

        // Автотригер публікує лише активні фіди; ручний — і draft (перша публікація)
        if (! $this->manual && ! $plugin->isLive()) {
            return;
        }

        $feeds->publish($plugin);
    }
}
