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
        return $user->isAdmin() || $site->client->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Site $site): bool
    {
        return $user->isAdmin() || $site->client->user_id === $user->id;
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->isAdmin();
    }
}
