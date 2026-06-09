<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithBulkSelection;
use App\Livewire\Concerns\WithReserves;
use App\Livewire\Concerns\WithWizard;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Services\ActivityLogService;
use App\Services\BulkActionService;
use App\Services\SitePluginSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Браузер даних')]
class DataBrowser extends Component
{
    use WithBulkSelection;
    use WithReserves;
    use WithWizard;
    use WithPagination;

    private const REQUIRED_CATEGORIES = ['phones', 'messengers'];
    private const DEFAULT_CATEGORIES = ['phones', 'messengers', 'prices'];
    private const CATEGORY_TYPES = [
        'phones' => 'phone',
        'messengers' => 'messenger',
        'prices' => 'price',
        'addresses' => 'address',
        'socials' => 'social',
    ];

    #[Url]
    public string $search = '';

    /** One entity type at a time — phone | messenger | price | … (no "all" view). */
    #[Url]
    public string $typeFilter = 'phone';

    /** Sub-kind within a type that has kinds (e.g. messenger → telegram|viber|…). */
    #[Url]
    public string $kindFilter = '';

    /** Sub-state within a type — '' (all) | primary | backup | hidden. */
    #[Url]
    public string $roleFilter = '';

    #[Url]
    public string $siteFilter = '';

    /** Occurrence sort — '' = newest first; else value|label|site|role + direction. */
    #[Url]
    public string $sortField = '';
    #[Url]
    public string $sortDir = 'asc';

    /** Group the list by site (Jira-style) so it's clear which numbers sit on which site. */
    #[Url]
    public bool $groupBySite = true;

    /** Trash mode — browse & manage soft-deleted entries (restore / purge). */
    #[Url]
    public bool $trashed = false;

    /**
     * Left-pane axis: 'value' lists distinct values (find a number/price, then act
     * on its occurrences — the headline use case); 'site' lists sites to dive into.
     */
    #[Url]
    public string $axis = 'value';

    /** The value-group picked in the 'value' axis — occurrences narrow to it. */
    #[Url]
    public string $pickedValue = '';

    /** Disambiguator for the price axis (1000 UAH ≠ 1000 EUR). */
    #[Url]
    public string $pickedCurrency = '';

    public function mount(): void
    {
        // Normalise stale/empty URLs (e.g. the old "all types" value) against the registry.
        if (! array_key_exists($this->typeFilter, ContactEntry::TYPES)) {
            $this->typeFilter = array_key_first(ContactEntry::TYPES);
        }
        $this->normalizeTypeFilterForEnabledTypes();
        $this->normalizeRoleFilterForType();
        // Types with kinds always have exactly one selected (no "all kinds"
        // option) — default to the first available kind.
        $kinds = $this->kindLabelsForType($this->typeFilter);
        if (! $kinds) {
            $this->kindFilter = '';
        } elseif (! array_key_exists($this->kindFilter, $kinds)) {
            $this->kindFilter = (string) array_key_first($kinds);
        }

        if (! in_array($this->axis, ['value', 'site'], true)) {
            $this->axis = 'value';
        }
    }

    /** Bulk "edit field" drawer state — two steps: enter (1) → confirm (2). */
    public bool $editingField = false;
    public string $editField = 'value';   // value | label
    public string $editValue = '';
    public int $editStep = 1;

    /** Generic "change ANY field" drawer — pick field + operation (set/clear/replace). */
    /** Jira-style bulk-change wizard — pick an operation, then route to its flow. */
    public bool $bulkWizard = false;

    public bool $editingGeneric = false;
    public string $genField = 'value';
    public string $genOp = 'set';          // set | clear | replace
    public string $genValue = '';
    public string $genFind = '';
    public int $genStep = 1;

    /** Selection review drawer (see / trim the cross-site set). */
    public bool $reviewingSelection = false;

    /** Find & replace (substring) drawer — two steps: enter (1) → confirm (2). */
    public bool $editingReplace = false;
    public string $findText = '';
    public string $replaceText = '';
    public int $replaceStep = 1;

    /** Bulk geo drawer. */
    public bool $editingGeo = false;
    public string $geoMode = 'all';        // all | only | except
    public string $geoCountries = '';      // free text: "UA, PL, DE"

    /** Bulk role/state drawer — primary | hidden (backup needs a parent). */
    public bool $editingRole = false;
    public string $roleValue = 'primary';

    /** Bulk price-fields drawer (only for the price type) — enter (1) → confirm (2). */
    public bool $editingPrice = false;
    public string $priceField = 'currency'; // currency | price_unit | price | old_price
    public string $priceValue = '';
    public int $priceStep = 1;

    /** Duplicate the selection onto other sites (multi-target). */
    public bool $duplicating = false;
    public array $dupSites = [];

    /** Move the selection to another site (single target). */
    public bool $moving = false;
    public string $moveSite = '';

    /** Create a new entry on one or more sites. */
    public bool $creating = false;
    public string $createValue = '';
    public string $createLabel = '';
    public string $createKind = '';
    public string $createRole = 'primary';
    public array $createSites = [];

