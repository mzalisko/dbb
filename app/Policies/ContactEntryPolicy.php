<?php

namespace App\Policies;

use App\Models\ContactEntry;
use App\Models\User;

class ContactEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ContactEntry $entry): bool
    {
        if (! $user->canEntryType((string) $entry->type, 'read')) {
            return false;
        }

        if (in_array($user->role, ['owner', 'admin'], true)) {
            return true;
        }

        return $this->owns($user, $entry);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'admin', 'manager'], true);
    }

    public function update(User $user, ContactEntry $entry): bool
    {
        if (! $user->canEntryType((string) $entry->type, 'edit')) {
            return false;
        }

        if (in_array($user->role, ['owner', 'admin'], true)) {
            return true;
        }

        return $user->role === 'manager' && $this->owns($user, $entry);
    }

    public function delete(User $user, ContactEntry $entry): bool
    {
        if (! $user->canEntryType((string) $entry->type, 'delete')) {
            return false;
        }

        if (in_array($user->role, ['owner', 'admin'], true)) {
            return true;
        }

        return false;
    }

    private function owns(User $user, ContactEntry $entry): bool
    {
        $entry->loadMissing('site');

        return $entry->site !== null && $user->canAccessSite($entry->site);
    }
}
