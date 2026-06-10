<?php

namespace Nwdb\Models;

use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Nwdb\Database\Factories\SitePluginFactory;

class SitePlugin extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ENABLED = 'enabled';
    public const STATUS_PAUSED = 'paused';

    protected $fillable = [
        'site_id', 'slug', 'display_name', 'prefix', 'text_domain',
        'feed_token', 'feed_filename', 'sym_key',
        'cron_hook', 'cron_interval',
        'status', 'payload_version', 'build_meta',
        'connected_at', 'keys_rotated_at',
    ];

    protected function casts(): array
    {
        return [
            'sym_key'         => 'encrypted',
            'build_meta'      => 'array',
            'cron_interval'   => 'integer',
            'payload_version' => 'integer',
            'connected_at'    => 'datetime',
            'keys_rotated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SitePluginFactory
    {
        return SitePluginFactory::new();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(FeedPublication::class);
    }

    public function latestPublication(): HasOne
    {
        return $this->hasOne(FeedPublication::class)->latestOfMany();
    }

    /** Шлях конверта відносно кореня dead-drop диска. */
    public function feedPath(): string
    {
        return trim(config('nwdb.deaddrop.prefix'), '/').'/'.$this->feed_filename;
    }

    /** Повний публічний URL фіду на нейтральному статик-домені. */
    public function feedUrl(): string
    {
        return rtrim(config('nwdb.deaddrop.base_url'), '/').'/'.$this->feedPath();
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }
}
