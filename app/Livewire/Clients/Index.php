<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;

#[Layout('components.layouts.app')]
#[Title('Clients')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $sortBy = 'company_name';

    #[Url]
    public string $sortDir = 'asc';

    /** Whitelist to prevent SQL injection via sortBy from URL */
    private array $allowedSorts = ['company_name', 'contact_name', 'status', 'created_at'];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
    }

    public function deleteClient(int $id): void
    {
        $client = Client::findOrFail($id);
        $this->authorize('delete', $client);
        $client->delete();
        session()->flash('message', 'Client deleted.');
    }

    #[On('client-saved')]
    public function refreshList(): void
    {
        // Livewire re-renders automatically
    }

    public function render()
    {
        $sortBy = in_array($this->sortBy, $this->allowedSorts) ? $this->sortBy : 'company_name';
        $sortDir = in_array($this->sortDir, ['asc', 'desc']) ? $this->sortDir : 'asc';

        $clients = Client::query()
            ->withCount('sites')
            ->when($this->search, fn($q) => $q->where('company_name', 'like', "%{$this->search}%")
                ->orWhere('contact_name', 'like', "%{$this->search}%")
                ->orWhere('contact_email', 'like', "%{$this->search}%"))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->orderBy($sortBy, $sortDir)
            ->paginate(15);

        return view('livewire.clients.index', compact('clients'));
    }
}
