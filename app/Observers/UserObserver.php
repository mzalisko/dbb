<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ActivityLogService;

class UserObserver
{
    public function created(User $user): void
    {
        ActivityLogService::log('user invited', $user, [
            'name' => $user->name,
            'role' => $user->role,
        ]);
    }

    public function updated(User $user): void
    {
        $sensitive = ['password', 'remember_token', 'updated_at'];
        $changes = collect($user->getChanges())->except($sensitive)->toArray();
        if (empty($changes)) return;
        ActivityLogService::log('user updated', $user, $changes);
    }

    public function deleted(User $user): void
    {
        ActivityLogService::log('user removed', $user, ['name' => $user->name]);
    }
}
