<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\SiteGroup;
use App\Models\User;
use App\Support\AuditAction;
use App\Support\AuditEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;

/**
 * Unified read-model: unions owen-it `audits` (model CRUD with old/new diff) and
 * `activity_log` (auth/bulk/group/system) into one normalized, filterable feed.
 *
 * Events are merged and sorted in PHP (newest first, id as tiebreaker). A per-source
 * cap keeps the merge bounded; with the 180-day retention (PM-T08) the tables stay
 * small. The UI paginates the merged result and resolves site names in one query.
 */
class AuditFeed
{
    /** Per-source row cap for the in-memory merge. */
    private const CAP = 1000;

    /** owen-it auditable_type (FQCN) → AuditAction domain key. */
    private const MODEL_KEY = [
        ContactEntry::class => 'entry',
        Site::class         => 'site',
        SiteGroup::class    => 'group',
        User::class         => 'user',
    ];

    /** Paginated merged feed. $filters: site_id, user_id, domain, severity, from, to, search, page. */
    public static function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $events = self::collect($filters);
        $page = max(1, (int) ($filters['page'] ?? (request()?->integer('page') ?: 1)));

        return new LengthAwarePaginator(
            $events->forPage($page, $perPage)->values(),
            $events->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page'],
        );
    }

    public static function forSite(int $siteId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return self::paginate(array_merge($filters, ['site_id' => $siteId]), $perPage);
    }

    /**
     * Pill/tab counts in one pass (no per-pill query) — ignores domain/severity so
     * the bars show totals across the rest of the active filter.
     *
     * @return array{total:int, byDomain:array<string,int>, bySeverity:array<int,int>}
     */
    public static function counts(array $filters = []): array
    {
        $events = self::collect(array_diff_key($filters, ['domain' => '', 'severity' => '']));

        return [
            'total'      => $events->count(),
            'byDomain'   => $events->groupBy(fn (AuditEntry $e) => $e->domain())->map->count()->all(),
            'bySeverity' => $events->groupBy(fn (AuditEntry $e) => $e->severity)->map->count()->all(),
        ];
    }

    /** @return Collection<int, AuditEntry> newest-first, filtered. */
    public static function collect(array $filters = []): Collection
    {
        $events = self::fromAudits($filters)->concat(self::fromActivity($filters));

        // occurred_at DESC, id DESC tiebreaker (stable paging for same-timestamp bursts).
        $sorted = $events->sortByDesc(fn (AuditEntry $e) => $e->occurredAt->getTimestamp() * 1_000_000 + $e->id)->values();

        return self::applyFilters($sorted, $filters);
    }

    // ─── Sources ──────────────────────────────────────────────────────────

    private static function fromAudits(array $filters): Collection
    {
        $audits = Audit::query()
            ->with('user')
            ->when(! empty($filters['from']), fn ($q) => $q->where('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->where('created_at', '<=', $filters['to']))
            ->latest('created_at')
            ->limit(self::CAP)
            ->get();

        // Resolve ContactEntry → site_id in one query (site_id isn't in the diff).
        $entryIds = $audits->where('auditable_type', ContactEntry::class)->pluck('auditable_id')->unique();
        $entrySites = $entryIds->isNotEmpty()
            ? ContactEntry::withTrashed()->whereIn('id', $entryIds)->pluck('site_id', 'id')
            : collect();

        return $audits->map(function (Audit $a) use ($entrySites) {
            $old = (array) $a->old_values;
            $new = (array) $a->new_values;
            $code = self::resolveAuditCode($a->event, $a->auditable_type, array_keys($new + $old));

            $siteId = match ($a->auditable_type) {
                Site::class         => (int) $a->auditable_id,
                ContactEntry::class => ($entrySites[$a->auditable_id] ?? null) !== null ? (int) $entrySites[$a->auditable_id] : null,
                default             => null,
            };

            return new AuditEntry(
                id: $a->id,
                source: 'audit',
                occurredAt: $a->created_at,
                userId: $a->getAttribute('user_id'),
                userName: $a->user?->name,
                actionCode: $code,
                severity: AuditAction::severity($code),
                subjectType: $a->auditable_type,
                subjectId: (int) $a->auditable_id,
                siteId: $siteId,
                old: $old,
                new: $new,
                ip: $a->ip_address,
                batchId: null,
                context: null,
            );
        });
    }

    private static function fromActivity(array $filters): Collection
    {
        return ActivityLog::query()
            ->with('user')
            ->when(! empty($filters['from']), fn ($q) => $q->where('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->where('created_at', '<=', $filters['to']))
            ->when(! empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->latest('created_at')
            ->limit(self::CAP)
            ->get()
            ->map(function (ActivityLog $l) {
                $props = $l->properties ?? [];
                $siteId = $props['site_id'] ?? ($l->subject_type === Site::class ? $l->subject_id : null);
                $hasDiff = array_key_exists('old', $props) || array_key_exists('new', $props);

                return new AuditEntry(
                    id: $l->id,
                    source: 'activity',
                    occurredAt: $l->created_at,
                    userId: $l->user_id,
                    userName: $l->user?->name,
                    actionCode: $l->action,
                    severity: $l->severity ?? AuditAction::severity($l->action),
                    subjectType: $l->subject_type,
                    subjectId: $l->subject_id,
                    siteId: $siteId !== null ? (int) $siteId : null,
                    old: $hasDiff && is_array($props['old'] ?? null) ? $props['old'] : [],
                    // Non-diff rows (bulk summary, auth, cascade) expose their props as "new".
                    new: $hasDiff
                        ? (is_array($props['new'] ?? null) ? $props['new'] : [])
                        : array_diff_key($props, ['site_id' => null]),
                    ip: $l->ip_address,
                    batchId: $l->batch_id,
                    context: $l->context,
                );
            });
    }

    // ─── Code resolution ───────────────────────────────────────────────────

    private static function resolveAuditCode(string $event, string $type, array $modifiedKeys): string
    {
        $m = self::MODEL_KEY[$type] ?? 'system';

        return match ($event) {
            'created'      => "{$m}.created",
            'deleted'      => "{$m}.deleted",
            'restored'     => "{$m}.restored",
            'forceDeleted' => $m === 'entry' ? 'entry.purged' : "{$m}.deleted",
            default        => self::resolveUpdate($m, $modifiedKeys), // 'updated'
        };
    }

    private static function resolveUpdate(string $model, array $keys): string
    {
        if ($model === 'site') {
            return self::resolveSiteUpdate($keys);
        }
        if ($model === 'entry') {
            // Only an actual amount change is a "price change" — a currency tweak
            // alone (or a stale EUR being cleared) stays a plain update.
            return array_intersect(['price', 'old_price'], $keys) ? 'entry.price.changed' : 'entry.updated';
        }
        if ($model === 'user') {
            if (in_array('suspended_at', $keys, true)) return 'user.suspended';
            if (in_array('role', $keys, true)) return 'user.role.changed';
            return 'user.updated';
        }

        return "{$model}.updated";
    }

    /**
     * Site update → code by which group of columns changed (critic S1):
     * we never guess a priority across domains — if ≥2 groups (or other columns)
     * are touched at once, fall back to the generic site.settings.updated.
     *   status-only        → site.status.changed
     *   failover_*-only    → site.failover.toggled
     *   geo_*-only         → site.geo.updated
     *   data_categories    → site.categories.updated
     *   else (mixed/other) → site.settings.updated
     */
    private static function resolveSiteUpdate(array $keys): string
    {
        $keys = array_filter($keys, fn ($k) => ! in_array($k, ['updated_at', 'last_checked_at'], true));

        $groups = [];
        foreach ($keys as $k) {
            if ($k === 'status')                               $groups['status'] = true;
            elseif (str_starts_with($k, 'failover'))           $groups['failover'] = true;
            elseif (in_array($k, ['geo_tabs', 'geo_rules'], true)) $groups['geo'] = true;
            elseif ($k === 'data_categories')                  $groups['categories'] = true;
            else                                               $groups['other'] = true;
        }

        if (count($groups) !== 1) {
            return 'site.settings.updated';
        }

        return match (array_key_first($groups)) {
            'status'     => 'site.status.changed',
            'failover'   => 'site.failover.toggled',
            'geo'        => 'site.geo.updated',
            'categories' => 'site.categories.updated',
            default      => 'site.settings.updated',
        };
    }

    // ─── Filters (cross-source, applied after merge) ─────────────────────────

    private static function applyFilters(Collection $events, array $filters): Collection
    {
        return $events
            ->when(array_key_exists('allowed_entry_types', $filters), fn ($c) => self::filterEntryTypes($c, (array) $filters['allowed_entry_types']))
            ->when(! empty($filters['domain']), fn ($c) => $c->filter(fn (AuditEntry $e) => $e->domain() === $filters['domain']))
            ->when(isset($filters['severity']) && $filters['severity'] !== '' && $filters['severity'] !== null,
                fn ($c) => $c->filter(fn (AuditEntry $e) => $e->severity === (int) $filters['severity']))
            ->when(! empty($filters['site_id']), fn ($c) => $c->filter(fn (AuditEntry $e) => $e->siteId === (int) $filters['site_id']))
            ->when(! empty($filters['user_id']), fn ($c) => $c->filter(fn (AuditEntry $e) => $e->userId === (int) $filters['user_id']))
            ->when(! empty($filters['search']), fn ($c) => $c->filter(
                fn (AuditEntry $e) => str_contains(mb_strtolower($e->label()), mb_strtolower((string) $filters['search']))
            ))
            ->when(! empty($filters['bulk']), fn ($c) => $c->filter(fn (AuditEntry $e) => str_contains($e->actionCode, '.bulk.')))
            ->when(! empty($filters['exclude_bulk']), fn ($c) => $c->reject(fn (AuditEntry $e) => str_contains($e->actionCode, '.bulk.')))
            ->values();
    }

    /**
     * Hide ContactEntry audit/activity rows for data types the current user cannot read.
     *
     * @param Collection<int, AuditEntry> $events
     * @param array<int, string> $allowedTypes
     * @return Collection<int, AuditEntry>
     */
    private static function filterEntryTypes(Collection $events, array $allowedTypes): Collection
    {
        $allowed = collect($allowedTypes)->filter()->values();

        if ($allowed->isEmpty()) {
            return $events->reject(fn (AuditEntry $e) => $e->domain() === 'entry');
        }

        $entryIds = $events
            ->filter(fn (AuditEntry $e) => $e->subjectType === ContactEntry::class && $e->subjectId)
            ->pluck('subjectId')
            ->unique()
            ->values();

        $typesById = $entryIds->isNotEmpty()
            ? ContactEntry::withTrashed()->whereIn('id', $entryIds)->pluck('type', 'id')
            : collect();

        return $events->filter(function (AuditEntry $e) use ($allowed, $typesById) {
            if ($e->domain() !== 'entry') {
                return true;
            }

            $type = null;
            if ($e->subjectType === ContactEntry::class && $e->subjectId) {
                $type = $typesById[$e->subjectId] ?? null;
            }

            $type ??= $e->new['type'] ?? $e->old['type'] ?? null;

            return $type !== null && $allowed->contains($type);
        });
    }
}
