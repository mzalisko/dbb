<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;

#[Layout('components.layouts.app')]
#[Title('Settings')]
class Settings extends Component
{
    // Profile tab
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('nullable|string|max:30')]
    public ?string $phone = '';

    // Organization tab (admin only)
    #[Validate('nullable|string|max:255')]
    public ?string $organization_name = '';

    // Password tab
    #[Validate('nullable|string|min:8|confirmed')]
    public ?string $new_password = '';
    public ?string $new_password_confirmation = '';

    #[Validate('required_with:new_password|string')]
    public ?string $current_password = '';

    public string $activeTab = 'profile';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->organization_name = $user->organization_name ?? '';
    }

    public function saveProfile(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:30',
        ]);

        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);

        session()->flash('profile-saved', true);
    }

    public function saveOrganization(): void
    {
        $this->validate([
            'organization_name' => 'nullable|string|max:255',
        ]);

        auth()->user()->update([
            'organization_name' => $this->organization_name,
        ]);

        session()->flash('org-saved', true);
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => 'required|string|current_password',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        auth()->user()->update([
            'password' => bcrypt($this->new_password),
        ]);

        $this->reset('current_password', 'new_password', 'new_password_confirmation');
        session()->flash('password-changed', true);
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
