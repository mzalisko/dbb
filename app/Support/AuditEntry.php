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
}
