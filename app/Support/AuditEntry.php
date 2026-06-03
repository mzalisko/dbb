<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * One normalized audit event from either source — owen-it `audits` (model CRUD
 * with before→after diff) or `activity_log` (auth / bulk / group / system).
 * AuditFeed builds these; the UI renders label()/icon()/severity() + changes().
 */
final class AuditEntry
{
    public function __construct(
        public readonly int $id,
        public readonly string $source,        // 'audit' | 'activity'
        public readonly Carbon $occurredAt,
        public readonly ?int $userId,
        public readonly ?string $userName,
        public readonly string $actionCode,    // domain.object.verb
        public readonly int $severity,
        public readonly ?string $subjectType,
        public readonly ?int $subjectId,
        public readonly ?int $siteId,
        public readonly array $old,
        public readonly array $new,
        public readonly ?string $ip,
        public readonly ?string $batchId,
        public readonly ?string $context,
    ) {}

    public function label(): string
    {
        return AuditAction::label($this->actionCode);
    }

    public function icon(): string
    {
        return AuditAction::icon($this->actionCode);
    }

    public function domain(): string
    {
        return AuditAction::domain($this->actionCode);
    }

    public function severityLabel(): string
    {
        return match ($this->severity) {
            AuditAction::CRITICAL => 'CRITICAL',
            AuditAction::WARN     => 'WARN',
            default               => 'OK',
        };
    }

    /**
     * Only the changed fields — owen-it stores just the diff, so untouched values
     * never appear. Noise columns are dropped. Returns [['field','old','new'], …].
     *
     * @return array<int, array{field:string, old:mixed, new:mixed}>
     */
    public function changes(): array
    {
        $skip = ['updated_at', 'created_at', 'last_checked_at'];
        $keys = array_values(array_unique(array_merge(array_keys($this->old), array_keys($this->new))));

        $out = [];
        foreach ($keys as $key) {
            if (in_array($key, $skip, true)) {
                continue;
            }
            $old = $this->old[$key] ?? null;
            $new = $this->new[$key] ?? null;
            if ($old === $new) {
                continue; // defensive — never show an unchanged value
            }
            $out[] = ['field' => $key, 'old' => $old, 'new' => $new];
        }

        return $out;
    }

    public function hasChanges(): bool
    {
        return $this->changes() !== [];
    }

    /** Human field name (UA) for the diff — falls back to the raw key. */
    public static function humanField(string $field): string
    {
        return [
            'value' => 'Значення', 'label' => 'Мітка', 'role' => 'Стан', 'kind' => 'Платформа',
            'geo_tag' => 'Приналежність', 'geo_mode' => 'Правило видимості', 'countries' => 'Країни',
            'visible' => 'Видимість', 'price' => 'Ціна', 'old_price' => 'Стара ціна',
            'currency' => 'Валюта', 'price_unit' => 'Одиниця', 'sku' => 'SKU',
            'parent_id' => 'Головний контакт', 'name' => 'Назва', 'url' => 'Адреса сайту',
            'status' => 'Статус', 'notes' => 'Нотатки', 'group' => 'Група', 'group_color' => 'Колір групи',
            'geo_tabs' => 'Гео-вкладки', 'data_categories' => 'Категорії даних',
            'geo_rules' => 'Правила ізоляції', 'messenger_kinds' => 'Платформи',
            'suspended_at' => 'Призупинення', 'failover_enabled' => 'Failover',
            'failover_interval' => 'Інтервал', 'failover_threshold' => 'Поріг',
            'order' => 'Порядок', 'site_id' => 'Сайт',
            'done' => 'Змінено', 'skipped' => 'Пропущено', 'count' => 'Усього',
        ][$field] ?? $field;
    }

    /** Human-readable value for the diff — maps enums, joins arrays, никаких raw JSON. */
    public static function humanValue(string $field, mixed $value): string
    {
        if (is_null($value) || $value === '' || $value === []) {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'так' : 'ні';
        }

        $enums = [
            'geo_mode' => ['all' => 'Усім', 'only' => 'Тільки вибраним', 'except' => 'Крім вибраних'],
            'role'     => ['primary' => 'Активний', 'backup' => 'Резерв', 'hidden' => 'Приховано'],
            'status'   => ['active' => 'Активний', 'maintenance' => 'Пауза', 'offline' => 'Офлайн'],
            'visible'  => ['1' => 'так', '0' => 'ні'],
        ];
        if (isset($enums[$field]) && is_scalar($value) && isset($enums[$field][(string) $value])) {
            return $enums[$field][(string) $value];
        }

        if (is_array($value)) {
            $parts = array_map(fn ($v) => self::humanItem($field, $v), $value);

            return implode(' · ', array_filter($parts, fn ($p) => $p !== '')) ?: '—';
        }

        return (string) $value;
    }

