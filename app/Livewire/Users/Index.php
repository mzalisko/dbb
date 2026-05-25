<?php

namespace App\Livewire\Users;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;

#[Layout('components.layouts.app')]
#[Title('Team')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function removeUser(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_if($id === auth()->id(), 403, 'Cannot remove yourself.');

        User::findOrFail($id)->delete();
        session()->flash('message', 'User removed from team.');
    }

    #[On('user-saved')]
    public function refreshList(): void
    {
        // Livewire re-renders automatically
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.users.index', compact('users'));
    }
}
