<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    public static function log(
        string $action,
        Model $subject,
        ?array $properties = null,
        ?User $user = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id'      => $user?->id ?? auth()->id(),
            'action'       => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id'   => $subject->getKey(),
            'properties'   => $properties,
            'ip_address'   => request()?->ip(),
            'created_at'   => now(),
        ]);
    }
}
