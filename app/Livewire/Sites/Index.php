<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;

#[Layout('components.layouts.app')]
#[Title('Sites')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $clientId = null;

    #[Url]
    public string $sortBy = 'name';

    #[Url]
    public string $sortDir = 'asc';

    /** Whitelist to prevent SQL injection via sortBy from URL */
    private array $allowedSorts = ['name', 'url', 'status', 'wp_version', 'last_checked_at'];

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
    }

    public function deleteSite(int $id): void
    {
        $site = Site::with('client')->findOrFail($id);
        $this->authorize('delete', $site);
        $site->delete();
        session()->flash('message', 'Site deleted.');
    }

    #[On('site-saved')]
    public function refreshList(): void
    {
        // Livewire re-renders automatically
    }

    public function render()
    {
        $sortBy = in_array($this->sortBy, $this->allowedSorts) ? $this->sortBy : 'name';
        $sortDir = in_array($this->sortDir, ['asc', 'desc']) ? $this->sortDir : 'asc';

        $sites = Site::query()
            ->with('client')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('url', 'like', "%{$this->search}%"))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->clientId, fn($q) => $q->where('client_id', $this->clientId))
            ->orderBy($sortBy, $sortDir)
            ->paginate(15);

        return view('livewire.sites.index', compact('sites'));
    }
}
