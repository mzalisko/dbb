<?php

namespace App\Livewire\Sites;

use App\Models\Client;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Models\SiteGroup;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Сайти')]
class Index extends Component
{
    #[Url(as: 'group')]
    public string $urlGroup = '';

    // Create site
    public string $createName = '';
    public string $createUrl = '';
    public string $createGroupId = '';
    public string $createStatus = 'active';

    public ?int $confirmDeleteSiteId = null;
    public string $confirmDeleteSiteName = '';
    public string $confirmDeleteSiteTypedName = '';

    public ?int $cloneSourceSiteId = null;
    public string $cloneSourceSiteName = '';
    public string $cloneName = '';
    public string $cloneKeyMode = 'generate';
    public bool $cloneCopyGroup = true;
    public bool $cloneCopySettings = true;
    public bool $cloneCopyGeo = true;
    public bool $cloneCopyCategories = true;
    public bool $cloneCopyPhones = false;
    public bool $cloneCopyMessengers = false;
    public bool $cloneCopyPrices = false;
    public bool $cloneCopyAddresses = false;
    public bool $cloneCopySocials = false;
    public bool $cloneCopyCustom = false;

    public function createSite(): void
    {
        $this->authorize('create', Site::class);

        $this->validate([
            'createName' => 'required|string|max:100',
            'createUrl' => 'nullable|url|max:255',
            'createStatus' => 'in:active,maintenance,offline',
        ]);

        $client = Client::first();
        if (! $client) {
            $this->addError('createName', 'Спочатку додайте клієнта');
            return;
        }

        $group = $this->createGroupId ? SiteGroup::find($this->createGroupId) : null;

        Site::create([
            'client_id' => $client->id,
            'name' => $this->createName,
            'url' => $this->createUrl ?: null,
            'group' => $group?->name,
            'group_color' => $group?->color,
            'status' => $this->createStatus,
        ]);

        $this->reset('createName', 'createUrl', 'createGroupId', 'createStatus');
        $this->createStatus = 'active';
        $this->dispatch('site-created');
    }

    #[Renderless]
    public function toggleFavourite(int $id): void
    {
        // Scope to accessible sites so a request can't flip the flag on someone
        // else's site (IDOR) by passing an arbitrary id.
        $site = Site::accessibleTo(auth()->user())->findOrFail($id);
        $site->update(['is_favourite' => ! $site->is_favourite]);
    }

