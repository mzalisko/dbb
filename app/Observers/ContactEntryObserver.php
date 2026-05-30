<?php

namespace App\Observers;

use App\Models\ContactEntry;

/**
 * CRUD logging moved to owen-it/laravel-auditing (PM-T01). This observer now
 * only carries non-audit domain logic: a messenger backup must inherit its
 * primary's platform (kind) so a Telegram primary can't hold a Viber reserve.
 */
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
}
