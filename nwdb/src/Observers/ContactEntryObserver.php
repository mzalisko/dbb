<?php

namespace Nwdb\Observers;

use App\Models\ContactEntry;
use Nwdb\Jobs\PublishSiteFeed;
use Nwdb\Models\SitePlugin;

/**
 * Автопублікація: будь-яка зміна контактів сайту з активним фідом
 * ставить у чергу відкладену публікацію (delay = дебаунс).
 */
class ContactEntryObserver
{
    public function created(ContactEntry $entry): void
    {
        $this->queuePublish($entry);
    }

    public function updated(ContactEntry $entry): void
    {
        $this->queuePublish($entry);
    }

    public function deleted(ContactEntry $entry): void
    {
        $this->queuePublish($entry);
    }

    private function queuePublish(ContactEntry $entry): void
    {
        $plugin = SitePlugin::where('site_id', $entry->site_id)
            ->where('status', SitePlugin::STATUS_ENABLED)
            ->first();

        if ($plugin) {
            PublishSiteFeed::dispatch($plugin->id)->delay(now()->addSeconds(20));
        }
    }
}
