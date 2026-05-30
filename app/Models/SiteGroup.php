<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SiteGroup extends Model implements AuditableContract
{
    use Auditable;

    protected $fillable = ['name', 'color'];

    protected $auditExclude = ['updated_at'];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'group', 'name');
    }
}
