<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

/**
 * Runs a callback against many models in chunks, skipping rows the current user
 * is not authorized to touch. Operates by id (not by hydrating the whole set up
 * front) and reports how many were applied vs skipped.
 */
class BulkActionService
{
    /**
     * @param  class-string  $modelClass
     * @param  array<int>     $ids
     * @param  string|null    $ability     Gate ability checked per-row (null = no check)
     * @param  \Closure       $action      fn($model): void
     * @return array{done:int, skipped:int}
     */
    public static function apply(
        string $modelClass,
        array $ids,
        ?string $ability,
        \Closure $action,
        bool $withTrashed = false
    ): array {
        if (empty($ids)) {
            return ['done' => 0, 'skipped' => 0];
        }

        $user = Auth::user();
        $done = 0;
        $skipped = 0;

        $query = $modelClass::query();
        if ($withTrashed) {
            $query->withTrashed();
        }

        $query->whereIn('id', $ids)->chunkById(500, function ($models) use (&$done, &$skipped, $ability, $action, $user) {
            foreach ($models as $model) {
                if ($ability !== null && (! $user || ! $user->can($ability, $model))) {
                    $skipped++;
                    continue;
                }

                $action($model);
                $done++;
            }
        });

        return ['done' => $done, 'skipped' => $skipped];
    }
}
