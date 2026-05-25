<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.app')]
#[Title('Activity Log')]
class ActivityLog extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = \App\Models\ActivityLog::with('user')
            ->when($this->search, fn($q) => $q->where('action', 'like', "%{$this->search}%"))
            ->latest('created_at')
            ->paginate(25);

        return view('livewire.activity-log', compact('logs'));
    }
}