    /** Attach the selection as reserves of a chosen primary (same site+type+kind). */
    public bool $attaching = false;
    public string $attachParent = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->resetMatchingFlag();
    }

    public function updatingTrashed(): void
    {
        // Switching between active list and trash is a different dataset entirely.
        $this->resetPage();
        $this->clearSelected();
    }

    public function updatingTypeFilter($value): void
    {
        // Type is the entity context — switching it drops the whole selection.
        // Types with kinds always have one selected (no "all kinds"), so default
        // to the first available kind of the new type.
        $kinds = $this->kindLabelsForType((string) $value);
        $this->kindFilter = $kinds ? (string) array_key_first($kinds) : '';
        if ($value === 'price' && $this->roleFilter === 'backup') {
            $this->roleFilter = '';
        }
        // The picked value belongs to the old entity — drop it with the selection.
        $this->pickedValue = '';
        $this->pickedCurrency = '';
        $this->resetPage();
        $this->clearSelected();
    }

    public function updatingKindFilter(): void
    {
        // A kind is its own entity (a Viber link is not a Telegram link), so
        // switching it drops the selection too — you re-pick within the new kind.
        $this->pickedValue = '';
        $this->pickedCurrency = '';
        $this->resetPage();
        $this->clearSelected();
    }

    public function updatingAxis(): void
    {
        // Switching how the left rail is organised resets the working set.
        $this->pickedValue = '';
        $this->pickedCurrency = '';
        $this->resetPage();
        $this->clearSelected();
    }

    public function updatedAxis(): void
    {
        // In the site axis the right pane is a per-site failover view, so it needs a
        // site chosen — auto-pick the first accessible one so the groups show at once
        // (otherwise the toggle just reveals an "оберіть сайт" prompt and feels empty).
        if ($this->axis === 'site' && $this->siteFilter === '') {
            $first = $this->sitesForUser()->first();
            if ($first) {
                $this->siteFilter = (string) $first->id;
            }
        }
    }

    /** Pick a value-group from the left rail; occurrences narrow to it. */
    public function pickValue(string $value, string $currency = ''): void
    {
        $this->pickedValue = $value;
        $this->pickedCurrency = $currency;
        $this->resetPage();
        $this->clearSelected();
    }

    public function clearPick(): void
    {
        $this->pickedValue = '';
        $this->pickedCurrency = '';
        $this->resetPage();
        $this->clearSelected();
    }

    public function updatingRoleFilter(): void
    {
        // Role is a scope filter (same entities, different state) like site —
        // explicit picks survive; only the filter-bound "all matching" resets.
        $this->resetPage();
        $this->resetMatchingFlag();
    }

    public function updatingSiteFilter(): void
    {
        $this->resetPage();
        $this->resetMatchingFlag();
    }

    /**
     * A site/search change only drops the filter-bound "select all matching" flag —
     * explicit picks survive so the user can assemble a cross-site set within a type.
     */
    private function resetMatchingFlag(): void
    {
        $this->selectAllMatching = false;
    }

    /**
     * Constrain reads to entries the current user may view, mirroring
     * ContactEntryPolicy::view(): owner/admin see everything; everyone else is
     * limited to entries they own (site.client.user_id). Shared by every read
     * path so render(), export(), the selection preview/review drawers and
     * "select all matching" cannot leak rows the user is not authorized to see.
     */
    protected function applyVisibility(Builder $query): Builder
    {
        $user = Auth::user();
        $readableTypes = $user?->readableEntryTypes() ?? [];

        if (empty($readableTypes)) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereIn('type', $readableTypes);

        // Only entries on a LIVE site — a deleted (soft-deleted) site keeps its rows
        // in the table, but they must not surface here (restore the site first). This
        // is what kept showing "already-deleted" data for owner/admin.
        $query->whereHas('site');

        if (in_array($user?->role, ['owner', 'admin'], true)) {
            return $query;
        }

        // accessibleTo folds in client ownership AND access_scope (group/site) —
        // so limited users never see, search or bulk-act on out-of-scope sites' data.
        return $query->whereHas('site', fn ($q) => $q->accessibleTo($user));
    }

    protected function sitesVisibleToUserQuery(): Builder
    {
        return Site::query()->accessibleTo(Auth::user());
    }

    private function normalizeDataCategories(?array $categories): array
    {
        $enabled = array_merge(self::REQUIRED_CATEGORIES, $categories ?? self::DEFAULT_CATEGORIES);

        return collect(array_keys(self::CATEGORY_TYPES))
            ->filter(fn (string $key) => in_array($key, $enabled, true))
            ->values()
            ->all();
    }

    private function enabledTypeLabels(): array
    {
        $readableTypes = collect(Auth::user()?->readableEntryTypes() ?? []);

        if ($readableTypes->isEmpty()) {
            return [];
        }

        $enabledTypes = $this->sitesVisibleToUserQuery()
            ->get(['data_categories'])
            ->flatMap(fn (Site $site) => collect($this->normalizeDataCategories($site->data_categories))
                ->map(fn (string $category) => self::CATEGORY_TYPES[$category] ?? null)
                ->filter())
            ->unique()
            ->values();

        return collect(ContactEntry::typeLabels())
            ->filter(fn ($label, $key) => $enabledTypes->contains($key) && $readableTypes->contains($key))
            ->all();
    }

    private function normalizeTypeFilterForEnabledTypes(): void
    {
        $enabled = $this->enabledTypeLabels();

        if (! $enabled) {
            $this->typeFilter = '';
            $this->kindFilter = '';

            return;
        }

        if (! array_key_exists($this->typeFilter, $enabled)) {
            $this->typeFilter = (string) array_key_first($enabled);
            $this->kindFilter = '';
        }
    }

    private function normalizeRoleFilterForType(): void
    {
        if ($this->typeFilter === 'price' && $this->roleFilter === 'backup') {
            $this->roleFilter = '';
        }
    }

    /** Click a column header to sort the occurrences; same field toggles direction. */
    public function sortBy(string $field): void
    {
        if (! in_array($field, ['value', 'label', 'site', 'role'], true)) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    /** Apply the chosen sort to the occurrences query (default = newest first). */
    private function applySort(Builder $query): Builder
    {
        $dir = $this->sortDir === 'desc' ? 'desc' : 'asc';
        $siteName = Site::select('name')->whereColumn('sites.id', 'contact_entries.site_id');

        // Group anchor: keep one site's rows together (chosen sort applies within).
        if ($this->groupBySite) {
            $query->orderBy($siteName, 'asc');
        }

        return match ($this->sortField) {
            'value' => $query->orderBy('value', $dir),
            'label' => $query->orderBy('label', $dir),
            'role'  => $query->orderBy('role', $dir),
            'site'  => $this->groupBySite ? $query : $query->orderBy($siteName, $dir),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    /** Filtered, un-paginated base query — shared by render() and bulk selection. */
    protected function bulkQuery(): Builder
    {
        return $this->applyVisibility(ContactEntry::query())
            ->when($this->typeFilter === '', fn ($q) => $q->whereRaw('1 = 0'))
            ->when($this->trashed, fn ($q) => $q->onlyTrashed())
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->kindFilter, fn ($q) => $q->where('kind', $this->kindFilter))
            ->when($this->roleFilter, fn ($q) => $q->where('role', $this->roleFilter))
            ->when($this->siteFilter, fn ($q) => $q->where('site_id', $this->siteFilter))
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('value', 'like', "%{$this->search}%")
                ->orWhere('label', 'like', "%{$this->search}%")
            ))
            // Value axis: a picked group narrows every read AND bulk action to just
            // that value's occurrences — this is what makes "replace selectively
            // where it occurs" safe (pickedValue='' = whole filter, unchanged).
            // Wizard multi-value: narrow to occurrences across several picked values
            // at once (e.g. delete three different reserve numbers in one pass).
            ->when(! empty($this->wizValues), function ($q) {
                $q->where(function ($w) {
                    foreach ($this->wizValues as $v) {
                        $w->orWhereRaw('TRIM(`value`) = ?', [(string) $v]);
                    }
                });
            })
            ->when(empty($this->wizValues) && $this->axis === 'value' && $this->pickedValue !== '', function ($q) {
                $this->typeFilter === 'price'
                    ? $q->where('price', $this->pickedValue)
                        ->when($this->pickedCurrency !== '', fn ($q2) => $q2->where('currency', $this->pickedCurrency))
                    : $q->whereRaw('TRIM(`value`) = ?', [$this->pickedValue]); // trim: match whitespace variants too
            });
    }

    /** Column the 'value' axis groups by — the price amount, else the raw value. */
    private function valueGroupColumn(): string
    {
        return $this->typeFilter === 'price' ? 'price' : 'value';
    }

    /**
     * Distinct value-groups for the left rail: each row is one value (or price
     * amount + currency) with how many entries carry it and across how many sites.
     * Honours every active filter except the pick itself.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function valueGroups()
    {
        $col = $this->valueGroupColumn();

        $base = $this->applyVisibility(ContactEntry::query())
            ->when($this->typeFilter === '', fn ($q) => $q->whereRaw('1 = 0'))
            ->when($this->trashed, fn ($q) => $q->onlyTrashed())
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->kindFilter, fn ($q) => $q->where('kind', $this->kindFilter))
            ->when($this->roleFilter, fn ($q) => $q->where('role', $this->roleFilter))
            ->when($this->siteFilter, fn ($q) => $q->where('site_id', $this->siteFilter))
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('value', 'like', "%{$this->search}%")
                ->orWhere('label', 'like', "%{$this->search}%")
            ))
            ->whereNotNull($col)
            ->reorder();

        if ($this->typeFilter === 'price') {
            return $base
                ->select('price as gkey', 'currency', DB::raw('COUNT(*) as n'), DB::raw('COUNT(DISTINCT site_id) as sites'),
                    DB::raw('SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) as prim'),
                    DB::raw('SUM(CASE WHEN parent_id IS NOT NULL THEN 1 ELSE 0 END) as res'))
                ->groupBy('price', 'currency')
                ->orderByDesc('n')->orderByDesc('price')
                ->limit(200)->get();
        }

        // TRIM so a stray leading/trailing space doesn't split one number into two
        // rows (e.g. " +380…" vs "+380…"). The pick constraint trims too, so editing
        // the merged group rewrites — and cleans — every whitespace variant.
        return $base
            ->selectRaw('TRIM(`value`) as gkey, COUNT(*) as n, COUNT(DISTINCT site_id) as sites, SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) as prim, SUM(CASE WHEN parent_id IS NOT NULL THEN 1 ELSE 0 END) as res')
            ->groupBy(DB::raw('TRIM(`value`)'))
            ->orderByDesc('n')->orderBy(DB::raw('TRIM(`value`)'))
            ->limit(200)->get();
    }

    /** Is a working set chosen (a value picked, or a site in the site axis)? */
    public function hasWorkingSet(): bool
    {
        return $this->axis === 'value'
            ? $this->pickedValue !== ''
            : $this->siteFilter !== '';
    }

    /**
     * Promote a reserve to a standalone primary — it captures the geo it had been
     * inheriting so its targeting survives the detach. Logged as one semantic feed
     * entry (the owen-it per-field audit is suppressed so the role/parent/geo churn
     * doesn't also show as a plain "entry.updated").
     */
    public function makePrimary(int $id): void
    {
        $entry = ContactEntry::with('parent')->find($id);
        if (! $entry || (is_null($entry->parent_id) && $entry->role !== 'backup')) {
            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->can('update', $entry)) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на цей запис');

            return;
        }

        $parent = $entry->parent;

        ContactEntry::disableAuditing();
        try {
            $entry->update([
                'role'      => 'primary',
                'parent_id' => null,
                'visible'   => true,
                'geo_tag'   => $parent?->geo_tag,
                'geo_mode'  => $parent?->geo_mode ?? 'all',
                'countries' => $parent?->countries,
            ]);
            $entry->forceFill(['failover_down' => false])->save();
        } finally {
            ContactEntry::enableAuditing();
        }

        ActivityLogService::log('entry.made_primary', $entry, [
            'site_id' => $entry->site_id,
            'type'    => $entry->type,
            'value'   => $entry->value,
            'from'    => $parent?->value,
        ]);
        $this->syncSites([(int) $entry->site_id]);
        $this->dispatch('toast', type: 'success', message: 'Переведено в основні');
    }

    /**
     * Move a reserve up/down within its primary's failover queue (site axis). One
     * semantic entry.reordered is logged; owen-it ignores `order`, so this is the
     * only trail. No-op (and no log) when the reserve is already at the edge.
     */
    public function reorderReserve(int $id, string $direction): void
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $entry = ContactEntry::find($id);
        if (! $entry || is_null($entry->parent_id)) {
            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->can('update', $entry)) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на цей запис');

            return;
        }

        $ids = ContactEntry::where('parent_id', $entry->parent_id)
            ->where('site_id', $entry->site_id)
            ->orderBy('order')
            ->pluck('id')->map(fn ($i) => (int) $i)->values()->all();

        $pos = array_search((int) $entry->id, $ids, true);
        $swap = $direction === 'up' ? $pos - 1 : $pos + 1;
        if ($pos === false || $swap < 0 || $swap >= count($ids)) {
            return; // already at the edge
        }

        [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];

        ContactEntry::disableAuditing();
        try {
            foreach ($ids as $order => $eid) {
                ContactEntry::where('id', $eid)
                    ->where('parent_id', $entry->parent_id)
                    ->update(['order' => $order + 1]);
            }
        } finally {
            ContactEntry::enableAuditing();
        }

        ActivityLogService::log('entry.reordered', $entry->parent, [
            'site_id' => $entry->site_id,
            'type'    => $entry->type,
            'scope'   => 'reserves',
            'count'   => count($ids),
        ]);
        $this->syncSites([(int) $entry->site_id]);
    }

    /**
     * Failover groups for the site axis: each primary (parent_id null) of the chosen
     * site + type (+kind) with its reserves in queue order. Read-scoped like the rest.
     *
     * @return \Illuminate\Support\Collection<int, ContactEntry>
     */
    protected function siteFailoverGroups()
    {
        if ($this->axis !== 'site' || $this->siteFilter === '') {
            return collect();
        }

        return $this->applyVisibility(ContactEntry::query())
            ->where('site_id', $this->siteFilter)
            ->where('type', $this->typeFilter)
            ->when($this->kindFilter, fn ($q) => $q->where('kind', $this->kindFilter))
            ->whereNull('parent_id')
            ->with(['backups' => fn ($q) => $q->orderBy('order')])
            ->orderBy('order')->orderBy('value')
            ->limit(200)
            ->get();
    }

    /**
     * Failover groups for the persistent "who is whose reserve" panel under the
     * two-pane: only primaries that actually HAVE reserves, for the current type
     * (+kind), scoped by the site filter when set. Always available (both axes).
     *
     * @return \Illuminate\Support\Collection<int, ContactEntry>
     */
    protected function reserveGroups()
    {
        if ($this->typeFilter === '' || $this->trashed) {
            return collect();
        }

        return $this->applyVisibility(ContactEntry::query())
            ->where('type', $this->typeFilter)
            ->when($this->kindFilter, fn ($q) => $q->where('kind', $this->kindFilter))
            ->when($this->siteFilter, fn ($q) => $q->where('site_id', $this->siteFilter))
            ->whereNull('parent_id')
            ->whereHas('backups')
            ->with(['backups' => fn ($q) => $q->orderBy('order'), 'site:id,name'])
            ->orderBy('site_id')->orderBy('value')
            ->limit(50)
            ->get();
    }

    /**
     * Authorised query for the explicitly-picked ids. Honours trash mode so the
     * review/preview drawers resolve soft-deleted rows when browsing the trash.
     */
    protected function selectedQuery(): Builder
    {
        $q = ContactEntry::query()
            ->when($this->trashed, fn ($b) => $b->withTrashed())
            ->whereIn('id', $this->selected);

        return $this->applyVisibility($q);
    }

    /** Authorised source rows for a bulk action (whole filter when "all matching"). */
    protected function selectedSourceQuery(): Builder
    {
        return $this->selectAllMatching ? $this->bulkQuery() : $this->selectedQuery();
    }

    /** Sites the current user may target — owner/admin all, others their own. */
    protected function sitesForUser()
    {
        return $this->sitesVisibleToUserQuery()->orderBy('name')->get(['id', 'name']);
    }

    /** @return array<int> ids of sites the user may create on / move to */
    protected function allowedSiteIds(): array
    {
        return $this->sitesForUser()->pluck('id')->map(fn ($i) => (int) $i)->all();
    }

    /**
     * Distinct entity signatures (type + kind) in the current selection. A value
     * is type+kind specific — a Viber link is not a Telegram link — so the edit
     * guard works at this granularity, not just by type.
     *
     * @return array<string> e.g. ['phone|', 'messenger|telegram']
     */
    protected function selectionSignatures(): array
    {
        if (! $this->hasSelection()) {
            return [];
        }

        $q = $this->selectAllMatching
            ? $this->bulkQuery()
            : $this->selectedQuery();

        return $q->reorder()->distinct()->get(['type', 'kind'])
            ->map(fn ($r) => $r->type.'|'.($r->kind ?? ''))
            ->unique()->values()->all();
    }

    /** Human labels for the selection's distinct entities (for the guard hint). */
    protected function selectionEntityLabels(): array
    {
        return collect($this->selectionSignatures())->map(function (string $sig) {
            [$type, $kind] = array_pad(explode('|', $sig, 2), 2, '');

            return $kind !== ''
                ? ($this->kindLabel($type, $kind) ?? $kind)
                : (ContactEntry::TYPES[$type]['label'] ?? $type);
        })->all();
    }

    private function kindLabel(string $type, string $kind): ?string
    {
        return $this->kindLabelsForType($type)[$kind] ?? null;
    }

    /**
     * Kinds that actually occur in the (authorised) data for a type — not the whole
     * static registry. The canonical labels/order come from ContactEntry::kindLabels,
     * but a kind only appears once at least one entry uses it, so the sub-filter never
     * shows platforms that aren't present on any site (custom kinds surface too).
     *
     * @return array<string,string>
     */
    private function kindLabelsForType(string $type): array
    {
        if (! ContactEntry::hasKinds($type)) {
            return [];
        }

        $present = $this->applyVisibility(ContactEntry::query())
            ->when($this->trashed, fn ($q) => $q->onlyTrashed())
            ->where('type', $type)
            ->whereNotNull('kind')
            ->distinct()
            ->pluck('kind')
            ->map(fn ($kind) => trim((string) $kind))
            ->filter()
            ->unique();

        $static = ContactEntry::kindLabels($type);
        $labels = [];

        // Canonical kinds first (in registry order), then any custom kinds in use.
        foreach ($static as $key => $label) {
            if ($present->contains($key)) {
                $labels[$key] = $label;
            }
        }
        foreach ($present as $kind) {
            $labels[$kind] ??= $this->humanKindLabel($kind);
        }

        return $labels;
    }

    private function humanKindLabel(string $kind): string
    {
        return str($kind)
            ->replace(['-', '_'], ' ')
            ->squish()
            ->title()
            ->toString();
    }

    private function selectionIsSingleEntity(): bool
    {
        return count($this->selectionSignatures()) <= 1;
    }

    // ─── Bulk: edit a field (replace value / change label) ────────────────

    public function openEdit(string $field): void
    {
        if (! in_array($field, ['value', 'label'], true) || ! $this->hasSelection()) {
            return;
        }

        // 'value' is type+kind specific; 'label' is common to all entities.
        if ($field === 'value' && ! $this->selectionIsSingleEntity()) {
            $this->dispatch('toast', type: 'error', message: 'Заміна значення — лише для записів одного виду');

            return;
        }

        $this->editField = $field;
        $this->editValue = '';
        $this->editStep = 1;
        $this->editingField = true;
    }

    public function closeEdit(): void
    {
        $this->editingField = false;
        $this->editField = 'value';
        $this->editValue = '';
        $this->editStep = 1;
    }

    /** Step 1 → 2: validate input is present, then show the confirmation list. */
    public function editConfirm(): void
    {
        if ($this->editField === 'value' && trim($this->editValue) === '') {
            $this->dispatch('toast', type: 'error', message: 'Введіть нове значення');

            return;
        }
        $this->editStep = 2;
    }

    public function editBack(): void
    {
        $this->editStep = 1;
    }

    /**
     * Inline single-cell edit (spreadsheet / Airtable style) — change one row's
     * value or label in place, no drawer. Authorised + audited like any edit.
     */
    public function inlineUpdate(int $id, string $field, string $value): void
    {
        if (! in_array($field, ['value', 'label', 'price', 'old_price'], true)) {
            return;
        }

        $entry = $this->applyVisibility(ContactEntry::query())->find($id);
        if (! $entry) {
            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->can('update', $entry)) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на цей запис');

            return;
        }

        $new = trim($value);
        $isNumeric = in_array($field, ['price', 'old_price'], true);

        if ($field === 'value' && $new === '') {
            $this->dispatch('toast', type: 'error', message: 'Значення не може бути порожнім');

            return;
        }
        if ($isNumeric && $new !== '' && ! is_numeric(str_replace(',', '.', $new))) {
            $this->dispatch('toast', type: 'error', message: 'Вкажіть число');

            return;
        }

        $stored = match (true) {
            $isNumeric          => ($new === '' ? null : (float) str_replace(',', '.', $new)),
            $field === 'label'  => ($new === '' ? null : $new),
            default             => $new,
        };
        if ((string) $entry->{$field} === (string) $stored) {
            return; // nothing changed — stay quiet
        }

        $old = $entry->{$field};
        $entry->update([$field => $stored]);
        $this->syncSites([(int) $entry->site_id]);

        $noun = match ($field) {
            'value' => 'Значення', 'label' => 'Мітку', 'price' => 'Ціну', 'old_price' => 'Стару ціну',
        };
        // Reuse the bulk per-row restore so a single inline edit is also undoable.
        $this->dispatch('toast',
            type: 'success',
            message: "{$noun} оновлено",
            action: 'bulkRestoreField',
            actionLabel: 'Відмінити',
            actionData: ['snapshot' => [$entry->id => $old], 'field' => $field],
        );
    }

    // ─── Generic "change ANY field" (set / clear / find-replace) ──────────

    /** Editable fields for the current type → label. */
    public function genericFields(): array
    {
        return $this->typeFilter === 'price'
            ? ['sku' => 'SKU', 'label' => 'Мітка', 'price' => 'Ціна', 'old_price' => 'Стара ціна', 'currency' => 'Валюта', 'price_unit' => 'Одиниця']
            : ['value' => 'Значення', 'label' => 'Мітка'];
    }

    /** Operations valid for a field → label. */
    public function genericOps(string $field): array
    {
        return match ($field) {
            'value'              => ['set' => 'Встановити', 'replace' => 'Знайти→замінити'],
            'currency'           => ['set' => 'Встановити'],
            'price', 'old_price' => ['set' => 'Встановити', 'clear' => 'Очистити'],
            default              => ['set' => 'Встановити', 'clear' => 'Очистити', 'replace' => 'Знайти→замінити'],
        };
    }

    // ─── Jira-style bulk-change wizard ────────────────────────────────────

    public function openBulkWizard(): void
    {
        if ($this->hasSelection()) {
            $this->bulkWizard = true;
        }
    }

    public function closeBulkWizard(): void
    {
        $this->bulkWizard = false;
    }

    /** Pick an operation in the wizard → route to its existing (audited, undoable) flow. */
    public function wizardTo(string $op): void
    {
        $this->bulkWizard = false;
        match ($op) {
            'field'     => $this->openGeneric(),
            'geo'       => $this->openGeo(),
            'state'     => $this->openRole(),
            'move'      => $this->openMove(),
            'duplicate' => $this->openDuplicate(),
            'attach'    => $this->openAttach(),
            'delete'    => $this->bulkDelete(),
            default     => null,
        };
    }

    public function openGeneric(): void
    {
        if (! $this->hasSelection()) {
            return;
        }
        // value/price/sku are type+kind specific — refuse a mixed selection.
        if (! $this->selectionIsSingleEntity()) {
            $this->dispatch('toast', type: 'error', message: 'Зміна поля — лише для записів одного виду');

            return;
        }

        $this->genField = (string) array_key_first($this->genericFields());
        $this->genOp = (string) array_key_first($this->genericOps($this->genField));
        $this->genValue = '';
        $this->genFind = '';
        $this->genStep = 1;
        $this->editingGeneric = true;
    }

    public function closeGeneric(): void
    {
        $this->editingGeneric = false;
        $this->genValue = '';
        $this->genFind = '';
        $this->genStep = 1;
    }

    /** Field changed → snap the operation to one valid for it. */
    public function updatedGenField(): void
    {
        if (! array_key_exists($this->genOp, $this->genericOps($this->genField))) {
            $this->genOp = (string) array_key_first($this->genericOps($this->genField));
        }
    }

    public function genericConfirm(): void
    {
        if (($err = $this->validateGeneric()) !== null) {
            $this->dispatch('toast', type: 'error', message: $err);

            return;
        }
        $this->genStep = 2;
    }

    public function genericBack(): void
    {
        $this->genStep = 1;
    }

    /** @return string|null error message, or null if valid */
    private function validateGeneric(): ?string
    {
        $field = $this->genField;
        if (! array_key_exists($field, $this->genericFields())) {
            return 'Невідоме поле';
        }
        if (! array_key_exists($this->genOp, $this->genericOps($field))) {
            return 'Невідома операція';
        }
        if ($this->genOp === 'replace') {
            return $this->genFind === '' ? 'Введіть текст для пошуку' : null;
        }
        if ($this->genOp === 'clear') {
            return null;
        }
        // set:
        $raw = trim($this->genValue);
        if ($field === 'currency') {
            return in_array(strtoupper($raw), ['EUR', 'USD', 'PLN', 'UAH'], true) ? null : 'Оберіть валюту';
        }
        if (in_array($field, ['price', 'old_price'], true)) {
            return ($raw === '' || is_numeric(str_replace(',', '.', $raw))) ? null : 'Вкажіть число';
        }
        if ($field === 'value' && $raw === '') {
            return 'Значення не може бути порожнім';
        }

        return null;
    }

    public function applyGeneric(): void
    {
        if (($err = $this->validateGeneric()) !== null) {
            $this->dispatch('toast', type: 'error', message: $err);

            return;
        }

        $field = $this->genField;
        $op = $this->genOp;

        // Resolve the stored value for set/clear (replace transforms per row).
        $value = null;
        if ($op === 'set') {
            $raw = trim($this->genValue);
            if ($field === 'currency') {
                $value = strtoupper($raw);
            } elseif (in_array($field, ['price', 'old_price'], true)) {
                $value = $raw === '' ? null : (float) str_replace(',', '.', $raw);
            } else {
                $value = $raw === '' ? null : $raw;
            }
        }

        $snapshot = [];
        $touchedSiteIds = [];
        $find = $this->genFind;
        $replaceWith = $this->genValue;

        BulkActionService::apply(
            ContactEntry::class,
            $this->bulkTargetIds(),
            'update',
            function (ContactEntry $e) use (&$snapshot, &$touchedSiteIds, $field, $op, $value, $find, $replaceWith) {
                if ($op === 'replace') {
                    $cur = (string) $e->{$field};
                    if (! str_contains($cur, $find)) {
                        return; // non-matching rows untouched
                    }
                    $snapshot[$e->id] = $e->{$field};
                    $touchedSiteIds[] = (int) $e->site_id;
                    $e->update([$field => str_replace($find, $replaceWith, $cur)]);

                    return;
                }
                $snapshot[$e->id] = $e->{$field};
                $touchedSiteIds[] = (int) $e->site_id;
                $e->update([$field => $value]);
            },
            auditAction: 'entry.bulk.updated',
        );

        $done = count($snapshot); // replace skips non-matching → snapshot is the truth
        $this->closeGeneric();
        $this->clearSelected();

        if ($done === 0) {
            $this->dispatch('toast', type: 'error', message: $op === 'replace' ? 'Жоден запис не містить шуканого' : 'Немає прав на редагування обраних');

            return;
        }

        $this->syncSites($touchedSiteIds);

        $label = $this->genericFields()[$field] ?? $field;
        $this->dispatch('toast',
            type: 'success',
            message: "«{$label}» змінено: {$done}",
            action: 'bulkRestoreField',
            actionLabel: 'Відмінити',
            actionData: ['snapshot' => $snapshot, 'field' => $field],
        );
    }

    public function applyEdit(): void
    {
        $field = $this->editField;
        $new = trim($this->editValue);

        if (! in_array($field, ['value', 'label'], true)) {
            return;
        }
        if ($field === 'value' && $new === '') {
            $this->dispatch('toast', type: 'error', message: 'Введіть нове значення');

            return;
        }

        $snapshot = [];
        $result = BulkActionService::apply(
            ContactEntry::class,
            $this->bulkTargetIds(),
            'update',
            function (ContactEntry $e) use (&$snapshot, $field, $new) {
                $snapshot[$e->id] = $e->{$field};
                $e->update([$field => $new]);
            },
            auditAction: 'entry.bulk.updated',
            syncTouchedSites: true,
        );

        $this->closeEdit();
        $this->clearSelected();

        if ($result['done'] === 0) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на редагування обраних записів');

            return;
        }

        $noun = $field === 'value' ? 'Значення' : 'Мітку';
        $this->dispatch('toast',
            type: 'success',
            message: $this->withSkipped("{$noun} змінено: {$result['done']}", $result['skipped']),
            action: 'bulkRestoreField',
            actionLabel: 'Відмінити',
            actionData: ['snapshot' => $snapshot, 'field' => $field],
        );
    }

    /** Undo target for a bulk field edit — writes each row's previous value back. */
    #[On('bulkRestoreField')]
    public function bulkRestoreField(array $snapshot, string $field): void
    {
        if (! in_array($field, ['value', 'label', 'currency', 'price', 'old_price', 'price_unit', 'sku'], true) || empty($snapshot)) {
            return;
        }

        $user = Auth::user();
        $done = 0;

        ContactEntry::withTrashed()->whereIn('id', array_keys($snapshot))->get()
            ->each(function (ContactEntry $e) use ($snapshot, $field, $user, &$done) {
                if ($user && $user->can('update', $e)) {
                    $e->update([$field => $snapshot[$e->id] ?? $e->{$field}]);
                    $done++;
                }
            });

        $this->dispatch('toast', type: 'success', message: "Відновлено: {$done}");
    }

    // ─── Bulk: role / state (primary or hidden) ──────────────────────────

    public function openRole(): void
    {
        if (! $this->hasSelection()) {
            return;
        }
        $this->roleValue = 'primary';
        $this->editingRole = true;
    }

    public function closeRole(): void
    {
        $this->editingRole = false;
        $this->roleValue = 'primary';
    }

    public function applyRole(): void
    {
        // 'backup' needs a parent — not a bulk operation; only primary/hidden here.
        if (! in_array($this->roleValue, ['primary', 'hidden'], true)) {
            $this->dispatch('toast', type: 'error', message: 'Оберіть стан: Активний або Прихований');

            return;
        }
        $new = $this->roleValue;

        $snapshot = [];
        $result = BulkActionService::apply(
            ContactEntry::class,
            $this->bulkTargetIds(),
            'update',
            function (ContactEntry $e) use (&$snapshot, $new) {
                $snapshot[$e->id] = [
                    'role'      => $e->role,
                    'visible'   => $e->visible,
                    'parent_id' => $e->parent_id,
                    'geo_tag'   => $e->geo_tag,
                    'geo_mode'  => $e->geo_mode,
                    'countries' => $e->countries,
                ];
                $data = ['role' => $new, 'visible' => $new !== 'hidden'];
                // Promoting a reserve to active detaches it from its primary and
                // captures the geo it had been inheriting, so targeting survives.
                if ($new === 'primary' && $e->parent_id) {
                    $parent = $e->parent;
                    $data['parent_id'] = null;
                    $data['geo_tag']   = $parent?->geo_tag;
                    $data['geo_mode']  = $parent?->geo_mode ?? 'all';
                    $data['countries'] = $parent?->countries;
                }
                $e->update($data);
            },
            auditAction: 'entry.bulk.role',
            syncTouchedSites: true,
        );

        $this->closeRole();
        $this->clearSelected();

        if ($result['done'] === 0) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на редагування обраних записів');

            return;
        }

        $verb = $new === 'primary' ? 'Активовано' : 'Приховано';
        $this->dispatch('toast',
            type: 'success',
            message: $this->withSkipped("{$verb}: {$result['done']}", $result['skipped']),
            action: 'bulkRestoreRole',
            actionLabel: 'Відмінити',
            actionData: ['snapshot' => $snapshot],
        );
    }

    /** Undo target for a bulk role change — restores role + visibility + link + geo. */
    #[On('bulkRestoreRole')]
    public function bulkRestoreRole(array $snapshot): void
    {
        if (empty($snapshot)) {
            return;
        }

        $user = Auth::user();
        $done = 0;

        ContactEntry::withTrashed()->whereIn('id', array_keys($snapshot))->get()
            ->each(function (ContactEntry $e) use ($snapshot, $user, &$done) {
                if ($user && $user->can('update', $e) && ($prev = $snapshot[$e->id] ?? null)) {
                    $e->update($prev);
                    $done++;
                }
            });

        $this->dispatch('toast', type: 'success', message: "Відновлено: {$done}");
    }

    // ─── Bulk: price fields (currency / unit / price / old price) ─────────

    public function openPriceEdit(): void
    {
        if (! $this->hasSelection() || $this->typeFilter !== 'price') {
            return;
        }
        $this->priceField = 'currency';
        $this->priceValue = '';
        $this->priceStep = 1;
        $this->editingPrice = true;
    }

    public function closePriceEdit(): void
    {
        $this->editingPrice = false;
        $this->priceField = 'currency';
        $this->priceValue = '';
        $this->priceStep = 1;
    }

    /** Step 1 → 2: validate the chosen field's value, then show the confirmation. */
    public function priceConfirm(): void
    {
        $field = $this->priceField;
        $raw = trim($this->priceValue);

        if ($field === 'currency' && ! in_array(strtoupper($raw), ['EUR', 'USD', 'PLN', 'UAH'], true)) {
            $this->dispatch('toast', type: 'error', message: 'Оберіть валюту');

            return;
        }
        if (in_array($field, ['price', 'old_price'], true) && $raw !== '' && ! is_numeric(str_replace(',', '.', $raw))) {
            $this->dispatch('toast', type: 'error', message: 'Вкажіть число');

            return;
        }
        $this->priceStep = 2;
    }

    public function priceBack(): void
    {
        $this->priceStep = 1;
    }

    public function applyPriceEdit(): void
    {
        $field = $this->priceField;
        if (! in_array($field, ['currency', 'price_unit', 'price', 'old_price'], true)) {
            return;
        }

        $raw = trim($this->priceValue);
        $isNumeric = in_array($field, ['price', 'old_price'], true);

        if ($field === 'currency') {
            $raw = strtoupper($raw);
            if (! in_array($raw, ['EUR', 'USD', 'PLN', 'UAH'], true)) {
                $this->dispatch('toast', type: 'error', message: 'Оберіть валюту');

                return;
            }
        }
        if ($isNumeric && $raw !== '' && ! is_numeric(str_replace(',', '.', $raw))) {
            $this->dispatch('toast', type: 'error', message: 'Вкажіть число');

            return;
        }

        // Numeric fields accept empty = clear (e.g. remove the old price); currency/unit don't.
        $value = $isNumeric
            ? ($raw === '' ? null : (float) str_replace(',', '.', $raw))
            : ($raw === '' ? null : $raw);

        $snapshot = [];
        $result = BulkActionService::apply(
            ContactEntry::class,
            $this->bulkTargetIds(),
            'update',
            function (ContactEntry $e) use (&$snapshot, $field, $value) {
                $snapshot[$e->id] = $e->{$field};
                $e->update([$field => $value]);
            },
            auditAction: 'entry.bulk.price',
            syncTouchedSites: true,
        );

        $this->closePriceEdit();
        $this->clearSelected();

        if ($result['done'] === 0) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на редагування обраних записів');

            return;
        }

        $noun = match ($field) {
            'currency'   => 'Валюту',
            'price_unit' => 'Одиницю',
            'price'      => 'Ціну',
            'old_price'  => 'Стару ціну',
        };
        $this->dispatch('toast',
            type: 'success',
            message: $this->withSkipped("{$noun} змінено: {$result['done']}", $result['skipped']),
            action: 'bulkRestoreField',
            actionLabel: 'Відмінити',
            actionData: ['snapshot' => $snapshot, 'field' => $field],
        );
    }

    // ─── Selection review (see / trim the cross-site set) ─────────────────

    public function openReview(): void
    {
        if ($this->hasSelection()) {
            $this->reviewingSelection = true;
        }
    }

    public function closeReview(): void
    {
        $this->reviewingSelection = false;
    }

    // ─── Bulk: find & replace a substring inside value ────────────────────

    public function openReplace(): void
    {
        if (! $this->hasSelection()) {
            return;
        }

        if (! $this->selectionIsSingleEntity()) {
            $this->dispatch('toast', type: 'error', message: 'Заміна підрядка — лише для записів одного виду');

            return;
        }

        $this->findText = '';
        $this->replaceText = '';
        $this->replaceStep = 1;
        $this->editingReplace = true;
    }

    public function closeReplace(): void
    {
        $this->editingReplace = false;
        $this->findText = '';
        $this->replaceText = '';
        $this->replaceStep = 1;
    }

    /** Step 1 → 2: need something to search for before showing the confirmation. */
    public function replaceConfirm(): void
    {
        if ($this->findText === '') {
            $this->dispatch('toast', type: 'error', message: 'Введіть текст для пошуку');

            return;
        }
        $this->replaceStep = 2;
    }

    public function replaceBack(): void
    {
        $this->replaceStep = 1;
    }

    public function applyReplace(): void
    {
        $find = $this->findText;
        if ($find === '') {
            $this->dispatch('toast', type: 'error', message: 'Введіть текст для пошуку');

            return;
        }

        $replace = $this->replaceText;
        $snapshot = [];
        $changed = 0;

        BulkActionService::apply(
            ContactEntry::class,
            $this->bulkTargetIds(),
            'update',
            function (ContactEntry $e) use (&$snapshot, &$changed, $find, $replace) {
                $current = (string) $e->value;
                if (! str_contains($current, $find)) {
                    return; // leave non-matching rows untouched
                }
                $snapshot[$e->id] = $e->value;
                $e->update(['value' => str_replace($find, $replace, $current)]);
                $changed++;
            },
            auditAction: 'entry.bulk.updated',
            syncTouchedSites: true,
        );

        if ($changed === 0) {
            // Keep the drawer open so the user can adjust the search text.
            $this->dispatch('toast', type: 'error', message: "Жоден запис не містить «{$find}»");

            return;
        }

        $this->closeReplace();
        $this->clearSelected();

        $this->dispatch('toast',
            type: 'success',
            message: "Замінено в {$changed}",
            action: 'bulkRestoreField',
            actionLabel: 'Відмінити',
            actionData: ['snapshot' => $snapshot, 'field' => 'value'],
        );
    }

    // ─── Bulk: geo (geo_mode + countries) ─────────────────────────────────

    public function openGeo(): void
    {
        if (! $this->hasSelection()) {
            return;
        }

        $this->geoMode = 'all';
        $this->geoCountries = '';
        $this->editingGeo = true;
    }

    public function closeGeo(): void
    {
        $this->editingGeo = false;
        $this->geoMode = 'all';
        $this->geoCountries = '';
    }

    /** Parse the free-text country box into clean, unique ISO-2/3 codes. */
    private function parsedCountries(): array
    {
        $raw = preg_split('/[\s,]+/', strtoupper(trim($this->geoCountries)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter($raw, fn ($c) => preg_match('/^[A-Z]{2,3}$/', $c))));
    }

    public function applyGeo(): void
    {
        $mode = $this->geoMode;
        if (! in_array($mode, ['all', 'only', 'except'], true)) {
            return;
        }

        $countries = $mode === 'all' ? [] : $this->parsedCountries();
        if ($mode !== 'all' && empty($countries)) {
            $this->dispatch('toast', type: 'error', message: 'Вкажіть хоча б одну країну (напр. UA, PL)');

            return;
        }

        // geo_tag keeps the single-country "only" shortcut used for tab membership.
        $geoTag = ($mode === 'only' && count($countries) === 1) ? $countries[0] : null;

        $snapshot = [];
        $result = BulkActionService::apply(
            ContactEntry::class,
            $this->bulkTargetIds(),
            'update',
            function (ContactEntry $e) use (&$snapshot, $mode, $countries, $geoTag) {
                $snapshot[$e->id] = [
                    'geo_mode' => $e->geo_mode,
                    'countries' => $e->countries,
                    'geo_tag' => $e->geo_tag,
                ];
                $e->update([
                    'geo_mode' => $mode,
                    'countries' => $countries ?: null,
                    'geo_tag' => $geoTag,
                ]);
            },
            auditAction: 'entry.bulk.geo',
            syncTouchedSites: true,
        );

        $this->closeGeo();
        $this->clearSelected();

        if ($result['done'] === 0) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на редагування обраних записів');

            return;
        }

        $this->dispatch('toast',
            type: 'success',
            message: $this->withSkipped("Гео змінено: {$result['done']}", $result['skipped']),
            action: 'bulkRestoreGeo',
            actionLabel: 'Відмінити',
            actionData: ['snapshot' => $snapshot],
        );
    }

    /** Undo target for a bulk geo change — restores mode + countries + tag per row. */
    #[On('bulkRestoreGeo')]
    public function bulkRestoreGeo(array $snapshot): void
    {
        if (empty($snapshot)) {
            return;
        }

        $user = Auth::user();
        $done = 0;

        ContactEntry::withTrashed()->whereIn('id', array_keys($snapshot))->get()
            ->each(function (ContactEntry $e) use ($snapshot, $user, &$done) {
                if ($user && $user->can('update', $e)) {
                    $prev = $snapshot[$e->id] ?? null;
                    if ($prev) {
                        $e->update([
                            'geo_mode' => $prev['geo_mode'] ?? 'all',
                            'countries' => $prev['countries'] ?? null,
                            'geo_tag' => $prev['geo_tag'] ?? null,
                        ]);
                        $done++;
                    }
                }
            });

        $this->dispatch('toast', type: 'success', message: "Відновлено: {$done}");
    }

    // ─── Bulk: delete (soft) + undo ───────────────────────────────────────

    public function bulkDelete(): void
    {
        $deleted = [];
        $result = BulkActionService::apply(
            ContactEntry::class,
            $this->bulkTargetIds(),
            'delete',
            function (ContactEntry $e) use (&$deleted) {
                $e->delete();
                $deleted[] = $e->id;
            },
            auditAction: 'entry.bulk.deleted',
            syncTouchedSites: true,
        );

        $this->clearSelected();

        if ($result['done'] === 0) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на видалення обраних записів');

            return;
        }

        $this->dispatch('toast',
            type: 'success',
            message: $this->withSkipped("Видалено {$result['done']}", $result['skipped']),
            action: 'bulkRestore',
            actionLabel: 'Відмінити',
            actionData: ['ids' => $deleted],
        );
    }

    #[On('bulkRestore')]
    public function bulkRestore(array $ids): void
    {
        // 'delete' ability gates restore too: same privilege, row is only soft-deleted.
        $result = BulkActionService::apply(
            ContactEntry::class,
            $ids,
            'delete',
            fn (ContactEntry $e) => $e->restore(),
            withTrashed: true,
            auditAction: 'entry.bulk.restored',
            syncTouchedSites: true,
        );

        $this->dispatch('toast', type: 'success', message: "Відновлено {$result['done']}");
    }

    // ─── Trash mode: restore / purge selected ─────────────────────────────

    public function restoreSelected(): void
    {
        $result = BulkActionService::apply(
            ContactEntry::class,
            $this->bulkTargetIds(),
            'delete',
            fn (ContactEntry $e) => $e->restore(),
            withTrashed: true,
            auditAction: 'entry.bulk.restored',
            syncTouchedSites: true,
        );

        $this->clearSelected();
        $this->dispatch('toast', type: 'success', message: $this->withSkipped("Відновлено {$result['done']}", $result['skipped']));
    }

    public function purgeSelected(): void
    {
        // Permanent — no undo. The UI guards this with a confirmation.
        $result = BulkActionService::apply(
            ContactEntry::class,
            $this->bulkTargetIds(),
            'delete',
            fn (ContactEntry $e) => $e->forceDelete(),
            withTrashed: true,
            auditAction: 'entry.bulk.purged',
            syncTouchedSites: true,
        );

        $this->clearSelected();
        $this->dispatch('toast', type: 'success', message: $this->withSkipped("Видалено назавжди: {$result['done']}", $result['skipped']));
    }

    // ─── Bulk: duplicate selection onto other sites ───────────────────────

    public function openDuplicate(): void
    {
        if (! $this->hasSelection()) {
            return;
        }
        $this->dupSites = [];
        $this->duplicating = true;
    }

    public function closeDuplicate(): void
    {
        $this->duplicating = false;
        $this->dupSites = [];
    }

    public function toggleDupSite(int $siteId): void
    {
        $this->dupSites = in_array($siteId, $this->dupSites, true)
            ? array_values(array_diff($this->dupSites, [$siteId]))
            : array_merge($this->dupSites, [$siteId]);
    }

    public function applyDuplicate(): void
    {
        $targets = array_values(array_intersect(array_map('intval', $this->dupSites), $this->allowedSiteIds()));
        if (empty($targets)) {
            $this->dispatch('toast', type: 'error', message: 'Оберіть хоча б один сайт');

            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->can('create', ContactEntry::class)) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на створення');

            return;
        }

        $created = [];
        ContactEntry::disableAuditing();
        try {
            $this->selectedSourceQuery()->chunkById(500, function ($rows) use ($targets, $user, &$created) {
                foreach ($rows as $src) {
                    // The selection may span several types — check each row's type permission.
                    if (! $user->canEntryType($src->type, 'create')) {
                        continue;
                    }
                    foreach ($targets as $sid) {
                        $copy = $src->replicate();
                        $copy->site_id = $sid;
                        $copy->parent_id = null;       // a copy stands alone on the target site
                        if ($copy->role === 'backup') {
                            $copy->role = 'primary';   // a reserve becomes primary when copied
                        }
                        $copy->save();
                        $created[] = $copy->id;
                    }
                }
            });
        } finally {
            ContactEntry::enableAuditing();
        }

        if ($created) {
            ActivityLogService::log('entry.bulk.created', null, ['done' => count($created), 'targets' => count($targets)], context: 'bulk');
            $this->syncSites($targets);
        }

        $this->closeDuplicate();
        $this->clearSelected();

        if (empty($created)) {
            $this->dispatch('toast', type: 'error', message: 'Нічого не скопійовано');

            return;
        }

        $this->dispatch('toast',
            type: 'success',
            message: 'Скопійовано: '.count($created),
            action: 'bulkPurgeCreated',
            actionLabel: 'Відмінити',
            actionData: ['ids' => $created],
        );
    }

    // ─── Bulk: move selection to another site ─────────────────────────────

    public function openMove(): void
    {
        if (! $this->hasSelection()) {
            return;
        }
        $this->moveSite = '';
        $this->moving = true;
    }

    public function closeMove(): void
    {
        $this->moving = false;
        $this->moveSite = '';
    }

    public function applyMove(): void
    {
        $target = (int) $this->moveSite;
        if (! in_array($target, $this->allowedSiteIds(), true)) {
            $this->dispatch('toast', type: 'error', message: 'Оберіть сайт призначення');

            return;
        }

        $user = Auth::user();
        $snapshot = [];   // id => previous site_id
        $done = 0;
        $touchedSiteIds = [$target];

        ContactEntry::disableAuditing();
        try {
            $this->selectedSourceQuery()->with('backups')->chunkById(500, function ($rows) use ($target, $user, &$snapshot, &$done, &$touchedSiteIds) {
                foreach ($rows as $e) {
                    if (! $user || ! $user->can('update', $e) || $e->site_id === $target) {
                        continue;
                    }
                    $snapshot[$e->id] = $e->site_id;
                    $touchedSiteIds[] = (int) $e->site_id;
                    $e->update(['site_id' => $target]);
                    // A primary takes its reserves along so the group stays on one site.
                    if (is_null($e->parent_id)) {
                        foreach ($e->backups as $b) {
                            $snapshot[$b->id] = $b->site_id;
                            $touchedSiteIds[] = (int) $b->site_id;
                            $b->update(['site_id' => $target]);
                        }
                    }
                    $done++;
                }
            });
        } finally {
            ContactEntry::enableAuditing();
        }

        if ($done > 0) {
            ActivityLogService::log('entry.bulk.moved', null, ['done' => $done, 'site_id' => $target], context: 'bulk');
            $this->syncSites($touchedSiteIds);
        }

        $this->closeMove();
        $this->clearSelected();

        if ($done === 0) {
            $this->dispatch('toast', type: 'error', message: 'Нічого не переміщено');

            return;
        }

        $this->dispatch('toast',
            type: 'success',
            message: "Переміщено: {$done}",
            action: 'bulkRestoreMove',
            actionLabel: 'Відмінити',
            actionData: ['snapshot' => $snapshot],
        );
    }

    // ─── Bulk: create a new entry on one or more sites ────────────────────

    public function openCreate(): void
    {
        $user = Auth::user();
        if ($this->typeFilter === '' || ! $user || ! $user->can('create', ContactEntry::class) || ! $user->canEntryType($this->typeFilter, 'create')) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на створення');

            return;
        }

        $this->createValue = '';
        $this->createLabel = '';
        $this->createKind = $this->kindFilter ?: (string) (array_key_first($this->kindLabelsForType($this->typeFilter)) ?? '');
        $this->createRole = 'primary';
        $this->createSites = $this->siteFilter !== '' ? [(int) $this->siteFilter] : [];
        $this->creating = true;
    }

    public function closeCreate(): void
    {
        $this->creating = false;
    }

    public function toggleCreateSite(int $siteId): void
    {
        $this->createSites = in_array($siteId, $this->createSites, true)
            ? array_values(array_diff($this->createSites, [$siteId]))
            : array_merge($this->createSites, [$siteId]);
    }

    public function applyCreate(): void
    {
        $type = $this->typeFilter;
        $value = trim($this->createValue);
        if ($value === '') {
            $this->dispatch('toast', type: 'error', message: 'Введіть значення');

            return;
        }

        $needsKind = ContactEntry::hasKinds($type);
        $kind = $needsKind ? $this->createKind : null;
        if ($needsKind && ! array_key_exists((string) $kind, ContactEntry::kindLabels($type))) {
            $this->dispatch('toast', type: 'error', message: 'Оберіть вид');

            return;
        }

        $targets = array_values(array_intersect(array_map('intval', $this->createSites), $this->allowedSiteIds()));
        if (empty($targets)) {
            $this->dispatch('toast', type: 'error', message: 'Оберіть хоча б один сайт');

            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->can('create', ContactEntry::class)) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на створення');

            return;
        }

        $role = in_array($this->createRole, ['primary', 'hidden'], true) ? $this->createRole : 'primary';
        $created = [];
        ContactEntry::disableAuditing();
        try {
            foreach ($targets as $sid) {
                $created[] = ContactEntry::create([
                    'site_id'  => $sid,
                    'type'     => $type,
                    'kind'     => $kind ?: null,
                    'value'    => $value,
                    'label'    => trim($this->createLabel) ?: null,
                    'role'     => $role,
                    'geo_mode' => 'all',
                    'visible'  => $role !== 'hidden',
                    'order'    => 1,
                ])->id;
            }
        } finally {
            ContactEntry::enableAuditing();
        }

        ActivityLogService::log('entry.bulk.created', null, ['done' => count($created), 'type' => $type], context: 'bulk');
        $this->syncSites($targets);

        $this->closeCreate();

        $this->dispatch('toast',
            type: 'success',
            message: 'Створено: '.count($created),
            action: 'bulkPurgeCreated',
            actionLabel: 'Відмінити',
            actionData: ['ids' => $created],
        );
    }

    // ─── Bulk: attach selection as reserves of a primary ──────────────────

    public function openAttach(): void
    {
        if (! $this->hasSelection()) {
            return;
        }
        if (! $this->selectionIsSingleEntity()) {
            $this->dispatch('toast', type: 'error', message: 'Приєднання — лише для записів одного виду');

            return;
        }
        // A reserve lives on its primary's site, so the whole selection must be one site.
        if ($this->selectedSourceQuery()->reorder()->distinct()->pluck('site_id')->count() !== 1) {
            $this->dispatch('toast', type: 'error', message: 'Оберіть записи лише одного сайту');

            return;
        }

        $this->attachParent = '';
        $this->attaching = true;
    }

    public function closeAttach(): void
    {
        $this->attaching = false;
        $this->attachParent = '';
    }

    /** Candidate primaries to attach the selection to (same site+type+kind, not selected). */
    protected function attachCandidates()
    {
        $first = $this->selectedSourceQuery()->reorder()->first();
        if (! $first) {
            return collect();
        }

        $selectedIds = $this->selectAllMatching ? [] : array_map('intval', $this->selected);

        return $this->applyVisibility(ContactEntry::query())
            ->where('site_id', $first->site_id)
            ->where('type', $first->type)
            ->when($first->kind, fn ($q) => $q->where('kind', $first->kind))
            ->where('role', 'primary')
            ->whereNull('parent_id')
            ->whereNotIn('id', $selectedIds ?: [0])
            ->orderBy('value')
            ->get(['id', 'value', 'label']);
    }

    public function applyAttach(): void
    {
        $parent = ((int) $this->attachParent)
            ? $this->applyVisibility(ContactEntry::query())->find((int) $this->attachParent)
            : null;
        if (! $parent || $parent->role !== 'primary' || ! is_null($parent->parent_id)) {
            $this->dispatch('toast', type: 'error', message: 'Оберіть активний запис');

            return;
        }

        $user = Auth::user();
        $snapshot = [];
        $done = 0;

        ContactEntry::disableAuditing();
        try {
            $this->selectedSourceQuery()->chunkById(500, function ($rows) use ($parent, $user, &$snapshot, &$done) {
                foreach ($rows as $e) {
                    if (! $user || ! $user->can('update', $e) || $e->id === $parent->id
                        || $e->site_id !== $parent->site_id || $e->type !== $parent->type
                        || ($parent->kind && $e->kind !== $parent->kind) || $e->backups()->exists()) {
                        continue;
                    }
                    $snapshot[$e->id] = ['role' => $e->role, 'parent_id' => $e->parent_id, 'geo_tag' => $e->geo_tag, 'geo_mode' => $e->geo_mode, 'countries' => $e->countries, 'visible' => $e->visible];
                    // A reserve stores neutral geo and reads through to its primary.
                    $e->update(['role' => 'backup', 'parent_id' => $parent->id, 'geo_tag' => null, 'geo_mode' => 'all', 'countries' => null, 'visible' => true]);
                    $done++;
                }
            });
        } finally {
            ContactEntry::enableAuditing();
        }

        if ($done > 0) {
            ActivityLogService::log('entry.bulk.attached', null, ['done' => $done, 'parent_id' => $parent->id], context: 'bulk');
            $this->syncSites([(int) $parent->site_id]);
        }

        $this->closeAttach();
        $this->clearSelected();

        if ($done === 0) {
            $this->dispatch('toast', type: 'error', message: 'Нічого не приєднано (перевірте сайт/вид)');

            return;
        }

        $this->dispatch('toast',
            type: 'success',
            message: "Приєднано як резерв: {$done}",
            action: 'bulkRestoreRole',
            actionLabel: 'Відмінити',
            actionData: ['snapshot' => $snapshot],
        );
    }

    // ─── Undo targets for create / duplicate / move ───────────────────────

    #[On('bulkPurgeCreated')]
    public function bulkPurgeCreated(array $ids): void
    {
        $user = Auth::user();
        $siteIds = ContactEntry::whereIn('id', $ids)->pluck('site_id')->map(fn ($id) => (int) $id)->all();
        ContactEntry::whereIn('id', $ids)->get()->each(function (ContactEntry $e) use ($user) {
            if ($user && $user->can('delete', $e)) {
                $e->forceDelete();
            }
        });
        $this->syncSites($siteIds);

        $this->dispatch('toast', type: 'success', message: 'Скасовано');
    }

    #[On('bulkRestoreMove')]
    public function bulkRestoreMove(array $snapshot): void
    {
        if (empty($snapshot)) {
            return;
        }
        $user = Auth::user();
        $siteIds = array_values(array_map('intval', array_values($snapshot)));
        ContactEntry::withTrashed()->whereIn('id', array_keys($snapshot))->get()
            ->each(function (ContactEntry $e) use ($snapshot, $user, &$siteIds) {
                if ($user && $user->can('update', $e)) {
                    $siteIds[] = (int) $e->site_id;
                    $e->update(['site_id' => $snapshot[$e->id]]);
                }
            });
        $this->syncSites($siteIds);

        $this->dispatch('toast', type: 'success', message: 'Повернено');
    }

    // ─── Export current filter to CSV ─────────────────────────────────────

    public function export()
    {
        $filename = 'contacts-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
            fputcsv($out, ['Тип', 'Значення', 'Мітка', 'Сайт', 'Гео', 'Роль', 'Видимість']);

            $this->bulkQuery()->with('site')->orderBy('created_at', 'desc')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $e) {
                        fputcsv($out, [
                            $e->type,
                            $e->value,
                            $e->label,
                            $e->site?->name ?? '',
                            $e->geo_label,
                            $e->role,
                            $e->visible ? 'так' : 'ні',
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function withSkipped(string $message, int $skipped): string
    {
        return $skipped > 0 ? "{$message} • {$skipped} пропущено" : $message;
    }

    private function syncSites(array $siteIds): void
    {
        // In the wizard, hold the push back — nothing goes to the live sites or
        // the plugin until the manager hits "Готово" (then wizFlushSync runs).
        if ($this->mode === 'wizard') {
            $this->wizPendingSites = array_values(array_unique(array_merge(
                $this->wizPendingSites, array_map('intval', $siteIds)
            )));

            return;
        }

        $this->pushSites($siteIds);
    }

    /** Actually push the given sites to the WP plugin (immediate). */
    private function pushSites(array $siteIds): void
    {
        $user = Auth::user();
        $siteIds = array_values(array_unique(array_filter(array_map('intval', $siteIds))));
        if (empty($siteIds)) {
            return;
        }

        $allowed = Site::query()
            ->whereIn('id', $siteIds)
            ->get()
            ->filter(fn (Site $site) => $user && $user->can('update', $site))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        app(SitePluginSyncService::class)->syncMany($allowed);
    }

    public function render()
    {
        $typeLabels = $this->enabledTypeLabels();
        $this->normalizeTypeFilterForEnabledTypes();
        $this->normalizeRoleFilterForType();

        $entries = $this->applySort(
            $this->bulkQuery()->with(['site', 'parent:id,value,label,type,kind'])
        )->paginate(15);

        // Small preview for the edit/replace drawers (explicit picks survive
        // filters, so resolve them directly; "all matching" = current filter).
        $selectionPreview = collect();
        if ($this->editingField || $this->editingReplace) {
            $q = $this->selectAllMatching
                ? $this->bulkQuery()
                : $this->selectedQuery();
            $selectionPreview = $q->with('site')->limit(8)->get();
        }

        // Full removable list for the review drawer (explicit picks only).
        $reviewItems = ($this->reviewingSelection && ! $this->selectAllMatching)
            ? $this->selectedQuery()->with('site')->orderBy('site_id')->get()
            : collect();

        return view('livewire.data-browser', [
            'entries' => $entries,
            'totalCount' => $this->applyVisibility(ContactEntry::query())
                ->when($this->trashed, fn ($q) => $q->onlyTrashed())
                ->where('type', $this->typeFilter)->count(),
            'pageIds' => $entries->pluck('id')->map(fn ($i) => (int) $i)->all(),
            'sites' => $this->sitesForUser(),
            'attachCandidates' => $this->attaching ? $this->attachCandidates() : collect(),
            'selectionPreview' => $selectionPreview,
            'reviewItems' => $reviewItems,
            'types' => $typeLabels,
            'kinds' => $this->kindLabelsForType($this->typeFilter),
            'selectionEntities' => $this->selectionEntityLabels(),
            'valueGroups' => $this->axis === 'value' ? $this->valueGroups() : collect(),
            'siteGroups' => $this->siteFailoverGroups(),
            'reserveGroups' => $this->reserveGroups(),
        ]);
    }
}
