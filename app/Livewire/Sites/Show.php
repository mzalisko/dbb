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
    public int $entryFormKey = 0;

    // Geo filter tabs (dynamic, per session)
    public array $geoTabs = ['UA', 'RU', 'BY'];
    public string $newGeoTab = '';

    // Geo isolation rules for conflict detection
    public array $geoRules = [['id' => 1, 'groups' => [['UA'], ['RU', 'BY']]]];
    public int $nextRuleId = 2;
    public string $newRuleA = '';
    public string $newRuleB = '';

    // Assign-backup modal state
    public bool $assigningBackup = false;
    public ?int $assignBackupPhoneId = null;

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

    /** Tag-based filter: only entries where geo_mode='only' and $geo in countries. */
    private function filterByGeoTag(Collection $entries, string $geo): Collection
    {
        if ($geo === 'all') return $entries;
        return $entries->filter(
            fn($e) => $e->geo_mode === 'only' && in_array($geo, $e->countries ?? [])
        )->values();
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
        $this->entryFormKey++;
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

    public function addEntry(string $type, ?int $parentId = null, ?string $geoTag = null): void
    {
        $this->authorize('create', \App\Models\ContactEntry::class);
        $this->resetEntryForm();
        $this->entryType     = $type;
        $this->entryParentId = $parentId;
        if ($parentId) {
            $this->entryRole = 'backup';
        }
        if ($geoTag) {
            $this->entryCountries = [$geoTag];
            $this->entryGeoMode   = 'only';
        }
        $this->addingEntry  = true;
        $this->editingEntry = false;
        $this->entryFormKey++;
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
            'parent_id'  => $this->entryRole === 'backup' ? $this->entryParentId : null,
            'currency'   => $this->entryCurrency ?: null,
            'price'      => $this->entryPrice,
            'old_price'  => $this->entryOldPrice,
            'price_unit' => $this->entryPriceUnit ?: null,
            'sku'        => $this->entrySku ?: null,
            'visible'    => $this->entryRole !== 'hidden',
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

        $savedType = $this->entryType;
        $this->resetEntryForm();
        $this->dispatch('toast', type: 'success', message: 'Збережено');
        if ($savedType === 'phone') {
            $this->dispatch('phones-updated');
        }
    }

    public function promoteEntry(int $id): void
    {
        $entry = \App\Models\ContactEntry::findOrFail($id);
        $this->authorize('update', $entry);
        $entry->update([
            'role'      => 'primary',
            'parent_id' => null,
            'visible'   => true,
        ]);
        $this->dispatch('toast', type: 'success', message: 'Переведено в активні');
        if ($entry->type === 'phone') {
            $this->dispatch('phones-updated');
        }
    }

    public function deleteEntry(int $id): void
    {
        $entry = \App\Models\ContactEntry::findOrFail($id);
        $this->authorize('delete', $entry);
        $isPhone = $entry->type === 'phone';
        $entry->delete();
        $this->resetEntryForm();
        $this->closePhone();
        $this->dispatch('toast', type: 'success', message: 'Видалено');
        if ($isPhone) {
            $this->dispatch('phones-updated');
        }
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

    public function reorderBackups(int $parentId, array $orderedIds): void
    {
        $this->authorize('update', $this->site);
        foreach ($orderedIds as $order => $id) {
            \App\Models\ContactEntry::where('id', $id)
                ->where('parent_id', $parentId)
                ->where('site_id', $this->site->id)
                ->update(['order' => $order + 1]);
        }
    }

    public function reorderEntries(array $orderedIds): void
    {
        $this->authorize('update', $this->site);
        foreach ($orderedIds as $order => $id) {
            \App\Models\ContactEntry::where('id', $id)
                ->where('site_id', $this->site->id)
                ->update(['order' => $order + 1]);
        }
    }

    public function openAssignModal(int $id): void
    {
        $entry = \App\Models\ContactEntry::findOrFail($id);
        $this->authorize('update', $entry);
        $this->assignBackupPhoneId = $id;
        $this->assigningBackup = true;
    }

    public function closeAssignModal(): void
    {
        $this->assigningBackup = false;
        $this->assignBackupPhoneId = null;
    }

    public function setEntryRole(string $role): void
    {
        if (!in_array($role, ['primary', 'backup', 'hidden'])) return;
        $this->entryRole = $role;
        if ($role !== 'backup') {
            $this->entryParentId = null;
        }
    }

    public function setEntryGeoMode(string $mode): void
    {
        if (!in_array($mode, ['all', 'only', 'except'])) return;
        $this->entryGeoMode = $mode;
    }

    public function assignAsBackup(int $id, int $parentId): void
    {
        $entry = \App\Models\ContactEntry::findOrFail($id);
        $this->authorize('update', $entry);
        \App\Models\ContactEntry::where('id', $parentId)
            ->where('site_id', $this->site->id)
            ->firstOrFail();
        $entry->update([
            'role'      => 'backup',
            'parent_id' => $parentId,
            'visible'   => true,
        ]);
        $this->assigningBackup = false;
        $this->assignBackupPhoneId = null;
        $this->dispatch('toast', type: 'success', message: 'Переведено в резерв');
    }

    public function toggleCountry(string $code): void
    {
        if (in_array($code, $this->entryCountries)) {
            $this->entryCountries = array_values(
                array_filter($this->entryCountries, fn($c) => $c !== $code)
            );
            if (empty($this->entryCountries)) {
                $this->entryGeoMode = 'all';
            }
        } else {
            $this->entryCountries[] = $code;
            $this->entryGeoMode = 'only';
        }
    }

    public function setGeoAll(): void
    {
        $this->entryGeoMode = 'all';
        $this->entryCountries = [];
    }

    public function addGeoTab(): void
    {
        $code = strtoupper(trim($this->newGeoTab));
        if (!$code || in_array($code, $this->geoTabs) || strlen($code) > 3) {
            $this->newGeoTab = '';
            return;
        }
        $this->geoTabs[] = $code;
        $this->newGeoTab = '';
    }

    public function removeGeoTab(string $code): void
    {
        $this->geoTabs = array_values(array_filter($this->geoTabs, fn($t) => $t !== $code));
    }

    public function addGeoRule(): void
    {
        if (!$this->newRuleA || !$this->newRuleB || $this->newRuleA === $this->newRuleB) return;
        $this->geoRules[] = [
            'id'     => $this->nextRuleId++,
            'groups' => [[$this->newRuleA], [$this->newRuleB]],
        ];
        $this->newRuleA = '';
        $this->newRuleB = '';
    }

    public function removeGeoRule(int $id): void
    {
        $this->geoRules = array_values(array_filter($this->geoRules, fn($r) => $r['id'] !== $id));
    }

    public function render()
    {
        $allPhones = $this->site->contactEntries()->where('type', 'phone')->where('visible', true)->orderBy('order')->with('backups')->get();
        $allMsgs   = $this->site->contactEntries()->where('type', 'messenger')->where('visible', true)->orderBy('order')->with('backups')->get();

        // Overview: what each geo group sees + "all" universal column
        $overviewByGeo = [];
        // "Всі" — universal phones (geo_mode=all)
        $univPhone = $allPhones->filter(fn($e) => is_null($e->parent_id) && $e->geo_mode === 'all')->firstWhere('role', 'primary');
        $univMsg   = $allMsgs->filter(fn($e) => is_null($e->parent_id) && $e->geo_mode === 'all')->firstWhere('role', 'primary');
        $overviewByGeo['all'] = [
            'label'        => 'Всі',
            'primaryPhone' => $univPhone,
            'primaryMsg'   => $univMsg,
            'backupCount'  => $univPhone ? $allPhones->where('parent_id', $univPhone->id)->count() : 0,
        ];
        // One column per geoTab — tag-based
        foreach ($this->geoTabs as $geoCode) {
            $fp = $this->filterByGeoTag($allPhones, $geoCode)->filter(fn($e) => is_null($e->parent_id));
            $fm = $this->filterByGeoTag($allMsgs,   $geoCode)->filter(fn($e) => is_null($e->parent_id));
            $pp = $fp->firstWhere('role', 'primary');
            $pm = $fm->firstWhere('role', 'primary');
            $overviewByGeo[$geoCode] = [
                'label'        => $geoCode,
                'primaryPhone' => $pp,
                'primaryMsg'   => $pm,
                'backupCount'  => $pp ? $allPhones->where('parent_id', $pp->id)->count() : 0,
            ];
        }

        // Geo visibility matrix — tag-based (geo_mode=only + country tag)
        $primaryPhones = $allPhones->filter(fn($e) => is_null($e->parent_id));
        $geoMatrix = $primaryPhones->map(function ($phone) {
            $vis = [];
            foreach ($this->geoTabs as $gc) {
                $vis[$gc] = $phone->geo_mode === 'only' && in_array($gc, $phone->countries ?? []);
            }
            return ['phone' => $phone, 'vis' => $vis];
        })->values();

        // Conflict detection
        $conflicts = [];
        $conflictPhoneIds = [];
        $seenConflicts = [];
        foreach ($this->geoRules as $rule) {
            [$groupA, $groupB] = $rule['groups'];
            foreach ($primaryPhones as $phone) {
                $seenByA = collect($groupA)->some(fn($c) => $phone->visibleForGeo($c));
                $seenByB = collect($groupB)->some(fn($c) => $phone->visibleForGeo($c));
                if ($seenByA && $seenByB) {
                    $key = $phone->id . '_' . $rule['id'];
                    if (!isset($seenConflicts[$key])) {
                        $conflicts[] = ['rule' => $rule, 'phone' => $phone];
                        $conflictPhoneIds[$phone->id] = true;
                        $seenConflicts[$key] = true;
                    }
                }
            }
        }
        $conflictPhoneIds = array_keys($conflictPhoneIds);

        // Pre-render geo variants for Data tab — tag-based: 'all'=все, 'UA'=тільки geo_mode=only+UA
        $phonePrimariesByGeo = [];
        $msgPrimariesByGeo   = [];
        foreach (array_merge(['all'], $this->geoTabs) as $gk) {
            $fp = $this->filterByGeoTag($allPhones, $gk);
            $phonePrimariesByGeo[$gk] = $fp->filter(fn($e) => is_null($e->parent_id))->values();
            $fm = $this->filterByGeoTag($allMsgs, $gk);
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

        $geoTabs = $this->geoTabs;

        $geoRules = $this->geoRules;
        $newRuleA = $this->newRuleA;
        $newRuleB = $this->newRuleB;

        return view('livewire.sites.show', compact(
            'overviewByGeo', 'geoMatrix', 'conflicts', 'conflictPhoneIds',
            'allPhones', 'allMsgs', 'allPhonesAll', 'allMsgsAll', 'allPricesAll',
            'phonePrimaries', 'msgPrimaries',
            'phonePrimariesByGeo', 'msgPrimariesByGeo',
            'msgByKind',
            'priceBySku', 'priceBySkuAll',
            'activityLogs',
            'phoneCount', 'msgCount', 'priceCount',
            'addressCount', 'socialCount',
            'geoTabs', 'geoRules', 'newRuleA', 'newRuleB',
        ));
    }
}
