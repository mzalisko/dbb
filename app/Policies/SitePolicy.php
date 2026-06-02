<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View a site -- if user has access to the owning client.
     * IMPORTANT: $site->client MUST be eager-loaded before calling this.
     */
    public function view(User $user, Site $site): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $site->client->user_id === $user->id && $user->canAccessSite($site);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Site $site): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $site->client->user_id === $user->id && $user->canAccessSite($site);
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->isAdmin();
    }
}
