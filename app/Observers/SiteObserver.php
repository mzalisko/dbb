<?php

namespace App\Observers;

use App\Models\Site;
use App\Services\ActivityLogService;

class SiteObserver
{
    public function created(Site $site): void
    {
        ActivityLogService::log('site created', $site, [
            'name' => $site->name,
            'url'  => $site->url,
        ]);
    }

    public function updated(Site $site): void
    {
        $changes = collect($site->getChanges())
            ->except(['updated_at', 'last_checked_at'])
            ->toArray();
        if (empty($changes)) return;
        ActivityLogService::log('site updated', $site, $changes);
    }

    public function deleted(Site $site): void
    {
        ActivityLogService::log('site deleted', $site, ['name' => $site->name]);
    }
}
