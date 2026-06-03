<?php

namespace App\Models;

use App\Observers\ContactEntryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[ObservedBy(ContactEntryObserver::class)]
class ContactEntry extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $auditExclude = ['updated_at', 'failover_down', 'order'];

    protected $fillable = [
        'site_id', 'type', 'kind', 'value', 'label',
        'role', 'geo_tag', 'geo_mode', 'countries', 'visible', 'order', 'parent_id',
        'currency', 'price', 'old_price', 'price_unit', 'sku',
    ];

    protected function casts(): array
    {
        return [
            'countries'     => 'array',
            'visible'       => 'boolean',
            'failover_down' => 'boolean',
            'price'         => 'float',
            'old_price'     => 'float',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function backups(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    /**
     * The number currently handling traffic for this failover group: the highest
     * priority one that is up (base first, then reserves by order). Call on the
     * group head (role=primary). Returns null only if every number is down.
     */
    public function failoverServing(): ?self
    {
        return collect([$this])
            ->concat($this->backups->where('visible', true)->sortBy('order'))
            ->first(fn (self $e) => ! $e->failover_down);
    }

    /**
     * Geo source of truth for this entry. A reserve (parent_id set) carries no
     * geo of its own — belonging, visibility rule and per-country visibility all
     * read through to its primary, so the two can never drift apart. Primaries
     * own their geo and resolve to themselves.
     */
    public function geoOwner(): self
    {
        return $this->parent_id ? ($this->parent ?? $this) : $this;
    }

    /** Returns true when this entry is visible for a given geo code (ISO-2 or 'world'). */
    public function visibleForGeo(string $geo): bool
    {
        // A reserve inherits its primary's geo targeting (single source of truth).
        if ($this->parent_id && ($owner = $this->geoOwner()) !== $this) {
            return $owner->visibleForGeo($geo);
        }

        $visible = $this->getAttribute('visible');
        if ($visible === false || $visible === 0 || $visible === '0') return false;
        if ($this->geo_mode === 'all') return true;
        if ($geo === 'world') {
            // "world" matches geoMode=all (above) and geoMode=except (catch-all)
            return $this->geo_mode === 'except';
        }
        $geo = strtoupper($geo);
        $tag = $this->geo_tag ? strtoupper($this->geo_tag) : null;
        $countries = $this->visibilityCountries();

        if ($this->geo_mode === 'only') {
            // Visible in its home country plus the explicitly chosen ones.
            if ($tag && ! in_array($tag, $countries, true)) {
                $countries[] = $tag;
            }
            return in_array($geo, $countries, true);
        }

        if ($this->geo_mode === 'except') {
            // Visible everywhere EXCEPT the chosen countries — the home country
            // (geo_tag) is never excluded, only the ones explicitly listed.
            return $geo === $tag || ! in_array($geo, $countries, true);
        }

        return false;
    }

    private function visibilityCountries(): array
    {
        $countries = collect($this->countries ?? [])
            ->map(fn($code) => strtoupper(trim((string) $code)))
            ->filter(fn($code) => preg_match('/^[A-Z]{2,3}$/', $code))
            ->values()
            ->all();

        if (empty($countries) && $this->geo_tag) {
            $countries[] = strtoupper($this->geo_tag);
        }

        return $countries;
    }

    public function getGeoLabelAttribute(): string
    {
        // A reserve shows its primary's rule, not its own (unused) columns.
        if ($this->parent_id && ($owner = $this->geoOwner()) !== $this) {
            return $owner->geo_label;
        }

        $codes = collect($this->countries ?? [])->implode(' · ');

        $codes = collect($this->visibilityCountries())->join(' · ');

        return match ($this->geo_mode) {
            'all'    => 'Усім',
            'only'   => 'Тільки ' . ($codes ?: '—'),
            'except' => 'Крім ' . ($codes ?: '—'),
            default  => '—',
        };
    }

    public function getPreviewGeoLabelAttribute(): ?string
    {
        // A reserve shows its primary's belonging tag (read-through).
        if ($this->parent_id && ($owner = $this->geoOwner()) !== $this) {
            return $owner->preview_geo_label;
        }

        if ($this->geo_tag) {
            return strtoupper($this->geo_tag);
        }

        $countries = $this->countries ?? [];
        if ($this->geo_mode === 'only' && count($countries) === 1) {
            return strtoupper((string) $countries[0]);
        }

        return null;
    }

    /** Filter a collection by geo. */
    public static function filterForGeo(Collection $entries, string $geo): Collection
    {
        return $entries->filter(fn($e) => $e->visibleForGeo($geo))->values();
    }

    public const MSG_KINDS = [
        'telegram'  => ['label' => 'Telegram',  'color' => '#229ED9', 'short' => 'TG'],
        'whatsapp'  => ['label' => 'WhatsApp',  'color' => '#25D366', 'short' => 'WA'],
        'viber'     => ['label' => 'Viber',     'color' => '#7360F2', 'short' => 'VB'],
        'messenger' => ['label' => 'Messenger', 'color' => '#0084FF', 'short' => 'FB'],
        'signal'    => ['label' => 'Signal',    'color' => '#3A76F0', 'short' => 'SG'],
        'skype'     => ['label' => 'Skype',     'color' => '#00AFF0', 'short' => 'SK'],
    ];

    public const SOCIAL_KINDS = [
        'facebook'  => ['label' => 'Facebook',    'color' => '#1877F2', 'short' => 'FB'],
        'instagram' => ['label' => 'Instagram',   'color' => '#E4405F', 'short' => 'IG'],
        'tiktok'    => ['label' => 'TikTok',      'color' => '#010101', 'short' => 'TT'],
        'youtube'   => ['label' => 'YouTube',     'color' => '#FF0000', 'short' => 'YT'],
        'x'         => ['label' => 'X / Twitter', 'color' => '#010101', 'short' => 'X'],
        'linkedin'  => ['label' => 'LinkedIn',    'color' => '#0A66C2', 'short' => 'IN'],
    ];

    /**
     * Central registry of entry types → label + optional sub-kinds. Single source
     * of truth: add a type here (plus its factory/migration) and it surfaces in the
     * data browser tabs, bulk actions and the value-edit guard automatically.
     * Future, e.g.: 'social' => ['label' => 'Соцмережі', 'kinds' => self::SOCIAL_KINDS],
     *               'address' => ['label' => 'Адреси', 'kinds' => []].
     */
    public const TYPES = [
        'phone'     => ['label' => 'Телефони',   'kinds' => []],
        'messenger' => ['label' => 'Месенджери', 'kinds' => self::MSG_KINDS],
        'price'     => ['label' => 'Ціни',        'kinds' => []],
        'social'    => ['label' => 'Соцмережі',   'kinds' => self::SOCIAL_KINDS],
        'address'   => ['label' => 'Адреси',      'kinds' => []],
    ];

    /** @return array<string,string> type key → label */
    public static function typeLabels(): array
    {
        return array_map(fn ($t) => $t['label'], self::TYPES);
    }

    /** @return array<string,string> kind key → label for a type ([] when the type has no kinds) */
    public static function kindLabels(string $type): array
    {
        return array_map(fn ($k) => $k['label'], self::TYPES[$type]['kinds'] ?? []);
    }

    public static function hasKinds(string $type): bool
    {
        return ! empty(self::TYPES[$type]['kinds'] ?? []);
    }
}
