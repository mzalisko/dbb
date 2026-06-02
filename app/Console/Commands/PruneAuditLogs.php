<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Console\Command;
use OwenIt\Auditing\Models\Audit;

/**
 * Retention for the audit tables, which grow fast. Deletes owen-it `audits` and
 * `activity_log` rows older than the TTL (default 180 days, critic decision #4),
 * and records one system.audit.pruned event so the cleanup itself leaves a trail.
 */
class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune {--days= : Retention window in days (default 180)} {--dry-run : Report only, delete nothing}';

    protected $description = 'Prune audit + activity_log rows older than the retention TTL.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('audit.retention_days', 180));
        if ($days < 1) {
            $this->error('Retention must be at least 1 day.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $auditCount = Audit::where('created_at', '<', $cutoff)->count();
        $logCount = ActivityLog::where('created_at', '<', $cutoff)->count();

        if ($this->option('dry-run')) {
            $this->info("Dry run: would prune {$auditCount} audits + {$logCount} activity_log rows older than {$days}d.");

            return self::SUCCESS;
        }

        Audit::where('created_at', '<', $cutoff)->delete();
        ActivityLog::where('created_at', '<', $cutoff)->delete();

        ActivityLogService::log('system.audit.pruned', null, [
            'days' => $days, 'audits' => $auditCount, 'activity_log' => $logCount,
        ], context: 'console');

        $this->info("Pruned {$auditCount} audits + {$logCount} activity_log rows older than {$days}d.");

        return self::SUCCESS;
    }
}