    /** Whether this field's values are lists (show an added/removed delta, not two arrays). */
    public static function isListField(string $field): bool
    {
        return in_array($field, ['countries', 'geo_tabs', 'data_categories', 'messenger_kinds'], true);
    }

    /** Plain-language delta for a list field: "Додано: Адреси · Прибрано: Ціни". */
    public static function arrayDelta(string $field, mixed $old, mixed $new): string
    {
        $old = is_array($old) ? $old : [];
        $new = is_array($new) ? $new : [];

        $added = array_values(array_diff($new, $old));
        $removed = array_values(array_diff($old, $new));

        $parts = [];
        if ($added) {
            $parts[] = 'Додано: '.implode(', ', array_map(fn ($v) => self::humanItem($field, $v), $added));
        }
        if ($removed) {
            $parts[] = 'Прибрано: '.implode(', ', array_map(fn ($v) => self::humanItem($field, $v), $removed));
        }

        return $parts ? implode(' · ', $parts) : 'без змін';
    }

    /** Human label for one list item (category key, messenger kind, country code…). */
    private static function humanItem(string $field, mixed $v): string
    {
        if (! is_scalar($v)) {
            return implode(' ', array_map('strval', (array) $v));
        }
        $v = (string) $v;

        if ($field === 'data_categories') {
            return [
                'phones' => 'Телефони', 'messengers' => 'Месенджери', 'prices' => 'Ціни',
                'addresses' => 'Адреси', 'socials' => 'Соцмережі', 'custom' => 'Інше',
            ][$v] ?? $v;
        }
        if ($field === 'messenger_kinds') {
            return \App\Models\ContactEntry::MSG_KINDS[$v]['label'] ?? ucfirst($v);
        }

        return $v; // countries / geo_tabs are ISO codes — keep as-is
    }

    // ─── Unified human diff (the only renderer the UI/CSV should use) ─────────

    /** Memoized site id → name (handles soft-deleted granted sites too). */
    private static array $siteNameCache = [];

    private const PERM_ACTIONS = [
        'read' => 'перегляд', 'create' => 'створення',
        'edit' => 'редагування', 'delete' => 'видалення',
    ];

    private const USER_FIELD_LABELS = [
        'role' => 'Роль', 'access_scope' => 'Рівень доступу', 'name' => 'Імʼя',
        'email' => 'Email', 'phone' => 'Телефон', 'organization_name' => 'Організація',
        'avatar_path' => 'Аватар', 'suspended_at' => 'Доступ', 'permissions' => 'Дозволи',
        'site_access' => 'Доступ до сайтів', 'group_access' => 'Доступ до груп',
    ];

    private const USER_ROLES = [
        'owner' => 'Власник', 'admin' => 'Адміністратор',
        'manager' => 'Менеджер', 'viewer' => 'Глядач', 'member' => 'Учасник',
    ];

