<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Site extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    /** Noise/derived columns kept out of the audit trail. */
    protected $auditExclude = ['updated_at', 'last_checked_at', 'is_favourite'];

    protected $fillable = [
        'client_id', 'name', 'url', 'wp_version',
        'php_version', 'status', 'group', 'group_color',
        'last_checked_at', 'notes', 'is_favourite',
        'failover_enabled', 'failover_interval', 'failover_threshold',
        'geo_tabs', 'geo_rules', 'data_categories', 'messenger_kinds',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at'  => 'datetime',
            'is_favourite'     => 'boolean',
            'failover_enabled' => 'boolean',
            'geo_tabs'         => 'array',
            'geo_rules'        => 'array',
            'data_categories'  => 'array',
            'messenger_kinds'  => 'array',
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
