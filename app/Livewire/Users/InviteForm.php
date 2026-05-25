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

    #[Validate('required|in:admin,member')]
    public string $role = 'member';

    public function save(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $this->validate();

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt(Str::random(16)),
            'role' => $this->role,
        ]);

        $this->reset();
        $this->dispatch('close-modal', 'invite-user');
        $this->dispatch('user-saved');
    }

    public function render()
    {
        return view('livewire.users.invite-form');
    }
}
