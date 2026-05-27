<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use App\Models\Client;
use App\Models\SiteGroup;

#[Layout('components.layouts.app')]
#[Title('Сайти')]
class Index extends Component
{
    #[Url(as: 'group')]
    public string $urlGroup = '';

    // Create site
    public string $createName   = '';
    public string $createUrl    = '';
    public string $createGroupId = '';
    public string $createStatus = 'active';

    public function createSite(): void
    {
        $this->validate([
            'createName'   => 'required|string|max:100',
            'createUrl'    => 'nullable|url|max:255',
            'createStatus' => 'in:active,maintenance,offline',
        ]);

        $group = $this->createGroupId ? SiteGroup::find($this->createGroupId) : null;

        Site::create([
            'client_id'   => Client::first()?->id ?? 1,
            'name'        => $this->createName,
            'url'         => $this->createUrl ?: null,
            'group'       => $group?->name,
            'group_color' => $group?->color,
            'status'      => $this->createStatus,
        ]);

        $this->reset('createName', 'createUrl', 'createGroupId', 'createStatus');
        $this->createStatus = 'active';
        $this->dispatch('site-created');
    }

    #[Renderless]
    public function toggleFavourite(int $id): void
    {
        $site = Site::findOrFail($id);
        $site->update(['is_favourite' => !$site->is_favourite]);
    }

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
            ->orderBy('name')
            ->get();

        $siteGroups = SiteGroup::orderBy('name')->get();

        $urlGroup = $this->urlGroup;

        return view('livewire.sites.index', compact('sites', 'groups', 'siteGroups', 'urlGroup'));
    }
}
