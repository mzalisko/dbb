<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Validate;

class InviteForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required|in:admin,manager,viewer')]
    public string $role = 'viewer';

    public function save(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $this->validate();

        $user = new User([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => bcrypt(Str::random(16)),
        ]);
        // role is guarded against mass assignment — set explicitly in this
        // admin-only flow (validated against the assignable list above).
        $user->forceFill(['role' => $this->role])->save();

        $this->reset();
        $this->dispatch('close-modal', 'invite-user');
        $this->dispatch('user-saved');
        $this->dispatch('toast', type: 'success', message: 'Користувача запрошено.');
    }

    public function render()
    {
        return view('livewire.users.invite-form');
    }
}
