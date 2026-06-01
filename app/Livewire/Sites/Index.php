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

    public ?int $confirmDeleteSiteId = null;
    public string $confirmDeleteSiteName = '';
    public string $confirmDeleteSiteTypedName = '';

    public function createSite(): void
    {
        $this->validate([
            'createName'   => 'required|string|max:100',
            'createUrl'    => 'nullable|url|max:255',
            'createStatus' => 'in:active,maintenance,offline',
        ]);

        $client = Client::first();
        if (!$client) {
            $this->addError('createName', 'Спочатку додайте клієнта');
            return;
        }

        $group = $this->createGroupId ? SiteGroup::find($this->createGroupId) : null;

        Site::create([
            'client_id'   => $client->id,
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

    public function assignSiteGroup(int $siteId, int $groupId): void
    {
        $site = Site::findOrFail($siteId);
        $this->authorize('update', $site);

        $group = SiteGroup::findOrFail($groupId);

        $site->update([
            'group' => $group->name,
            'group_color' => $group->color,
        ]);

        $this->dispatch('site-group-updated', id: $site->id, group: strtolower($group->name));

        $this->dispatch('toast', type: 'success', message: 'Групу сайту оновлено');
    }

    public function requestDeleteSite(int $id): void
    {
        $site = Site::findOrFail($id);
        $this->authorize('delete', $site);

        $this->confirmDeleteSiteId = $site->id;
        $this->confirmDeleteSiteName = $site->name;
    }

    public function cancelDeleteSite(): void
    {
        $this->reset('confirmDeleteSiteId', 'confirmDeleteSiteName', 'confirmDeleteSiteTypedName');
        $this->resetErrorBag('confirmDeleteSiteTypedName');
    }

    public function confirmDeleteSite(): void
    {
        if (!$this->confirmDeleteSiteId) {
            return;
        }

        $site = Site::findOrFail($this->confirmDeleteSiteId);

        if (trim($this->confirmDeleteSiteTypedName) !== $site->name) {
            $this->addError('confirmDeleteSiteTypedName', 'Введіть точну назву сайту для підтвердження.');
            return;
        }

        $this->deleteSite($this->confirmDeleteSiteId);
        $this->cancelDeleteSite();
        $this->dispatch('toast', type: 'success', message: 'Сайт видалено');
    }

    #[On('site-saved')]
    public function refreshList(): void {}

    public function setGroupFilter(string $group): void
    {
        $group = strtolower($group);
        $this->urlGroup = $group === 'all' ? '' : $group;
    }

    public function render()
    {
        $groups = \App\Models\Site::whereNotNull('group')
            ->selectRaw('`group`, group_color, count(*) as sites_count')
            ->groupBy('group', 'group_color')
            ->get();

        $groupFilter = strtolower($this->urlGroup);

        $sitesQuery = Site::query()
            ->with('client')
            ->withCount([
                'contactEntries as active_phones_count' => fn($q) => $q
                    ->where('type', 'phone')
                    ->where('role', 'primary')
                    ->where('visible', true),
                'contactEntries as backup_phones_count' => fn($q) => $q
                    ->where('type', 'phone')
                    ->where('role', 'backup')
                    ->where('visible', true),
                'contactEntries as active_messengers_count' => fn($q) => $q
                    ->where('type', 'messenger')
                    ->where('role', 'primary')
                    ->where('visible', true),
                'contactEntries as backup_messengers_count' => fn($q) => $q
                    ->where('type', 'messenger')
                    ->where('role', 'backup')
                    ->where('visible', true),
                'contactEntries as active_prices_count' => fn($q) => $q
                    ->where('type', 'price')
                    ->where('role', 'primary')
                    ->where('visible', true),
            ]);

        if ($groupFilter !== '') {
            $sitesQuery->whereRaw('LOWER(`group`) = ?', [$groupFilter]);
        }

        $sites = $sitesQuery->orderBy('name')->get();

        $siteGroups = SiteGroup::orderBy('name')->get();

        $urlGroup = $groupFilter;

        return view('livewire.sites.index', compact('sites', 'groups', 'siteGroups', 'urlGroup'));
    }
}
