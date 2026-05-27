<?php

namespace App\Observers;

use App\Models\Client;
use App\Services\ActivityLogService;

class ClientObserver
{
    public function created(Client $client): void
    {
        ActivityLogService::log('client created', $client, [
            'company_name' => $client->company_name,
        ]);
    }

    public function updated(Client $client): void
    {
        $changes = collect($client->getChanges())->except(['updated_at'])->toArray();
        if (empty($changes)) return;
        ActivityLogService::log('client updated', $client, $changes);
    }

    public function deleted(Client $client): void
    {
        ActivityLogService::log('client deleted', $client, [
            'company_name' => $client->company_name,
        ]);
    }
}