    /**
     * Fully-rendered, human-readable change rows — the single source of truth for
     * the Логи drawer, the site Активність timeline and the CSV export. Never emits
     * JSON, raw arrays, technical keys or unchanged fields. Each row is one of:
     *   ['kind'=>'scalar','field'=>string,'old'=>string,'new'=>string,'oldEmpty'=>bool,'newEmpty'=>bool]
     *   ['kind'=>'delta', 'field'=>string,'added'=>string[],'removed'=>string[]]
     *   ['kind'=>'group', 'field'=>string,'lines'=>[['label'=>string,'old'=>string,'new'=>string], …]]
     *
     * @return array<int, array<string,mixed>>
     */
    public function humanChanges(): array
    {
        // Failover events are a from→to transition, not a column diff — render the
        // numbers plainly instead of leaking from_id/to_id/mode/ok technical keys.
        if (str_contains($this->actionCode, 'failover')) {
            return $this->failoverRows();
        }

        $isUser = $this->subjectType === \App\Models\User::class;

        $isEntry = $this->subjectType === \App\Models\ContactEntry::class;
        // A reserve inherits geo from its primary (read-through), so its own geo
        // columns are noise — never show "Правило видимості: Усім" for a reserve.
        $isReserve = $isEntry
            && (! empty($this->new['parent_id']) || ! empty($this->old['parent_id']));
        $inheritedGeo = ['geo_tag', 'geo_mode', 'countries'];
        // Implied/technical entry columns the admin doesn't need in the feed.
        $entryNoise = ['type', 'site_id'];

        $rows = [];

        foreach ($this->changes() as $c) {
            $field = $c['field'];
            $old = $c['old'];
            $new = $c['new'];

            if ($isEntry && in_array($field, $entryNoise, true)) {
                continue;
            }
            if ($isReserve && in_array($field, $inheritedGeo, true)) {
                continue;
            }

            // Permission matrix → leaf-level lines ("Сайти: створення — вимкнено → увімкнено").
            if ($isUser && $field === 'permissions') {
                $lines = $this->permissionLines($old, $new);
                if ($lines) {
                    $rows[] = ['kind' => 'group', 'field' => 'Дозволи', 'lines' => $lines];
                }
                continue;
            }

            // Site/group access → added/removed by name (never raw ids).
            if ($isUser && ($field === 'site_access' || $field === 'group_access')) {
                [$label, $added, $removed] = self::accessDelta($field, $old, $new);
                if ($added || $removed) {
                    $rows[] = ['kind' => 'delta', 'field' => $label, 'added' => $added, 'removed' => $removed];
                }
                continue;
            }

            // List columns (geo / categories / messengers / countries) → added/removed.
            if (self::isListField($field)) {
                $oldArr = self::asArray($old);
                $newArr = self::asArray($new);
                $added = array_values(array_diff($newArr, $oldArr));
                $removed = array_values(array_diff($oldArr, $newArr));
                if ($added || $removed) {
                    $rows[] = [
                        'kind' => 'delta',
                        'field' => self::humanField($field),
                        'added' => array_map(fn ($v) => self::humanItem($field, $v), $added),
                        'removed' => array_map(fn ($v) => self::humanItem($field, $v), $removed),
                    ];
                }
                continue;
            }

            // Scalar field → old → new (subject-aware label + value).
            $rows[] = [
                'kind' => 'scalar',
                'field' => $this->fieldLabel($field),
                'old' => $this->scalarValue($field, $old),
                'new' => $this->scalarValue($field, $new),
                'oldEmpty' => self::isEmpty($old),
                'newEmpty' => self::isEmpty($new),
            ];
        }

        return $rows;
    }

    public function hasHumanChanges(): bool
    {
        return $this->humanChanges() !== [];
    }

    /** Plain from→to (with the actual phone numbers) for a failover event. */
    private function failoverRows(): array
    {
        $p = $this->new;

        if (($p['ok'] ?? true) === false) {
            return [[
                'kind' => 'scalar', 'field' => 'Результат',
                'old' => '—', 'new' => 'Помилка перемикання',
                'oldEmpty' => true, 'newEmpty' => false,
            ]];
        }

        $from = $p['from'] ?? null;
        $to = $p['to'] ?? null;
        if (self::isEmpty($from) && self::isEmpty($to)) {
            return [];
        }

        return [[
            'kind' => 'scalar', 'field' => 'Робочий номер',
            'old' => $from ?: '—', 'new' => $to ?: '—',
            'oldEmpty' => self::isEmpty($from), 'newEmpty' => self::isEmpty($to),
        ]];
    }

    private function fieldLabel(string $field): string
    {
        if ($this->subjectType === \App\Models\User::class && isset(self::USER_FIELD_LABELS[$field])) {
            return self::USER_FIELD_LABELS[$field];
        }

        return self::humanField($field);
    }

    /** Subject-aware human value (User enums differ from ContactEntry/Site). */
    private function scalarValue(string $field, mixed $value): string
    {
        if ($this->subjectType === \App\Models\User::class) {
            if ($field === 'access_scope') {
                return ((string) $value) === 'limited' ? 'Обмежений доступ' : 'Повний доступ';
            }
            if ($field === 'role') {
                return self::USER_ROLES[(string) $value] ?? (string) $value;
            }
            if ($field === 'suspended_at') {
                return self::isEmpty($value) ? 'Активний' : 'Призупинено';
            }
        }

        return self::humanValue($field, $value);
    }

