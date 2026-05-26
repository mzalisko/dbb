<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;

#[Layout('components.layouts.app')]
#[Title('Сайти')]
class Index extends Component
{
    #[Url]
    public string $groupFilter = 'all';

    public function deleteSite(int $id): void
    {
        $site = Site::with('client')->findOrFail($id);
        $this->authorize('delete', $site);
        $site->delete();
    }

    #[On('site-saved')]
    public function refreshList(): void {}

    public function render()
    {
        $groups = \App\Models\Site::whereNotNull('group')
            ->selectRaw('`group`, group_color, count(*) as sites_count')
            ->groupBy('group', 'group_color')
            ->get();

        $sites = Site::query()
            ->with('client')
            ->withCount([
                'contactEntries as phones_count'     => fn($q) => $q->where('type', 'phone')->where('visible', true),
                'contactEntries as messengers_count' => fn($q) => $q->where('type', 'messenger')->where('visible', true),
            ])
            ->when($this->groupFilter !== 'all', fn($q) => $q->where('group', $this->groupFilter))
            ->orderBy('name')
            ->get();

        return view('livewire.sites.index', compact('sites', 'groups'));
    }
}
