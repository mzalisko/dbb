<?php

namespace App\Livewire;

use App\Models\ContactEntry;
use App\Models\Site;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Layout('components.layouts.app')]
#[Title('Браузер даних')]
class DataBrowser extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'phone';

    public array $selected = [];

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

    public function render()
    {
        $entries = ContactEntry::query()
            ->with('site')
            ->where('type', $this->typeFilter)
            ->where('visible', true)
            ->when($this->search, fn($q) => $q->where('value', 'like', "%{$this->search}%")
                ->orWhere('label', 'like', "%{$this->search}%"))
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get();

        $totalCount = ContactEntry::where('visible', true)->count();

        return view('livewire.data-browser', compact('entries', 'totalCount'));
    }
}
