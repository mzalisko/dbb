<?php

namespace App\Services;

use App\Models\ContactEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Runs a callback against many models in chunks, skipping rows the current user
 * is not authorized to touch. Operates by id (not by hydrating the whole set up
 * front) and reports how many were applied vs skipped.
 *
 * Auditing: per-row owen-it events are suppressed for the duration of the batch
 * and a single summary row is written to activity_log under one batch_id — so a
 * bulk action of 500 leaves one trail entry, not 500 (PM-T04). Nothing is logged
 * when nothing changed (done === 0).
 */
class BulkActionService
{
    /**
     * @param  class-string  $modelClass
     * @param  array<int>     $ids
     * @param  string|null    $ability       Gate ability checked per-row (null = no check)
     * @param  \Closure       $action        fn($model): void
     * @param  string|null    $auditAction   semantic code for the one summary row (null = none)
     * @return array{done:int, skipped:int, batch_id:?string}
     */
    public static function apply(
        string $modelClass,
        array $ids,
        ?string $ability,
        \Closure $action,
        bool $withTrashed = false,
        ?string $auditAction = null,
        bool $syncTouchedSites = false,
    ): array {
        if (empty($ids)) {
            return ['done' => 0, 'skipped' => 0, 'batch_id' => null];
        }

        $batchId = (string) Str::uuid();
        $user = Auth::user();
        $done = 0;
        $skipped = 0;
        $touchedSiteIds = [];

        // Suppress per-row model audits — the batch is one event, not N.
        // owen-it toggles auditing via a static on the auditable model class.
        $modelClass::disableAuditing();
        try {
            $query = $modelClass::query();
            if ($withTrashed) {
                $query->withTrashed();
            }

            $query->whereIn('id', $ids)->chunkById(500, function ($models) use (&$done, &$skipped, &$touchedSiteIds, $ability, $action, $user, $syncTouchedSites) {
                foreach ($models as $model) {
                    if ($ability !== null && (! $user || ! $user->can($ability, $model))) {
                        $skipped++;
                        continue;
                    }
                    $beforeSiteId = $model instanceof ContactEntry ? (int) $model->site_id : null;
                    $beforeDeleted = $model instanceof ContactEntry && method_exists($model, 'trashed') ? $model->trashed() : false;
                    $action($model);
                    if ($syncTouchedSites && $model instanceof ContactEntry) {
                        $afterSiteId = (int) ($model->site_id ?? $beforeSiteId);
                        $afterDeleted = method_exists($model, 'trashed') ? $model->trashed() : false;
                        if ($model->wasChanged() || $beforeDeleted !== $afterDeleted || ! $model->exists) {
                            if ($beforeSiteId) {
                                $touchedSiteIds[] = $beforeSiteId;
                            }
                            if ($afterSiteId) {
                                $touchedSiteIds[] = $afterSiteId;
                            }
                        }
                    }
                    $done++;
                }
            });
        } finally {
            $modelClass::enableAuditing();
        }

        // One summary row — only when something actually happened (critic decision #2).
        if ($auditAction !== null && $done > 0) {
            ActivityLogService::log($auditAction, null, [
                'model'      => $modelClass,
                'done'       => $done,
                'skipped'    => $skipped,
                'count'      => count($ids),
                'ids_sample' => array_slice(array_values($ids), 0, 20),
            ], batchId: $batchId, context: 'bulk');
        }

        if ($syncTouchedSites && ! empty($touchedSiteIds)) {
            app(SitePluginSyncService::class)->syncMany($touchedSiteIds);
        }

        return ['done' => $done, 'skipped' => $skipped, 'batch_id' => $batchId];
    }
}
