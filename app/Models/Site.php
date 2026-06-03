<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Site extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    /** Noise/derived columns kept out of the audit trail. */
    protected $auditExclude = ['updated_at', 'last_checked_at', 'is_favourite', 'api_key'];

    protected $fillable = [
        'client_id', 'name', 'url', 'api_key', 'wp_version',
        'php_version', 'status', 'group', 'group_color',
        'last_checked_at', 'notes', 'is_favourite',
        'failover_enabled', 'failover_interval', 'failover_threshold',
        'geo_tabs', 'geo_rules', 'data_categories', 'messenger_kinds',
    ];

    protected static function booted(): void
    {
        static::creating(function (Site $site) {
            if (empty($site->attributes['api_key'] ?? null)) {
                $site->api_key = static::generateApiKey();
            }
        });
    }

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

    public static function generateApiKey(): string
    {
        return 'db_live_' . Str::lower(Str::random(32));
    }

    public static function legacyApiKeyForId(int $id): string
    {
        return 'db_live_' . substr(md5($id . 'key'), 0, 10);
    }

    public function getApiKeyAttribute($value): string
    {
        return $value ?: ($this->id ? static::legacyApiKeyForId((int) $this->id) : static::generateApiKey());
    }

    /**
     * Restrict a query to sites the user may see. Owner/admin — and any team member
     * whose access_scope isn't 'limited' — see every site. A limited user sees only
     * sites in their granted groups (group_access) or explicitly granted ids
     * (site_access). Team members access sites by scope, NOT by owning the client.
     */
    public function scopeAccessibleTo($query, ?User $user)
    {
        if (! $user || in_array($user->role, ['owner', 'admin'], true) || $user->access_scope !== 'limited') {
            return $query;
        }

        $groups = $user->group_access ?: ['__none__'];
        $sites = $user->site_access ?: [0];

        return $query->where(fn ($q) => $q->whereIn('group', $groups)->orWhereIn('id', $sites));
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
