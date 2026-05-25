<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'company_name', 'contact_name',
        'contact_email', 'contact_phone', 'notes', 'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function activeSites(): HasMany
    {
        return $this->sites()->where('status', 'active');
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(collect(explode(' ', $this->company_name))
            ->map(fn($w) => $w[0] ?? '')
            ->take(2)
            ->join(''));
    }
}
