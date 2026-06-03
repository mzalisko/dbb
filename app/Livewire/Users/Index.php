<?php

namespace App\Livewire\Users;

use App\Models\Site;
use App\Models\SiteGroup;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Команда')]
class Index extends Component
{
    use WithPagination;

    private const ASSIGNABLE_ROLES = ['admin', 'manager', 'viewer'];

    public string $search = '';
    public ?int $openUserId = null;

    public string $pendingRole = 'viewer';

    // Editable permission matrix: [resource => [action => bool]]
    public array $matrix = [];

    // Site access scope
    public string $accessScope = 'all';
    public array $groupAccess = [];
    public array $siteAccess = [];

    // Password change
    public bool $changingPassword = false;
    public string $newPassword = '';
    public string $confirmPassword = '';
    public string $generatedPassword = '';

    public bool $confirmingDelete = false;

    public function viewUser(int $id): void
    {
        $user = User::findOrFail($id);
        $this->openUserId    = $id;
        $this->pendingRole   = in_array($user->role, self::ASSIGNABLE_ROLES) ? $user->role : 'viewer';
        $this->matrix        = $user->effectivePermissions();
        $this->accessScope   = $user->access_scope ?? 'all';
        $this->groupAccess   = $user->group_access ?? [];
        $this->siteAccess    = array_map('intval', $user->site_access ?? []);
        $this->changingPassword = false;
        $this->newPassword   = '';
        $this->confirmPassword = '';
        $this->generatedPassword = '';
        $this->confirmingDelete = false;
    }

    public function closeUser(): void
    {
        $this->openUserId = null;
        $this->changingPassword = false;
        $this->newPassword = '';
        $this->confirmPassword = '';
        $this->generatedPassword = '';
        $this->confirmingDelete = false;
    }

    public function generateTemporaryPassword(): void
    {
        $user = $this->openUserId ? User::findOrFail($this->openUserId) : null;
        abort_unless($this->canManage($user), 403);

        $password = Str::random(6) . '-' . random_int(1000, 9999) . '-' . Str::random(6);

        // Additive credential: the user's real password is left untouched so they
        // can keep signing in; this temp password is for admin access and expires.
        $user->setTemporaryPassword($password, now()->addHours(48));

        $this->changingPassword = false;
        $this->newPassword = '';
        $this->confirmPassword = '';
        $this->generatedPassword = $password;

        $this->dispatch('toast', type: 'success', message: 'Тимчасовий пароль створено (діє 48 год, не скидає основний).');
    }

    public function selectRole(string $role): void
    {
        if (! in_array($role, self::ASSIGNABLE_ROLES)) {
            return;
        }
        $this->pendingRole = $role;
        // Switching role resets the matrix to that role's defaults; admin can then fine-tune.
        $this->matrix = User::ROLE_PERMISSIONS[$role];
    }

    public function togglePerm(string $resource, string $action): void
    {
        if (! isset($this->matrix[$resource][$action])) {
            return;
        }
        $this->matrix[$resource][$action] = ! $this->matrix[$resource][$action];
    }

    public function setAccessScope(string $scope): void
    {
        $this->accessScope = $scope === 'limited' ? 'limited' : 'all';
    }

    public function toggleGroup(string $name): void
    {
        if (($i = array_search($name, $this->groupAccess, true)) !== false) {
            unset($this->groupAccess[$i]);
            $this->groupAccess = array_values($this->groupAccess);
        } else {
            $this->groupAccess[] = $name;
        }
    }

    public function toggleSite(int $id): void
    {
        if (($i = array_search($id, $this->siteAccess, true)) !== false) {
            unset($this->siteAccess[$i]);
            $this->siteAccess = array_values($this->siteAccess);
        } else {
            $this->siteAccess[] = $id;
        }
    }

    /**
     * Role hierarchy. The owner is a super user: it manages every non-owner
     * (admins included) but is itself untouchable from the team page, and nobody
     * manages themselves here (self-lockout guard). An admin manages only
     * managers/viewers — never other admins or the owner.
     */
    private function canManage(?User $target): bool
    {
        $actor = auth()->user();

        if (! $target || $target->role === 'owner' || $target->id === $actor->id) {
            return false;
        }
        if ($actor->isOwner()) {
            return true;
        }

        return $actor->role === 'admin' && in_array($target->role, ['manager', 'viewer'], true);
    }

    public function saveUser(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $user = User::findOrFail($this->openUserId);

        $isSelf    = $user->id === auth()->id();
        $canManage = $this->canManage($user);

        // Role / permissions / access — only for a user you may manage. (Never for
        // the owner or yourself: that risks a self-lockout.)
        if ($canManage) {
            if (in_array($this->pendingRole, self::ASSIGNABLE_ROLES)) {
                $user->role = $this->pendingRole;
            }

            // Persist custom matrix only when it diverges from the role defaults.
            $defaults = User::ROLE_PERMISSIONS[$this->pendingRole] ?? [];
            $user->permissions = $this->normalizedMatrix() === $defaults ? null : $this->normalizedMatrix();

            $user->access_scope = $this->accessScope === 'limited' ? 'limited' : 'all';
            if ($user->access_scope === 'limited') {
                $user->group_access = array_values($this->groupAccess);
                $user->site_access  = array_values(array_map('intval', $this->siteAccess));
            } else {
                $user->group_access = null;
                $user->site_access  = null;
            }
        }

        // A password change is allowed for your own account or one you manage.
        if ($this->changingPassword && $this->newPassword !== '' && ($isSelf || $canManage)) {
            $this->validate([
                'newPassword'     => 'required|min:8',
                'confirmPassword' => 'required|same:newPassword',
            ]);
            // A real password change erases the old password AND any outstanding
            // temporary access password.
            $user->password = $this->newPassword;
            $user->temp_password = null;
            $user->temp_password_expires_at = null;
            $this->changingPassword = false;
            $this->newPassword = '';
            $this->confirmPassword = '';
            $this->generatedPassword = '';
        }

        $user->save();
        $this->dispatch('toast', type: 'success', message: 'Зміни збережено.');
    }

    private function normalizedMatrix(): array
    {
        $clean = [];
        foreach (User::RESOURCES as $resource => $_) {
            foreach (User::ACTIONS as $action) {
                $clean[$resource][$action] = (bool) ($this->matrix[$resource][$action] ?? false);
            }
        }
        return $clean;
    }

    public function toggleSuspend(int $id): void
    {
        $user = User::findOrFail($id);
        abort_unless($this->canManage($user), 403);

        $user->suspended_at = $user->suspended_at ? null : now();
        $user->save();

        $this->dispatch('toast', type: 'success',
            message: $user->suspended_at ? 'Доступ призупинено.' : 'Доступ відновлено.');
    }

    public function removeUser(int $id): void
    {
        $user = User::findOrFail($id);
        abort_unless($this->canManage($user), 403);

        $user->delete();
        $this->closeUser();
        $this->dispatch('toast', type: 'success', message: 'Користувача видалено.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('user-saved')]
    public function refreshList(): void {}

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 WHEN 'manager' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->paginate(20);

        $openUser = $this->openUserId ? User::find($this->openUserId) : null;

        return view('livewire.users.index', [
            'users'     => $users,
            'openUser'  => $openUser,
            'resources' => User::RESOURCES,
            'actions'   => User::ACTIONS,
            'allGroups' => SiteGroup::orderBy('name')->get(['name', 'color']),
            'allSites'  => Site::orderBy('name')->get(['id', 'name', 'group']),
        ]);
    }
}
