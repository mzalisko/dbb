<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\AuditAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Single choke-point for non-CRUD audit events (auth, bulk, group, system).
 * Model CRUD is handled by owen-it/laravel-auditing → `audits`; this writes the
 * complementary `activity_log` stream that AuditFeed (PM-T05) unions over.
 */
class ActivityLogService
{
    public static function log(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?int $severity = null,
        ?string $batchId = null,
        string $context = 'web',
        ?User $user = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id'      => $user?->id ?? auth()->id(),
            'action'       => $action,
            'severity'     => $severity ?? AuditAction::severity($action),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id'   => $subject?->getKey(),
            'properties'   => $properties ?: null,
            'batch_id'     => $batchId,
            'context'      => $context,
            'ip_address'   => request()?->ip(),
            'created_at'   => now(),
        ]);
    }
}