    /**
     * Leaf-level permission diff. A null side means "follow role defaults", so we
     * resolve that side's role (this audit's old/new role, else the user's current
     * role) and compare against ROLE_PERMISSIONS — only genuinely changed toggles show.
     *
     * @return array<int, array{label:string, old:string, new:string}>
     */
    private function permissionLines(mixed $old, mixed $new): array
    {
        $oldMap = self::asArray($old) ?: (\App\Models\User::ROLE_PERMISSIONS[$this->roleForSide('old')] ?? []);
        $newMap = self::asArray($new) ?: (\App\Models\User::ROLE_PERMISSIONS[$this->roleForSide('new')] ?? []);

        $lines = [];
        foreach (\App\Models\User::RESOURCES as $res => $resLabel) {
            foreach (self::PERM_ACTIONS as $act => $actLabel) {
                $o = (bool) ($oldMap[$res][$act] ?? false);
                $n = (bool) ($newMap[$res][$act] ?? false);
                if ($o === $n) {
                    continue;
                }
                $lines[] = [
                    'label' => $resLabel.': '.$actLabel,
                    'old' => $o ? 'увімкнено' : 'вимкнено',
                    'new' => $n ? 'увімкнено' : 'вимкнено',
                ];
            }
        }

        return $lines;
    }

    /** Role baseline for one side of a permissions diff. */
    private function roleForSide(string $side): ?string
    {
        $role = $this->{$side}['role'] ?? null;
        if (is_string($role) && $role !== '') {
            return $role;
        }

        return \App\Models\User::query()->find($this->subjectId)?->role;
    }

    /**
     * Added/removed names for a User access list.
     *
     * @return array{0:string, 1:array<int,string>, 2:array<int,string>}
     */
    private static function accessDelta(string $field, mixed $old, mixed $new): array
    {
        $old = self::asArray($old);
        $new = self::asArray($new);

        if ($field === 'site_access') {
            $addedIds = array_values(array_diff($new, $old));
            $removedIds = array_values(array_diff($old, $new));
            $names = self::siteNames(array_merge($addedIds, $removedIds));
            $name = fn ($id) => $names[(int) $id] ?? ('сайт #'.$id);

            return ['Доступ до сайтів', array_map($name, $addedIds), array_map($name, $removedIds)];
        }

        // group_access stores group-name strings already.
        return [
            'Доступ до груп',
            array_values(array_diff($new, $old)),
            array_values(array_diff($old, $new)),
        ];
    }

    /** @param array<int,mixed> $ids @return array<int,string> id → name */
    private static function siteNames(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, fn ($i) => $i !== null && $i !== ''))));
        $missing = array_diff($ids, array_keys(self::$siteNameCache));

        if ($missing) {
            $found = \App\Models\Site::query()->withTrashed()->whereIn('id', $missing)->pluck('name', 'id');
            foreach ($missing as $id) {
                self::$siteNameCache[$id] = $found[$id] ?? null;
            }
        }

        $out = [];
        foreach ($ids as $id) {
            if (self::$siteNameCache[$id] !== null) {
                $out[$id] = self::$siteNameCache[$id];
            }
        }

        return $out;
    }

    private static function isEmpty(mixed $v): bool
    {
        return is_null($v) || $v === '' || $v === [];
    }

    /**
     * Coerce an audit value to an array. owen-it persists array-cast columns
     * (permissions, site_access, group_access, geo_tabs, …) as JSON *strings* in
     * old/new, so a plain is_array() check misses them — decode those here.
     */
    private static function asArray(mixed $v): array
    {
        if (is_array($v)) {
            return $v;
        }
        if (is_string($v) && $v !== '') {
            $decoded = json_decode($v, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /** Plain-language join of a pre-rendered added/removed delta (drawer, timeline, CSV). */
    public static function deltaText(array $added, array $removed): string
    {
        $parts = [];
        if ($added) {
            $parts[] = 'Додано: '.implode(', ', $added);
        }
        if ($removed) {
            $parts[] = 'Прибрано: '.implode(', ', $removed);
        }

        return $parts ? implode(' · ', $parts) : 'без змін';
    }
}

