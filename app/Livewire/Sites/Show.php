<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Database\Eloquent\Collection;

#[Layout('components.layouts.app')]
#[Title('Сайт')]
class Show extends Component
{
    public Site $site;
    public ?int $openPhoneId = null;

    public bool $failoverEnabled = true;
    public string $failoverInterval = '5min';
    public int $failoverThreshold = 3;

    // ContactEntry CRUD state
    public bool $editingEntry = false;
    public bool $addingEntry = false;
    public ?int $editEntryId = null;
    public string $entryType = 'phone';
    public string $entryValue = '';
    public string $entryLabel = '';
    public string $entryRole = 'primary';
    public string $entryGeoMode = 'all';
    public array $entryCountries = [];
    public ?int $entryParentId = null;
    public string $entryKind = '';
    public string $entryCurrency = 'EUR';
    public ?float $entryPrice = null;
    public ?float $entryOldPrice = null;
    public string $entryPriceUnit = '';
    public string $entrySku = '';

    public function openPhone(int $id): void
    {
        $this->openPhoneId = $id;
    }

    public function closePhone(): void
    {
        $this->openPhoneId = null;
    }

    public function mount(Site $site): void
    {
        $this->authorize('view', $site);
        $this->site = $site->load('client');

        $this->failoverEnabled   = $this->site->failover_enabled ?? true;
        $this->failoverInterval  = $this->site->failover_interval ?? '5min';
        $this->failoverThreshold = $this->site->failover_threshold ?? 3;
    }

    private function filterForGeo(Collection $entries, string $geo): Collection
    {
        if ($geo === 'all') return $entries;
        return $entries->filter(fn($e) => $e->visibleForGeo($geo))->values();
    }

    public function saveFailover(): void
    {
        $this->authorize('update', $this->site);
        $this->site->update([
            'failover_enabled'   => $this->failoverEnabled,
            'failover_interval'  => $this->failoverInterval,
            'failover_threshold' => $this->failoverThreshold,
        ]);
        $this->dispatch('toast', message: 'Налаштування збережено');
    }

    public function toggleFailover(): void
    {
        $this->authorize('update', $this->site);
        $this->failoverEnabled = !$this->failoverEnabled;
        $this->site->update(['failover_enabled' => $this->failoverEnabled]);
    }

    public function editEntry(int $id): void
    {
        $entry = \App\Models\ContactEntry::findOrFail($id);
        $this->authorize('update', $entry);
        $this->editEntryId    = $entry->id;
        $this->entryType      = $entry->type;
        $this->entryValue     = $entry->value ?? '';
        $this->entryLabel     = $entry->label ?? '';
        $this->entryRole      = $entry->role;
        $this->entryGeoMode   = $entry->geo_mode;
        $this->entryCountries = $entry->countries ?? [];
        $this->entryParentId  = $entry->parent_id;
        $this->entryKind      = $entry->kind ?? '';
        $this->entryCurrency  = $entry->currency ?? 'EUR';
        $this->entryPrice     = $entry->price;
        $this->entryOldPrice  = $entry->old_price;
        $this->entryPriceUnit = $entry->price_unit ?? '';
        $this->entrySku       = $entry->sku ?? '';
        $this->editingEntry   = true;
        $this->addingEntry    = false;
    }

    public function addEntry(string $type, ?int $parentId = null): void
    {
        $this->authorize('create', \App\Models\ContactEntry::class);
        $this->resetEntryForm();
        $this->entryType    = $type;
        $this->entryParentId = $parentId;
        if ($parentId) {
            $this->entryRole = 'backup';
        }
        $this->addingEntry  = true;
        $this->editingEntry = false;
    }

    public function saveEntry(): void
    {
        $rules = [
            'entryRole'    => 'required|in:primary,backup,hidden',
            'entryGeoMode' => 'required|in:all,only,except',
            'entryCountries' => 'required_if:entryGeoMode,only|required_if:entryGeoMode,except|array',
        ];

        if ($this->entryType === 'phone') {
            $rules['entryValue'] = 'required|max:500';
            $rules['entryLabel'] = 'nullable|max:255';
        } elseif ($this->entryType === 'messenger') {
            $rules['entryValue'] = 'required|max:500';
            $rules['entryKind']  = 'required|in:telegram,whatsapp,viber,messenger,signal,skype';
        } elseif ($this->entryType === 'price') {
            $rules['entryPrice']    = 'required|numeric|min:0';
            $rules['entryCurrency'] = 'required|size:3';
            $rules['entrySku']      = 'required|max:255';
        }

        $this->validate($rules);

        $data = [
            'site_id'    => $this->site->id,
            'type'       => $this->entryType,
            'kind'       => $this->entryKind ?: null,
            'value'      => $this->entryValue,
            'label'      => $this->entryLabel ?: null,
            'role'       => $this->entryRole,
            'geo_mode'   => $this->entryGeoMode,
            'countries'  => $this->entryCountries ?: null,
            'parent_id'  => $this->entryParentId,
            'currency'   => $this->entryCurrency ?: null,
            'price'      => $this->entryPrice,
            'old_price'  => $this->entryOldPrice,
            'price_unit' => $this->entryPriceUnit ?: null,
            'sku'        => $this->entrySku ?: null,
            'visible'    => true,
            'order'      => 1,
        ];

        if ($this->editEntryId) {
            $entry = \App\Models\ContactEntry::findOrFail($this->editEntryId);
            $this->authorize('update', $entry);
            $entry->update($data);
        } else {
            $this->authorize('create', \App\Models\ContactEntry::class);
            $data['order'] = $this->site->contactEntries()->where('type', $this->entryType)->max('order') + 1;
            \App\Models\ContactEntry::create($data);
        }

        $this->resetEntryForm();
        $this->dispatch('toast', type: 'success', message: 'Збережено');
    }

