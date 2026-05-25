<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'name', 'url', 'wp_version',
        'php_version', 'status', 'group', 'group_color',
        'last_checked_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
