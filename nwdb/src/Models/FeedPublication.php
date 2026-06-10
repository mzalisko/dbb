<?php

namespace Nwdb\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedPublication extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'site_plugin_id', 'version', 'payload_hash', 'byte_size',
        'entry_count', 'disk', 'path', 'status', 'error', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'version'      => 'integer',
            'byte_size'    => 'integer',
            'entry_count'  => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function sitePlugin(): BelongsTo
    {
        return $this->belongsTo(SitePlugin::class);
    }
}