    public function deleteEntry(int $id): void
    {
        $entry = \App\Models\ContactEntry::findOrFail($id);
        $this->authorize('delete', $entry);
        $entry->delete();
        $this->resetEntryForm();
        $this->closePhone();
        $this->dispatch('toast', type: 'success', message: 'Видалено');
    }

    public function toggleEntryVisibility(int $id): void
    {
        $entry = \App\Models\ContactEntry::findOrFail($id);
        $this->authorize('update', $entry);
        $entry->update(['visible' => !$entry->visible]);
    }

    public function resetEntryForm(): void
    {
        $this->editingEntry   = false;
        $this->addingEntry    = false;
        $this->editEntryId    = null;
        $this->entryType      = 'phone';
        $this->entryValue     = '';
        $this->entryLabel     = '';
        $this->entryRole      = 'primary';
        $this->entryGeoMode   = 'all';
        $this->entryCountries = [];
        $this->entryParentId  = null;
        $this->entryKind      = '';
        $this->entryCurrency  = 'EUR';
        $this->entryPrice     = null;
        $this->entryOldPrice  = null;
        $this->entryPriceUnit = '';
        $this->entrySku       = '';
    }

    public function render()
    {
        $allPhones = $this->site->contactEntries()->where('type', 'phone')->where('visible', true)->orderBy('order')->with('backups')->get();
        $allMsgs   = $this->site->contactEntries()->where('type', 'messenger')->where('visible', true)->orderBy('order')->with('backups')->get();

        // Overview: 3 geo pools with their primaries and backups
        $geos = [
            ['key' => 'PL',    'flag' => '🇵🇱', 'label' => 'Польща'],
            ['key' => 'UA',    'flag' => '🇺🇦', 'label' => 'Україна'],
            ['key' => 'world', 'flag' => '🌐',  'label' => 'Світ'],
        ];
        foreach ($geos as &$geo) {
            $geoPhones = $this->filterForGeo($allPhones, $geo['key']);
            $geoMsgs   = $this->filterForGeo($allMsgs,   $geo['key']);
            $geo['primaryPhone'] = $geoPhones->firstWhere('role', 'primary');
            $geo['primaryMsg']   = $geoMsgs->firstWhere('role', 'primary');
            $geo['backupPhones'] = $geo['primaryPhone']
                ? $allPhones->where('parent_id', $geo['primaryPhone']->id)->values()
                : collect();
            $geo['backupMsgs']   = $geo['primaryMsg']
                ? $allMsgs->where('parent_id', $geo['primaryMsg']->id)->values()
                : collect();
        }
        unset($geo);

        // Pre-render all 4 geo variants so category/geo switching is pure Alpine (no Livewire round-trip)
        $phonePrimariesByGeo = [];
        $msgPrimariesByGeo   = [];
        foreach (['all', 'world', 'PL', 'UA'] as $gk) {
            $fp = $gk === 'all' ? $allPhones : $this->filterForGeo($allPhones, $gk);
            $phonePrimariesByGeo[$gk] = $fp->filter(fn($e) => is_null($e->parent_id))->values();
            $fm = $gk === 'all' ? $allMsgs : $this->filterForGeo($allMsgs, $gk);
            $msgPrimariesByGeo[$gk] = $fm->filter(fn($e) => is_null($e->parent_id))->values();
        }
        // Settings tab still uses $phonePrimaries (all geo, visible primaries)
        $phonePrimaries = $phonePrimariesByGeo['all'];
        $msgPrimaries   = $msgPrimariesByGeo['all'];

        // Prices
        $allPrices = $this->site->contactEntries()->where('type', 'price')->where('visible', true)->get();
        $priceBySku = $allPrices->groupBy('sku');

        // Activity
        $activityLogs = ActivityLog::where('subject_type', Site::class)
            ->where('subject_id', $this->site->id)
            ->with('user')
            ->latest()
            ->take(20)
            ->get();

        // All entries including hidden (for full Data tab list)
        $allPhonesAll = $this->site->contactEntries()->where('type', 'phone')->orderBy('order')->with('backups')->get();
        $allMsgsAll   = $this->site->contactEntries()->where('type', 'messenger')->orderBy('order')->with('backups')->get();
        $allPricesAll = $this->site->contactEntries()->where('type', 'price')->orderBy('order')->get();

        // Extra categories
        $addressCount = $this->site->contactEntries()->where('type', 'address')->count();
        $socialCount  = $this->site->contactEntries()->where('type', 'social')->count();

        // Messenger kinds grouped (for platform pills)
        $msgByKind = $allMsgs->groupBy('kind');

        // Prices grouped by SKU (all including hidden)
        $priceBySkuAll = $allPricesAll->groupBy('sku');

        // Counts
        $phoneCount = $allPhones->count();
        $msgCount   = $allMsgs->count();
        $priceCount = $allPrices->count();

        return view('livewire.sites.show', compact(
            'geos',
            'allPhones', 'allMsgs', 'allPhonesAll', 'allMsgsAll', 'allPricesAll',
            'phonePrimaries', 'msgPrimaries',
            'phonePrimariesByGeo', 'msgPrimariesByGeo',
            'msgByKind',
            'priceBySku', 'priceBySkuAll',
            'activityLogs',
            'phoneCount', 'msgCount', 'priceCount',
            'addressCount', 'socialCount',
        ));
    }
}
