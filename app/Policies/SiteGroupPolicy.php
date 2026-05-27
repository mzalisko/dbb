<?php

namespace App\Policies;

use App\Models\User;

class SiteGroupPolicy
{
    /**
     * Delete a group — owner/admin, or manager (has the right by role).
     * Viewers cannot.
     */
    public function delete(User $user): bool
    {
        return in_array($user->role, ['owner', 'admin', 'manager'], true);
    }
}
