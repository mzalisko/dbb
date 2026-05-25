<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Any authenticated user can view the list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View a specific client -- only the record owner or admin/owner.
     */
    public function view(User $user, Client $client): bool
    {
        return $user->isAdmin() || $client->user_id === $user->id;
    }

    /**
     * Any authenticated user can create clients.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Update -- record owner or admin.
     */
    public function update(User $user, Client $client): bool
    {
        return $user->isAdmin() || $client->user_id === $user->id;
    }

    /**
     * Delete -- only admin/owner.
     */
    public function delete(User $user, Client $client): bool
    {
        return $user->isAdmin();
    }
}
