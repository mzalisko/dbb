<?php

namespace App\Observers;

use App\Models\ContactEntry;
use App\Services\ActivityLogService;

class ContactEntryObserver
{
    public function saving(ContactEntry $entry): void
    {
        if ($entry->type !== 'messenger' || $entry->role !== 'backup' || !$entry->parent_id) {
            return;
        }

        $parent = ContactEntry::query()
            ->whereKey($entry->parent_id)
            ->where('type', 'messenger')
            ->first(['id', 'kind']);

        if ($parent?->kind) {
            $entry->kind = $parent->kind;
        }
    }

    public function created(ContactEntry $entry): void
    {
        ActivityLogService::log('entry created', $entry, [
            'type'    => $entry->type,
            'value'   => $entry->value,
            'site_id' => $entry->site_id,
        ]);
    }

    public function updated(ContactEntry $entry): void
    {
        $changes = collect($entry->getChanges())->except(['updated_at'])->toArray();
        if (empty($changes)) return;
        ActivityLogService::log('entry updated', $entry, array_merge(
            ['site_id' => $entry->site_id],
            $changes
        ));
    }

    public function deleted(ContactEntry $entry): void
    {
        ActivityLogService::log('entry deleted', $entry, [
            'type'    => $entry->type,
            'value'   => $entry->value,
            'site_id' => $entry->site_id,
        ]);
    }

    public function restored(ContactEntry $entry): void
    {
        ActivityLogService::log('entry restored', $entry, [
            'type'    => $entry->type,
            'value'   => $entry->value,
            'site_id' => $entry->site_id,
        ]);
    }

    public function forceDeleted(ContactEntry $entry): void
    {
        ActivityLogService::log('entry purged', $entry, [
            'type'    => $entry->type,
            'value'   => $entry->value,
            'site_id' => $entry->site_id,
        ]);
    }
}
