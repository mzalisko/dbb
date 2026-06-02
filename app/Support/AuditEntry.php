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
            'parent_id' => 'Активний контакт', 'name' => 'Назва', 'url' => 'Адреса сайту',
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
            $parts = array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : implode(' ', array_map('strval', (array) $v)), $value);

            return implode(' · ', array_filter($parts, fn ($p) => $p !== '')) ?: '—';
        }

        return (string) $value;
    }
}