    public function deleteSite(int $id): void
    {
        $site = Site::with('client')->findOrFail($id);
        $this->authorize('delete', $site);
        $siteName = $site->name;
        $site->delete();
        $this->dispatch('toast', type: 'success', message: "Сайт «{$siteName}» видалено");
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

    public function requestCloneSite(int $id): void
    {
        $site = Site::findOrFail($id);
        $this->authorize('update', $site);

        $this->cloneSourceSiteId = $site->id;
        $this->cloneSourceSiteName = $site->name;
        $this->cloneName = $this->suggestCloneName($site->name);
        $this->cloneKeyMode = 'generate';
        $this->cloneCopyGroup = true;
        $this->cloneCopySettings = true;
        $this->cloneCopyGeo = true;
        $this->cloneCopyCategories = true;
        $this->cloneCopyPhones = false;
        $this->cloneCopyMessengers = false;
        $this->cloneCopyPrices = false;
        $this->cloneCopyAddresses = false;
        $this->cloneCopySocials = false;
        $this->cloneCopyCustom = false;
    }

    public function cancelCloneSite(): void
    {
        $this->reset(
            'cloneSourceSiteId',
            'cloneSourceSiteName',
            'cloneName',
            'cloneKeyMode',
            'cloneCopyGroup',
            'cloneCopySettings',
            'cloneCopyGeo',
            'cloneCopyCategories',
            'cloneCopyPhones',
            'cloneCopyMessengers',
            'cloneCopyPrices',
            'cloneCopyAddresses',
            'cloneCopySocials',
            'cloneCopyCustom',
        );
        $this->resetErrorBag();
    }

    public function confirmCloneSite(): void
    {
        $this->validate([
            'cloneSourceSiteId' => 'required|integer|exists:sites,id',
            'cloneName' => 'required|string|max:100',
            'cloneKeyMode' => 'required|in:generate,keep',
        ]);

        $source = Site::with('contactEntries')->findOrFail($this->cloneSourceSiteId);
        $this->authorize('update', $source);
        $this->authorize('create', Site::class); // a clone creates a new site

        $clone = DB::transaction(function () use ($source) {
            $attributes = [
                'client_id' => $source->client_id,
                'name' => trim($this->cloneName),
                'url' => $source->url,
                'status' => 'maintenance',
                'api_key' => $this->cloneKeyMode === 'keep' ? $source->api_key : Site::generateApiKey(),
                'is_favourite' => false,
                'last_checked_at' => null,
            ];

            if ($this->cloneCopyGroup) {
                $attributes['group'] = $source->group;
                $attributes['group_color'] = $source->group_color;
            } else {
                $attributes['group'] = null;
                $attributes['group_color'] = null;
            }

            if ($this->cloneCopySettings) {
                $attributes['wp_version'] = $source->wp_version;
                $attributes['php_version'] = $source->php_version;
                $attributes['failover_enabled'] = $source->failover_enabled;
                $attributes['failover_interval'] = $source->failover_interval;
                $attributes['failover_threshold'] = $source->failover_threshold;
            }

            if ($this->cloneCopyGeo) {
                $attributes['geo_tabs'] = $source->geo_tabs;
                $attributes['geo_rules'] = $source->geo_rules;
            }

            if ($this->cloneCopyCategories) {
                $attributes['data_categories'] = $source->data_categories;
                $attributes['messenger_kinds'] = $source->messenger_kinds;
            }

            $clone = Site::create($attributes);
            $types = $this->cloneDataTypes();

            if ($types !== []) {
                $this->copyContactEntries($source, $clone, $types);
            }

            auth()->user()?->grantSiteAccess($clone);

            return $clone;
        });

        $this->dispatch('site-created');
        $this->dispatch('toast', type: 'success', message: 'Сайт створено на основі каркасу: ' . $clone->name);
        $this->cancelCloneSite();
    }

    private function suggestCloneName(string $name): string
    {
        return $name . ' (копія)';
    }

    private function cloneDataTypes(): array
    {
        return collect([
            'phone' => $this->cloneCopyPhones,
            'messenger' => $this->cloneCopyMessengers,
            'price' => $this->cloneCopyPrices,
            'address' => $this->cloneCopyAddresses,
            'social' => $this->cloneCopySocials,
            'custom' => $this->cloneCopyCustom,
        ])
            ->filter()
            ->keys()
            ->values()
            ->all();
    }

    private function copyContactEntries(Site $source, Site $clone, array $types): void
    {
        $entries = $source->contactEntries->whereIn('type', $types);
        $idMap = [];

        foreach ($entries->whereNull('parent_id') as $primary) {
            $copy = $this->copyContactEntry($primary, $clone);
            $idMap[$primary->id] = $copy->id;
        }

        foreach ($entries->whereNotNull('parent_id') as $backup) {
            if (! isset($idMap[$backup->parent_id])) {
                continue;
            }

            $copy = $this->copyContactEntry($backup, $clone);
            $copy->forceFill(['parent_id' => $idMap[$backup->parent_id]])->save();
        }
    }

    private function copyContactEntry(ContactEntry $entry, Site $clone): ContactEntry
    {
        $copy = $entry->replicate();
        $copy->site_id = $clone->id;
        $copy->parent_id = null;
        $copy->save();

        return $copy;
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
        if (! $this->confirmDeleteSiteId) {
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
        $user = auth()->user();
        $canPhones = $user?->canEntryType('phone') ?? false;
        $canMessengers = $user?->canEntryType('messenger') ?? false;
        $canPrices = $user?->canEntryType('price') ?? false;

        $groups = Site::accessibleTo($user)->whereNotNull('group')
            ->selectRaw('`group`, group_color, count(*) as sites_count')
            ->groupBy('group', 'group_color')
            ->get();

        $groupFilter = strtolower($this->urlGroup);
        $counts = [];

        if ($canPhones) {
            $counts['contactEntries as active_phones_count'] = fn ($q) => $q
                ->where('type', 'phone')
                ->where('role', 'primary')
                ->where('visible', true);
            $counts['contactEntries as backup_phones_count'] = fn ($q) => $q
                ->where('type', 'phone')
                ->where('role', 'backup')
                ->where('visible', true);
        }

        if ($canMessengers) {
            $counts['contactEntries as active_messengers_count'] = fn ($q) => $q
                ->where('type', 'messenger')
                ->where('role', 'primary')
                ->where('visible', true);
            $counts['contactEntries as backup_messengers_count'] = fn ($q) => $q
                ->where('type', 'messenger')
                ->where('role', 'backup')
                ->where('visible', true);
        }

        if ($canPrices) {
            $counts['contactEntries as active_prices_count'] = fn ($q) => $q
                ->where('type', 'price')
                ->where('role', 'primary')
                ->where('visible', true);
        }

        $sitesQuery = Site::query()
            ->accessibleTo($user)
            ->with('client')
            ->withCount($counts);

        if ($groupFilter !== '') {
            $sitesQuery->whereRaw('LOWER(`group`) = ?', [$groupFilter]);
        }

        $sites = $sitesQuery->orderBy('name')->get();
        $siteGroups = SiteGroup::orderBy('name')->get();
        $urlGroup = $groupFilter;

        return view('livewire.sites.index', compact('sites', 'groups', 'siteGroups', 'urlGroup', 'canPhones', 'canMessengers', 'canPrices'));
    }
}
