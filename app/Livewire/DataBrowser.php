<?php

namespace App\Livewire;

use App\Models\ContactEntry;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Layout('components.layouts.app')]
#[Title('Браузер даних')]
class DataBrowser extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = '';

    public array $selected = [];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }

    public function toggleSelected(int $id): void
    {
        if (in_array($id, $this->selected)) {
            $this->selected = array_values(array_filter($this->selected, fn($i) => $i !== $id));
        } else {
            $this->selected[] = $id;
        }
    }

    public function clearSelected(): void
    {
        $this->selected = [];
    }

    public function bulkToggleVisibility(): void
    {
        ContactEntry::whereIn('id', $this->selected)->each(function ($entry) {
            $entry->update(['visible' => !$entry->visible]);
        });
        $this->selected = [];
    }

    public function render()
    {
        $entries = ContactEntry::query()
            ->with('site')
            ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
            ->when($this->search, fn($q) => $q->where(fn($q2) =>
                $q2->where('value', 'like', "%{$this->search}%")
                   ->orWhere('label', 'like', "%{$this->search}%")
            ))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $totalCount = ContactEntry::count();

        return view('livewire.data-browser', compact('entries', 'totalCount'));
    }
}
