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

#[ObservedBy(ContactEntryObserver::class)]
class ContactEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'site_id', 'type', 'kind', 'value', 'label',
        'role', 'geo_tag', 'geo_mode', 'countries', 'visible', 'order', 'parent_id',
        'currency', 'price', 'old_price', 'price_unit', 'sku',
    ];

    protected function casts(): array
    {
        return [
            'countries' => 'array',
            'visible'   => 'boolean',
            'price'     => 'float',
            'old_price' => 'float',
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

    /** Returns true when this entry is visible for a given geo code (ISO-2 or 'world'). */
    public function visibleForGeo(string $geo): bool
    {
        $visible = $this->getAttribute('visible');
        if ($visible === false || $visible === 0 || $visible === '0') return false;
        if ($this->geo_mode === 'all') return true;
        if ($geo === 'world') {
            // "world" matches geoMode=all (above) and geoMode=except (catch-all)
            return $this->geo_mode === 'except';
        }
        $geo = strtoupper($geo);
        if ($this->geo_tag && strtoupper($this->geo_tag) === $geo) {
            return $this->geo_mode !== 'except';
        }
        $countries = $this->visibilityCountries();
        if ($this->geo_tag && !in_array(strtoupper($this->geo_tag), $countries, true)) {
            $countries[] = strtoupper($this->geo_tag);
        }
        if ($this->geo_mode === 'only')   return in_array($geo, $countries, true);
        if ($this->geo_mode === 'except') return !in_array($geo, $countries, true);
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
