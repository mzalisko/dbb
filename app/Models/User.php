<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[ObservedBy(UserObserver::class)]
#[Fillable(['name', 'email', 'password', 'role', 'permissions', 'access_scope', 'group_access', 'site_access', 'organization_name', 'phone', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Resources and actions used in the permission matrix. */
    public const RESOURCES = [
        'sites'   => 'Сайти',
        'phones'  => 'Телефони',
        'groups'  => 'Групи сайтів',
        'team'    => 'Команда',
        'api'     => 'API ключі',
    ];

    public const ACTIONS = ['read', 'create', 'edit', 'delete'];

    /** Default permission matrices per role. */
    public const ROLE_PERMISSIONS = [
        'owner' => [
            'sites'  => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'phones' => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'groups' => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'team'   => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'api'    => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
        ],
        'admin' => [
            'sites'  => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'phones' => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'groups' => ['read' => true, 'create' => true,  'edit' => true,  'delete' => false],
            'team'   => ['read' => true, 'create' => true,  'edit' => true,  'delete' => true],
            'api'    => ['read' => true, 'create' => true,  'edit' => false, 'delete' => true],
        ],
        'manager' => [
            'sites'  => ['read' => true,  'create' => true,  'edit' => true,  'delete' => false],
            'phones' => ['read' => true,  'create' => true,  'edit' => true,  'delete' => false],
            'groups' => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'team'   => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'api'    => ['read' => false, 'create' => false, 'edit' => false, 'delete' => false],
        ],
        'viewer' => [
            'sites'  => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'phones' => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'groups' => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'team'   => ['read' => true,  'create' => false, 'edit' => false, 'delete' => false],
            'api'    => ['read' => false, 'create' => false, 'edit' => false, 'delete' => false],
        ],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'suspended_at'      => 'datetime',
            'password'          => 'hashed',
            'permissions'       => 'array',
            'group_access'      => 'array',
            'site_access'       => 'array',
        ];
    }

    /** Effective permission matrix: custom override if present, else role defaults. */
    public function effectivePermissions(): array
    {
        $defaults = self::ROLE_PERMISSIONS[$this->role] ?? self::ROLE_PERMISSIONS['viewer'];

        if (! is_array($this->permissions)) {
            return $defaults;
        }

        // Merge custom over defaults so newly-added resources still have a baseline.
        $merged = $defaults;
        foreach ($this->permissions as $resource => $actions) {
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
        return (bool) (self::effectivePermissions()[$resource][$action] ?? false);
    }

    /** Whether the user can reach a given site, honouring the access scope. */
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
