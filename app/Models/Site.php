<?php

namespace App\Models;

use App\Observers\SiteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(SiteObserver::class)]
class Site extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'name', 'url', 'wp_version',
        'php_version', 'status', 'group', 'group_color',
        'last_checked_at', 'notes', 'is_favourite',
        'failover_enabled', 'failover_interval', 'failover_threshold',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at'  => 'datetime',
            'is_favourite'     => 'boolean',
            'failover_enabled' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contactEntries(): HasMany
    {
        return $this->hasMany(ContactEntry::class)->orderBy('order');
    }

    /** Filter contact entries visible for a given geo (ISO-2 or 'world'). */
    public function entriesForGeo(string $type, string $geo): \Illuminate\Database\Eloquent\Collection
    {
        return $this->contactEntries()
            ->where('type', $type)
            ->where('visible', true)
            ->get()
            ->filter(fn($e) => $e->visibleForGeo($geo))
            ->values();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'ok',
            'maintenance' => 'warn',
            'offline' => 'bad',
            default => 'info',
        };
    }
}
