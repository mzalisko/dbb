<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;

class ContactEntry extends Model
{
    protected $fillable = [
        'site_id', 'type', 'kind', 'value', 'label',
        'role', 'geo_mode', 'countries', 'visible', 'order', 'parent_id',
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
        if (!$this->visible) return false;
        if ($this->geo_mode === 'all') return true;
        $countries = $this->countries ?? [];
        if ($geo === 'world') {
            // "world" matches geoMode=all (above) and geoMode=except (catch-all)
            return $this->geo_mode === 'except';
        }
        if ($this->geo_mode === 'only')   return in_array($geo, $countries);
        if ($this->geo_mode === 'except') return !in_array($geo, $countries);
        return false;
    }

    public function getGeoLabelAttribute(): string
    {
        $flags = collect($this->countries ?? [])
            ->map(fn($c) => match ($c) {
                'PL' => '🇵🇱', 'UA' => '🇺🇦', 'DE' => '🇩🇪',
                'US' => '🇺🇸', 'GB' => '🇬🇧', 'FR' => '🇫🇷',
                default => $c,
            })->implode('');

        return match ($this->geo_mode) {
            'all'    => '🌐 Усім',
            'only'   => 'Тільки ' . $flags,
            'except' => 'Крім ' . $flags,
            default  => '—',
        };
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
}
