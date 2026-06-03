<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['name', 'email', 'password', 'role', 'permissions', 'access_scope', 'group_access', 'site_access', 'organization_name', 'phone', 'avatar_path'])]
#[Hidden(['password', 'temp_password', 'remember_token'])]
class User extends Authenticatable implements AuditableContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Auditable;

    /** Never audit secrets or login-timestamp noise. */
    protected $auditExclude = ['password', 'temp_password', 'temp_password_expires_at', 'remember_token', 'updated_at', 'last_login_at'];

    public const RESOURCES = [
        'sites'           => 'Сайти',
        'data_phones'     => 'Телефони',
        'data_messengers' => 'Месенджери',
        'data_prices'     => 'Ціни',
        'data_addresses'  => 'Адреси',
        'data_socials'    => 'Соц. мережі',
        'data_custom'     => 'Custom',
        'groups'          => 'Групи сайтів',
        'team'            => 'Команда',
        'api'             => 'API ключі',
    ];

    public const ENTRY_TYPE_RESOURCES = [
        'phone'     => 'data_phones',
        'messenger' => 'data_messengers',
        'price'     => 'data_prices',
        'address'   => 'data_addresses',
        'social'    => 'data_socials',
        'custom'    => 'data_custom',
    ];

    public const ACTIONS = ['read', 'create', 'edit', 'delete'];

    public const ROLE_PERMISSIONS = [
        'owner' => [
            'sites'           => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_phones'     => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_messengers' => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_prices'     => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_addresses'  => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_socials'    => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_custom'     => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'groups'          => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'team'            => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'api'             => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
        ],
        'admin' => [
            'sites'           => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_phones'     => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_messengers' => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_prices'     => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_addresses'  => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_socials'    => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'data_custom'     => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'groups'          => ['read' => true, 'create' => true,  'edit' => true,  'delete' => false],
            'team'            => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'api'             => ['read' => true, 'create' => true,  'edit' => false, 'delete' => true],
        ],
        'manager' => [
            'sites'           => ['read' => true,  'create' => true,  'edit' => true,  'delete' => false],
            'data_phones'     => ['read' => true,  'create' => true,  'edit' => true,  'delete' => false],
            'data_messengers' => ['read' => true,  'create' => true,  'edit' => true,  'delete' => false],
            'data_prices'     => ['read' => true,  'create' => true,  'edit' => true,  'delete' => false],
            'data_addresses'  => ['read' => true,  'create' => true,  'edit' => true,  'delete' => false],
            'data_socials'    => ['read' => true,  'create' => true,  'edit' => true,  'delete' => false],
            'data_custom'     => ['read' => true,  'create' => true,  'edit' => true,  'delete' => false],
            'groups'          => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'team'            => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'api'             => ['read' => false, 'create' => false, 'edit' => false, 'delete' => false],
        ],
        'viewer' => [
            'sites'           => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'data_phones'     => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'data_messengers' => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'data_prices'     => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'data_addresses'  => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'data_socials'    => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'data_custom'     => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'groups'          => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'team'            => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'api'             => ['read' => false, 'create' => false, 'edit' => false, 'delete' => false],
        ],
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'suspended_at'      => 'datetime',
            'temp_password_expires_at' => 'datetime',
            'password'          => 'hashed',
            'permissions'       => 'array',
            'group_access'      => 'array',
            'site_access'       => 'array',
        ];
    }

    public function effectivePermissions(): array
    {
        $defaults = self::ROLE_PERMISSIONS[$this->role] ?? self::ROLE_PERMISSIONS['viewer'];

        if (! is_array($this->permissions)) {
            return $defaults;
        }

        $merged = $defaults;
        foreach ($this->permissions as $resource => $actions) {
            $resource = $resource === 'phones' ? 'data_phones' : $resource;

            if (! isset($merged[$resource]) || ! is_array($actions)) {
                continue;
            }
            foreach (self::ACTIONS as $action) {
                $merged[$resource][$action] = (bool) ($actions[$action] ?? false);
            }
        }

        return $merged;
    }

    public function can_(string $resource, string $action): bool
    {
        return (bool) ($this->effectivePermissions()[$resource][$action] ?? false);
    }

    public static function resourceForEntryType(string $type): string
    {
        return self::ENTRY_TYPE_RESOURCES[$type] ?? 'data_custom';
    }

    public function canEntryType(string $type, string $action = 'read'): bool
    {
        return $this->can_(self::resourceForEntryType($type), $action);
    }

    public function readableEntryTypes(): array
    {
        return collect(array_keys(self::ENTRY_TYPE_RESOURCES))
            ->filter(fn (string $type) => $this->canEntryType($type))
            ->values()
            ->all();
    }

    public function canAccessSite(Site $site): bool
    {
        if ($this->access_scope !== 'limited') {
            return true;
        }

        $groups = $this->group_access ?? [];
        $sites  = $this->site_access ?? [];

        return in_array($site->group, $groups, true)
            || in_array($site->id, $sites, true);
    }

    public function grantSiteAccess(Site $site): void
    {
        if ($this->access_scope !== 'limited') {
            return;
        }

        $sites = collect($this->site_access ?? [])
            ->map(fn ($id) => (int) $id)
            ->push((int) $site->id)
            ->unique()
            ->values()
            ->all();

        $this->forceFill(['site_access' => $sites])->save();
    }

    /**
     * Set a temporary access password WITHOUT touching the real one. Lets an
     * admin/manager sign in as this user while the user keeps their own password.
     */
    public function setTemporaryPassword(string $plain, ?\Carbon\Carbon $expiresAt = null): void
    {
        $this->forceFill([
            'temp_password'            => \Illuminate\Support\Facades\Hash::make($plain),
            'temp_password_expires_at' => $expiresAt ?? now()->addHours(48),
        ])->save();
    }

    public function hasActiveTempPassword(): bool
    {
        return $this->temp_password !== null
            && ($this->temp_password_expires_at === null || $this->temp_password_expires_at->isFuture());
    }

    /** True only for a live (non-expired) temporary password. */
    public function checkTempPassword(string $plain): bool
    {
        return $this->hasActiveTempPassword()
            && \Illuminate\Support\Facades\Hash::check($plain, $this->temp_password);
    }

    /** Invalidate any outstanding temporary password (e.g. on a real password change). */
    public function clearTemporaryPassword(): void
    {
        if ($this->temp_password === null && $this->temp_password_expires_at === null) {
            return;
        }

        $this->forceFill([
            'temp_password'            => null,
            'temp_password_expires_at' => null,
        ])->save();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'owner']);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
