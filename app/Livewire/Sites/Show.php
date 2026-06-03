<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use App\Models\ActivityLog;
use App\Models\ContactEntry;
use App\Models\SiteGroup;
use App\Services\ActivityLogService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.app')]
#[Title('Сайт')]
class Show extends Component
{
    private const DEFAULT_GEO_TABS = ['UA', 'RU', 'BY'];

    /** Phones + messengers are always present; the rest can be toggled per-site. */
    public const REQUIRED_CATEGORIES = ['phones', 'messengers'];
    private const OPTIONAL_CATEGORIES = ['prices', 'addresses', 'socials', 'custom'];
    private const DEFAULT_CATEGORIES = ['phones', 'messengers', 'prices'];

    /** Data-category key → ContactEntry type ('custom' has no backing type yet). */
    private const CATEGORY_TYPES = [
        'phones' => 'phone', 'messengers' => 'messenger', 'prices' => 'price',
        'addresses' => 'address', 'socials' => 'social', 'custom' => 'custom',
    ];

    public Site $site;
    public ?int $openPhoneId = null;

    /** Enabled data categories shown in the Data tab (Settings → Категорії даних). */
    public array $dataCategories = self::DEFAULT_CATEGORIES;

    public bool $failoverEnabled = true;
    public string $failoverInterval = '5min';
    public int $failoverThreshold = 3;
    public string $siteGroupId = '';
    public string $siteName = '';

    // ContactEntry CRUD state
    public bool $editingEntry = false;
    public bool $addingEntry = false;
    public ?int $editEntryId = null;
    public string $entryType = 'phone';
    public string $entryValue = '';
    public string $entryLabel = '';
    public string $entryRole = 'primary';
    public string $entryGeoTag = '';
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
    public array $messengerKinds = [];
    public string $newMessengerKind = '';

    // Geo isolation rules for conflict detection
    public array $geoRules = [['id' => 1, 'groups' => [['UA'], ['RU', 'BY']]]];
    public int $nextRuleId = 2;
    public array $newRuleA = [];
    public array $newRuleB = [];

    // Assign-backup modal state
    public bool $assigningBackup = false;
    public ?int $assignBackupPhoneId = null;

    // Styled confirmation modal state
    public bool $confirmingAction = false;
    public string $confirmAction = '';
    public ?int $confirmEntryId = null;
    public ?string $confirmGeoCode = null;
    public ?string $confirmMessengerKind = null;
    public ?string $confirmCategory = null;
    public ?string $confirmSiteStatus = null;
    public string $confirmDeleteSiteName = '';
    public string $confirmTitle = '';
    public string $confirmSubject = '';
    public string $confirmMessage = '';
    public string $confirmButtonLabel = 'Видалити';
    public bool $confirmIsDanger = true;

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
        $this->geoTabs = $this->site->geo_tabs === null
            ? $this->defaultGeoTabsFromEntries()
            : $this->normalizeGeoTabs($this->site->geo_tabs);
        if ($this->site->geo_tabs === null) {
            $this->saveGeoTabs();
        }
        $this->geoRules = $this->site->geo_rules ?? $this->geoRules;
        $this->nextRuleId = (int) collect($this->geoRules)->max('id') + 1;

        $this->dataCategories = $this->normalizeCategories(
            $this->site->data_categories ?? self::DEFAULT_CATEGORIES
        );
        $this->messengerKinds = $this->site->messenger_kinds === null
            ? $this->defaultMessengerKindsFromEntries()
            : $this->normalizeMessengerKinds($this->site->messenger_kinds);
        if ($this->site->messenger_kinds === null) {
            $this->saveMessengerKinds();
        }

        $this->failoverEnabled   = $this->site->failover_enabled ?? true;
        $this->failoverInterval  = $this->site->failover_interval ?? '5min';
        $this->failoverThreshold = $this->site->failover_threshold ?? 3;
        $this->siteName = $this->site->name;
        $this->siteGroupId = (string) (SiteGroup::query()
            ->where('name', $this->site->group)
            ->value('id') ?? '');
    }

    private function normalizeGeoTabs(?array $tabs): array
    {
        return collect($tabs ?? [])
            ->map(fn($code) => strtoupper(trim((string) $code)))
            ->filter(fn($code) => $code !== '' && strlen($code) <= 3)
            ->unique()
            ->values()
            ->all();
    }

    private function saveGeoTabs(): void
    {
        $this->authorize('update', $this->site);
        $this->geoTabs = $this->normalizeGeoTabs($this->geoTabs);
        $this->site->forceFill(['geo_tabs' => $this->geoTabs])->save();
    }

    private function saveGeoRules(): void
    {
        $this->authorize('update', $this->site);
        $this->site->forceFill(['geo_rules' => $this->geoRules])->save();
    }

    private function normalizeMessengerKinds(?array $kinds): array
    {
        return collect($kinds ?? [])
            ->map(fn($kind) => $this->resolveMessengerKind((string) $kind))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function saveMessengerKinds(): void
    {
        $this->authorize('update', $this->site);
        $this->messengerKinds = $this->normalizeMessengerKinds($this->messengerKinds);
        $this->site->forceFill(['messenger_kinds' => $this->messengerKinds])->save();
    }

    private function resolveMessengerKind(string $value): ?string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }

        $normalized = preg_replace('/[^a-z0-9]+/', '', $value);
        $aliases = [
            'fb' => 'messenger',
            'facebook' => 'messenger',
            'facebookmessenger' => 'messenger',
            'tg' => 'telegram',
            'wa' => 'whatsapp',
            'vb' => 'viber',
        ];

        foreach (\App\Models\ContactEntry::MSG_KINDS as $key => $meta) {
            $candidates = [
                $key,
                strtolower($meta['label'] ?? ''),
                strtolower($meta['short'] ?? ''),
                preg_replace('/[^a-z0-9]+/', '', strtolower($meta['label'] ?? '')),
            ];

            if (in_array($value, $candidates, true) || in_array($normalized, $candidates, true)) {
                return $key;
            }
        }

        if (isset($aliases[$normalized])) {
            return $aliases[$normalized];
        }

        $slug = preg_replace('/[^a-z0-9]+/', '-', $value);
        $slug = trim((string) $slug, '-');

        return $slug !== '' && strlen($slug) <= 40 ? $slug : null;
    }

    /** Keep required categories, drop unknown ones, preserve canonical order. */
    private function normalizeCategories(array $cats): array
    {
        $all = array_merge(self::REQUIRED_CATEGORIES, self::OPTIONAL_CATEGORIES);
        $enabled = array_merge(self::REQUIRED_CATEGORIES, $cats);

        return collect($all)
            ->filter(fn($key) => in_array($key, $enabled, true))
            ->values()
            ->all();
    }

    private function canEntryType(string $type, string $action = 'read'): bool
    {
        return auth()->user()?->canEntryType($type, $action) ?? false;
    }

    private function canCategory(string $category, string $action = 'read'): bool
    {
        $type = self::CATEGORY_TYPES[$category] ?? null;

        return $type ? $this->canEntryType($type, $action) : false;
    }

    private function visibleDataCategories(): array
    {
        return collect($this->dataCategories)
            ->filter(fn (string $category) => $this->canCategory($category))
            ->values()
            ->all();
    }

    public function toggleDataCategory(string $key): void
    {
        $this->authorize('update', $this->site);

        // Required categories cannot be turned off; unknown keys are ignored.
        if (in_array($key, self::REQUIRED_CATEGORIES, true) || !in_array($key, self::OPTIONAL_CATEGORIES, true)) {
            return;
        }

        // Turning a category OFF while it still holds rows → confirm, then trash them.
        if (in_array($key, $this->dataCategories, true)) {
            $type = self::CATEGORY_TYPES[$key] ?? null;
            $count = $type ? $this->site->contactEntries()->where('type', $type)->count() : 0;
            if ($count > 0) {
                $this->requestDisableCategory($key, $count);
                return;
            }
        }

        $this->applyCategoryToggle($key);
    }

    /** Flip a category on/off and persist (no row side-effects). */
    private function applyCategoryToggle(string $key): void
    {
        if (in_array($key, $this->dataCategories, true)) {
            $this->dataCategories = array_values(array_filter($this->dataCategories, fn($c) => $c !== $key));
        } else {
            $this->dataCategories[] = $key;
        }

        $this->dataCategories = $this->normalizeCategories($this->dataCategories);
        $this->site->forceFill(['data_categories' => $this->dataCategories])->save();
    }

    public function requestDisableCategory(string $key, ?int $count = null): void
    {
        $this->authorize('update', $this->site);
        if (!in_array($key, self::OPTIONAL_CATEGORIES, true) || !in_array($key, $this->dataCategories, true)) {
            return;
        }

        $type = self::CATEGORY_TYPES[$key] ?? null;
        $count ??= $type ? $this->site->contactEntries()->where('type', $type)->count() : 0;

        $labels = ['prices' => 'Ціни', 'addresses' => 'Адреси', 'socials' => 'Соцмережі', 'custom' => 'Custom'];
        $label = $labels[$key] ?? $key;

        $this->confirmingAction = true;
        $this->confirmAction = 'disable-category';
        $this->confirmEntryId = null;
        $this->confirmGeoCode = null;
        $this->confirmMessengerKind = null;
        $this->confirmSiteStatus = null;
        $this->confirmCategory = $key;
        $this->confirmTitle = 'Вимкнути «' . $label . '»?';
        $this->confirmSubject = $label . ' · ' . $count . ' ' . ($count === 1 ? 'запис' : 'записів');
        $this->confirmMessage = 'Категорію буде вимкнено, а її записи переміщено в Кошик. Їх можна відновити звідти.';
        $this->confirmButtonLabel = 'Вимкнути і прибрати';
        $this->confirmIsDanger = true;
    }

    /** Trash this site's rows of the category's type, then turn the category off. */
    public function disableCategory(string $key): void
    {
        $this->authorize('update', $this->site);
        if (!in_array($key, self::OPTIONAL_CATEGORIES, true)) {
            return;
        }

        $type = self::CATEGORY_TYPES[$key] ?? null;
        if ($type) {
            // Soft-delete (recoverable from the trash); reserves cascade with their primary.
            $this->site->contactEntries()->where('type', $type)->get()->each->delete();
        }

        if (in_array($key, $this->dataCategories, true)) {
            $this->dataCategories = $this->normalizeCategories(
                array_values(array_filter($this->dataCategories, fn($c) => $c !== $key))
            );
            $this->site->forceFill(['data_categories' => $this->dataCategories])->save();
        }

        $this->dispatch('toast', type: 'success', message: 'Категорію вимкнено · записи в Кошику');
    }

    private function defaultGeoTabsFromEntries(): array
    {
        $codes = $this->site->contactEntries()
            ->get(['geo_tag', 'countries'])
            ->flatMap(function ($entry) {
                return array_merge(
                    $entry->geo_tag ? [$entry->geo_tag] : [],
                    $entry->countries ?? []
                );
            })
            ->all();

        return $this->normalizeGeoTabs(array_merge(self::DEFAULT_GEO_TABS, $codes));
    }

    private function defaultMessengerKindsFromEntries(): array
    {
        $kinds = $this->site->contactEntries()
            ->where('type', 'messenger')
            ->pluck('kind')
            ->all();

        return $this->normalizeMessengerKinds($kinds);
    }

    private function filterForGeo(Collection $entries, string $geo): Collection
    {
        if ($geo === 'all') return $entries;
        return $entries->filter(fn($e) => $e->visibleForGeo($geo))->values();
    }

    /** Entries visible for a selected preview tab. */
    private function filterByGeoTag(Collection $entries, string $geo): Collection
    {
        if ($geo === 'all') return $entries;
        return $entries->filter(fn($e) => $e->visibleForGeo($geo))->values();
    }

    private function filterByPreviewTab(Collection $entries, string $geo): Collection
    {
        if ($geo === 'all') return $entries;

        return $entries->filter(fn($e) => $this->entryPreviewTag($e) === $geo)->values();
    }

    private function entryPreviewTag(\App\Models\ContactEntry $entry): ?string
    {
        if ($entry->geo_tag) {
            return $entry->geo_tag;
        }

        $countries = $entry->countries ?? [];
        if ($entry->geo_mode === 'only' && count($countries) === 1) {
            return $countries[0];
        }

        return null;
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

    public function triggerFailover(int $fromId, int $toId): void
    {
        $this->performFailover($fromId, $toId, 'site.failover.triggered', 'manual', 'manual trigger', 'Перемкнено на резерв');
    }

    public function restoreFailover(int $fromId, int $toId): void
    {
        $this->performFailover($fromId, $toId, 'site.failover.restored', 'restore', 'manual restore', 'Відновлено активний номер');
    }

    /**
     * Promote a reserve to active and persist it (the failed primary becomes a
     * reserve of the new active; its other reserves are re-pointed). Persisting the
     * swap is what makes a manual trigger survive a reload — and what stops the 2nd
     * run from 404-ing on a now-stale role lookup. owen-it is muted for the swap;
     * the single failover activity row (with both numbers) is the human record.
     */
    private function performFailover(int $fromId, int $toId, string $action, string $mode, string $cause, string $okMsg): void
    {
        $this->authorize('update', $this->site);

        $scope = ContactEntry::query()->where('site_id', $this->site->id)->where('type', 'phone');
        $from = (clone $scope)->find($fromId);
        $to   = (clone $scope)->find($toId);

        // A stale or double-clicked trigger must report cleanly, never 404.
        if (! $from || ! $to || $from->id === $to->id) {
            ActivityLogService::log($action, $this->site, [
                'from_id' => $fromId, 'to_id' => $toId, 'mode' => $mode,
                'cause' => $cause, 'ok' => false, 'error' => 'number_not_found',
            ]);
            $this->dispatch('toast', type: 'error', message: 'Не вдалося перемкнути: номер не знайдено.');
            return;
        }

        $fromValue = $from->value;
        $toValue   = $to->value;
        $geo = $to->preview_geo_label ?? $from->preview_geo_label;

        ContactEntry::disableAuditing();
        try {
            DB::transaction(function () use ($from, $to) {
                // Reserve takes over, inheriting the failed primary's targeting.
                $to->forceFill([
                    'role' => 'primary', 'parent_id' => null, 'visible' => true,
                    'geo_tag' => $from->geo_tag, 'geo_mode' => $from->geo_mode, 'countries' => $from->countries,
                ])->save();

                // The old primary's remaining reserves follow the new active number.
                ContactEntry::query()
                    ->where('parent_id', $from->id)
                    ->where('id', '!=', $to->id)
                    ->update(['parent_id' => $to->id]);

                // Failed primary parks as a reserve under the new active.
                $from->forceFill(['role' => 'backup', 'parent_id' => $to->id])->save();
            });
        } finally {
            ContactEntry::enableAuditing();
        }

        ActivityLogService::log($action, $this->site, [
            'from_id' => $from->id, 'from' => $fromValue,
            'to_id' => $to->id, 'to' => $toValue,
            'geo' => $geo, 'mode' => $mode, 'cause' => $cause, 'ok' => true,
        ]);

        $this->dispatch('phones-updated');
        $this->dispatch('toast', type: 'success', message: $okMsg.': '.($toValue ?? '—'));
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
        $this->entryGeoTag    = $entry->geo_tag ?? '';
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
        if (! $this->canEntryType($type, 'create')) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на створення цього типу даних');

            return;
        }

        $this->resetEntryForm();
        $this->entryType     = $type;
        $this->entryParentId = $parentId;
        // Socials default to the first platform so the picker isn't blank.
        if ($type === 'social') {
            $this->entryKind = array_key_first(\App\Models\ContactEntry::SOCIAL_KINDS);
        }
        if ($parentId) {
            $this->entryRole = 'backup';
            // A messenger reserve must be the same platform as its primary (e.g. Telegram → Telegram).
            if ($type === 'messenger') {
                $parent = \App\Models\ContactEntry::where('id', $parentId)
                    ->where('site_id', $this->site->id)
                    ->first();
                $this->entryKind = $parent?->kind ?? '';
            }
        }
        if ($geoTag) {
            $this->entryGeoTag = $geoTag;
        }
        $this->addingEntry  = true;
        $this->editingEntry = false;
        $this->entryFormKey++;
    }

    public function addPriceToSku(string $sku): void
    {
        $this->authorize('create', \App\Models\ContactEntry::class);
        if (! $this->canEntryType('price', 'create')) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на створення цін');

            return;
        }

        $template = $this->site->contactEntries()
            ->where('type', 'price')
            ->where('sku', $sku)
            ->orderBy('order')
            ->first();

        $this->addEntry('price');
        $this->entrySku = $sku;
        $this->entryLabel = $template?->label ?? '';
        $this->entryPriceUnit = $template?->price_unit ?? '';
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
            $allowedMessengerKinds = $this->normalizeMessengerKinds(array_merge(
                array_keys(\App\Models\ContactEntry::MSG_KINDS),
                $this->messengerKinds
            ));
            $rules['entryValue'] = 'required|max:500';
            $rules['entryKind']  = 'required|in:' . implode(',', $allowedMessengerKinds);
        } elseif ($this->entryType === 'price') {
            $rules['entryPrice']    = 'required|numeric|min:0';
            $rules['entryCurrency'] = 'required|size:3';
            $rules['entrySku']      = 'required|max:255';
        } elseif ($this->entryType === 'social') {
            $rules['entryValue'] = 'required|max:500';
            $rules['entryKind']  = 'required|in:' . implode(',', array_keys(\App\Models\ContactEntry::SOCIAL_KINDS));
            $rules['entryLabel'] = 'nullable|max:255';
        } elseif ($this->entryType === 'address') {
            $rules['entryValue'] = 'required|max:500';
            $rules['entryLabel'] = 'nullable|max:255';
        }

        $this->validate($rules);

        $requiredAction = $this->editEntryId ? 'edit' : 'create';
        if (! $this->canEntryType($this->entryType, $requiredAction)) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав для цього типу даних');

            return;
        }

        $parentEntry = null;
        if ($this->entryRole === 'backup' && $this->entryParentId) {
            $parentEntry = \App\Models\ContactEntry::where('id', $this->entryParentId)
                ->where('site_id', $this->site->id)
                ->firstOrFail();
        }

        // A reserve carries no geo of its own — it reads through to its primary
        // at render time (see ContactEntry::geoOwner), so we store neutral geo
        // and never let the two drift apart.
        $isReserve      = $this->entryRole === 'backup' && $parentEntry;
        $entryGeoTag    = $isReserve ? null  : ($this->entryGeoTag ?: null);
        $entryGeoMode   = $isReserve ? 'all' : $this->entryGeoMode;
        $entryCountries = $isReserve ? []    : $this->entryCountries;
        // Messenger reserves inherit the platform of their primary (Telegram can't reserve to Viber).
        $entryKind = ($parentEntry && $this->entryType === 'messenger') ? $parentEntry->kind : $this->entryKind;

        $data = [
            'site_id'    => $this->site->id,
            'type'       => $this->entryType,
            'kind'       => $entryKind ?: null,
            'value'      => $this->entryValue,
            'label'      => $this->entryLabel ?: null,
            'role'       => $this->entryRole,
            'geo_tag'    => $entryGeoTag ?: null,
            'geo_mode'   => $entryGeoMode,
            'countries'  => $entryCountries ?: null,
            'parent_id'  => $this->entryRole === 'backup' ? $this->entryParentId : null,
            // Price columns belong to the price type only — otherwise the form's
            // default currency ('EUR') would leak onto phones/messengers and a plain
            // edit would look like "price changed" in the audit feed.
            'currency'   => $this->entryType === 'price' ? ($this->entryCurrency ?: null) : null,
            'price'      => $this->entryType === 'price' ? $this->entryPrice : null,
            'old_price'  => $this->entryType === 'price' ? $this->entryOldPrice : null,
            'price_unit' => $this->entryType === 'price' ? ($this->entryPriceUnit ?: null) : null,
            'sku'        => $this->entryType === 'price' ? ($this->entrySku ?: null) : null,
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
        $savedKind = $entryKind;
        $this->resetEntryForm();
        $this->dispatch('toast', type: 'success', message: 'Збережено');
        if ($savedType === 'phone') {
            $this->dispatch('phones-updated');
        }
        if ($savedType === 'messenger' && $savedKind && !in_array($savedKind, $this->messengerKinds, true)) {
            $this->messengerKinds[] = $savedKind;
            $this->saveMessengerKinds();
        }
    }

    public function promoteEntry(int $id): void
    {
        $entry = \App\Models\ContactEntry::with('parent')->findOrFail($id);
        $this->authorize('update', $entry);
        // Promoting a reserve makes it stand-alone — capture the geo it had been
        // inheriting from its (now-detached) primary so its targeting is preserved.
        $parent = $entry->parent;
        $entry->update([
            'role'      => 'primary',
            'parent_id' => null,
            'visible'   => true,
            'geo_tag'   => $parent?->geo_tag,
            'geo_mode'  => $parent?->geo_mode ?? 'all',
            'countries' => $parent?->countries,
        ]);
        $this->dispatch('toast', type: 'success', message: 'Переведено в активні');
        if ($entry->type === 'phone') {
            $this->dispatch('phones-updated');
        }
    }

    /**
     * Roll back a field edit: write an owen-it audit's old_values back onto the
     * record. Only 'updated' events (the rollback itself is logged as a new edit).
     */
    public function rollbackAudit(int $auditId): void
    {
        $audit = \OwenIt\Auditing\Models\Audit::find($auditId);
        if (! $audit || $audit->event !== 'updated') {
            return;
        }

        // Allow-list of rollbackable fields per type — never restore identity/scope
        // columns (role, site_id, parent_id, client_id…) even if owen-it captured them.
        $allowedFields = match ($audit->auditable_type) {
            \App\Models\ContactEntry::class => ['value', 'label', 'kind', 'geo_tag', 'geo_mode', 'countries', 'visible', 'currency', 'price', 'old_price', 'price_unit', 'sku', 'order'],
            \App\Models\Site::class         => ['name', 'url', 'status', 'notes', 'group', 'group_color', 'geo_tabs', 'geo_rules', 'data_categories', 'messenger_kinds', 'failover_enabled', 'failover_interval', 'failover_threshold'],
            default                         => null,
        };
        if ($allowedFields === null) {
            return;
        }

        $model = $audit->auditable;
        // Anti-IDOR: this component only rolls back records of THIS site.
        $belongsToSite = ($model instanceof \App\Models\ContactEntry && (int) $model->site_id === (int) $this->site->id)
            || ($model instanceof \App\Models\Site && (int) $model->id === (int) $this->site->id);
        if (! $model || ! $belongsToSite) {
            $this->dispatch('toast', type: 'error', message: 'Запис недоступний для відновлення');

            return;
        }

        $this->authorize('update', $model);

        $old = collect((array) $audit->old_values)->only($allowedFields)->all();

        if (empty($old)) {
            return;
        }

        $model->update($old);
        $this->dispatch('toast', type: 'success', message: 'Відновлено попередні значення');

        if ($model instanceof \App\Models\ContactEntry && $model->type === 'phone') {
            $this->dispatch('phones-updated');
        }
    }

    public function deleteEntry(int $id): void
    {
        $entry = \App\Models\ContactEntry::findOrFail($id);
        $this->authorize('delete', $entry);
        $isPhone = $entry->type === 'phone';
        // backups()->delete() is a builder mass-delete that bypasses owen-it — log
        // the cascade explicitly so the reserves' removal leaves a trail (critic B3).
        $backupIds = $entry->backups()->pluck('id');
        if ($backupIds->isNotEmpty()) {
            \App\Services\ActivityLogService::log('entry.deleted', $entry, [
                'cascade' => 'backups', 'backup_ids' => $backupIds->all(), 'count' => $backupIds->count(),
            ]);
        }
        $entry->backups()->delete();
        $entry->delete();
        $this->resetEntryForm();
        $this->closePhone();
        $this->dispatch('toast', type: 'success', message: 'Видалено');
        if ($isPhone) {
            $this->dispatch('phones-updated');
        }
    }

    public function requestDeleteEntry(int $id): void
    {
        $entry = \App\Models\ContactEntry::with('backups')->findOrFail($id);
        $this->authorize('delete', $entry);

        $kind = match ($entry->type) {
            'phone' => $entry->role === 'backup' ? 'резервний телефон' : 'телефон',
            'messenger' => $entry->role === 'backup' ? 'резервний месенджер' : 'месенджер',
            'price' => 'ціну',
            default => 'запис',
        };

        $backupCount = $entry->backups->count();

        $this->confirmingAction = true;
        $this->confirmAction = 'delete-entry';
        $this->confirmEntryId = $entry->id;
        $this->confirmGeoCode = null;
        $this->confirmTitle = 'Видалити ' . $kind . '?';
        $this->confirmSubject = $entry->value . ($entry->label ? ' · ' . $entry->label : '');
        $this->confirmMessage = $backupCount > 0
            ? 'Разом із цим записом буде видалено резервів: ' . $backupCount . '. Дію не можна скасувати.'
            : 'Запис буде видалено з бази. Дію не можна скасувати.';
    }

    public function requestRemoveGeoRule(int $id): void
    {
        $rule = collect($this->geoRules)->firstWhere('id', $id);
        if (!$rule) {
            return;
        }

        $this->authorize('update', $this->site);
        $this->confirmingAction = true;
        $this->confirmAction = 'remove-geo-rule';
        $this->confirmEntryId = $id;
        $this->confirmGeoCode = null;
        $this->confirmMessengerKind = null;
        $this->confirmTitle = 'Видалити правило ізоляції?';
        $this->confirmSubject = implode(' · ', $rule['groups'][0]) . ' ↔ ' . implode(' · ', $rule['groups'][1]);
        $this->confirmMessage = 'Правило буде прибрано з цього сайту і не повернеться після оновлення сторінки.';
    }

    public function requestRemoveGeoTab(string $code): void
    {
        $code = strtoupper(trim($code));
        if (!$code || !in_array($code, $this->geoTabs, true)) {
            return;
        }

        $this->authorize('update', $this->site);
        $this->confirmingAction = true;
        $this->confirmAction = 'remove-geo-tab';
        $this->confirmEntryId = null;
        $this->confirmGeoCode = $code;
        $this->confirmMessengerKind = null;
        $this->confirmTitle = 'Видалити ' . $code . ' з приналежності?';
        $this->confirmSubject = $code;
        $this->confirmMessage = 'Пункт зникне з перемикача приналежності для цього сайту і не повернеться після оновлення сторінки.';
    }

    public function requestSetSiteStatus(string $status): void
    {
        if (!in_array($status, ['active', 'maintenance'], true)) {
            return;
        }

        $this->authorize('update', $this->site);

        if ($this->site->status === $status) {
            return;
        }

        $label = $status === 'active' ? 'Активний' : 'Пауза';

        $this->confirmingAction = true;
        $this->confirmAction = 'set-site-status';
        $this->confirmEntryId = null;
        $this->confirmGeoCode = null;
        $this->confirmMessengerKind = null;
        $this->confirmSiteStatus = $status;
        $this->confirmTitle = 'Змінити стан сайту?';
        $this->confirmSubject = $this->site->name . ' -> ' . $label;
        $this->confirmMessage = $status === 'active'
            ? 'Сайт буде повернено в активний стан і він знову відображатиметься як робочий.'
            : 'Сайт буде переведено на паузу. Це не видаляє дані, але позначить сайт як призупинений.';
        $this->confirmButtonLabel = 'Підтвердити';
        $this->confirmIsDanger = false;
    }

    public function requestDeleteSite(): void
    {
        $this->authorize('delete', $this->site);

        $this->confirmingAction = true;
        $this->confirmAction = 'delete-site';
        $this->confirmEntryId = null;
        $this->confirmGeoCode = null;
        $this->confirmMessengerKind = null;
        $this->confirmSiteStatus = null;
        $this->confirmDeleteSiteName = '';
        $this->confirmTitle = 'Видалити сайт?';
        $this->confirmSubject = $this->site->name;
        $this->confirmMessage = 'Щоб підтвердити видалення, введіть точну назву сайту.';
        $this->confirmButtonLabel = 'Видалити сайт';
        $this->confirmIsDanger = true;
    }

    public function cancelConfirm(): void
    {
        $this->confirmingAction = false;
        $this->confirmAction = '';
        $this->confirmEntryId = null;
        $this->confirmGeoCode = null;
        $this->confirmMessengerKind = null;
        $this->confirmCategory = null;
        $this->confirmSiteStatus = null;
        $this->confirmDeleteSiteName = '';
        $this->confirmTitle = '';
        $this->confirmSubject = '';
        $this->confirmMessage = '';
        $this->confirmButtonLabel = 'Видалити';
        $this->confirmIsDanger = true;
    }

    public function confirmPendingAction(): void
    {
        if ($this->confirmAction === 'set-site-status' && $this->confirmSiteStatus) {
            $status = $this->confirmSiteStatus;
            $this->cancelConfirm();
            $this->setSiteStatus($status);
            return;
        }

        if ($this->confirmAction === 'delete-site') {
            if ($this->confirmDeleteSiteName !== $this->site->name) {
                return;
            }
            $this->cancelConfirm();
            $this->deleteSite();
            return;
        }

        if ($this->confirmAction === 'delete-entry' && $this->confirmEntryId) {
            $id = $this->confirmEntryId;
            $this->cancelConfirm();
            $this->deleteEntry($id);
            return;
        }

        if ($this->confirmAction === 'remove-geo-tab' && $this->confirmGeoCode) {
            $code = $this->confirmGeoCode;
            $this->cancelConfirm();
            $this->removeGeoTab($code);
            return;
        }

        if ($this->confirmAction === 'remove-messenger-kind' && $this->confirmMessengerKind) {
            $kind = $this->confirmMessengerKind;
            $this->cancelConfirm();
            $this->removeMessengerKind($kind);
            return;
        }

        if ($this->confirmAction === 'remove-geo-rule' && $this->confirmEntryId) {
            $id = $this->confirmEntryId;
            $this->cancelConfirm();
            $this->removeGeoRule($id);
            return;
        }

        if ($this->confirmAction === 'disable-category' && $this->confirmCategory) {
            $key = $this->confirmCategory;
            $this->cancelConfirm();
            $this->disableCategory($key);
            return;
        }

        $this->cancelConfirm();
    }

    public function setSiteStatus(string $status): void
    {
        if (!in_array($status, ['active', 'maintenance'], true)) {
            return;
        }

        $this->authorize('update', $this->site);
        $this->site->update(['status' => $status]);
        $this->site = $this->site->fresh('client');
        $this->dispatch('toast', type: 'success', message: 'Стан сайту оновлено');
    }

    public function updateSiteName(): void
    {
        $this->authorize('update', $this->site);

        $this->validate([
            'siteName' => 'required|string|max:100',
        ]);

        $name = trim($this->siteName);
        if ($name === $this->site->name) {
            return;
        }

        $this->site->update(['name' => $name]);
        $this->site = $this->site->fresh('client');
        $this->siteName = $this->site->name;
        $this->dispatch('toast', type: 'success', message: 'Назву сайту оновлено');
    }

    public function regenerateApiKey(): void
    {
        $this->authorize('update', $this->site);

        $this->site->forceFill(['api_key' => Site::generateApiKey()])->save();
        $this->site = $this->site->fresh('client');
        $this->dispatch('toast', type: 'success', message: 'API ключ оновлено');
    }

    public function updateSiteGroup(): void
    {
        $this->authorize('update', $this->site);

        if ($this->siteGroupId === '') {
            return;
        }

        $group = SiteGroup::query()->findOrFail((int) $this->siteGroupId);

        $this->site->update([
            'group' => $group->name,
            'group_color' => $group->color,
        ]);

        $this->site = $this->site->fresh('client');
        $this->siteGroupId = (string) $group->id;
        $this->dispatch('toast', type: 'success', message: 'Групу сайту оновлено');
    }

    public function setSiteGroup(int $groupId): void
    {
        $this->siteGroupId = (string) $groupId;
        $this->updateSiteGroup();
    }

    public function deleteSite()
    {
        $this->authorize('delete', $this->site);
        $this->site->delete();
        $this->dispatch('toast', type: 'success', message: 'Сайт видалено');

        return $this->redirectRoute('sites.index');
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
        $this->entryGeoTag    = '';
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

    /** When a messenger reserve is linked to a primary, inherit its platform (2.1). */
    public function updatedEntryParentId($value): void
    {
        if ($this->entryType === 'messenger' && $value) {
            $parent = \App\Models\ContactEntry::where('id', $value)
                ->where('site_id', $this->site->id)
                ->first();
            if ($parent) {
                $this->entryKind = $parent->kind;
            }
        }
    }

    public function setEntryGeoTag(?string $code = null): void
    {
        $code = $code ? strtoupper(trim($code)) : '';
        if ($code !== '' && !in_array($code, $this->geoTabs, true)) {
            return;
        }

        $this->entryGeoTag = $code;
    }

    public function assignAsBackup(int $id, int $parentId): void
    {
        $entry = \App\Models\ContactEntry::findOrFail($id);
        $this->authorize('update', $entry);
        // Validate the parent belongs to this site; the reserve stores neutral
        // geo and reads through to the parent at render time (geoOwner).
        \App\Models\ContactEntry::where('id', $parentId)
            ->where('site_id', $this->site->id)
            ->firstOrFail();
        $entry->update([
            'role'      => 'backup',
            'parent_id' => $parentId,
            'geo_tag'   => null,
            'geo_mode'  => 'all',
            'countries' => null,
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
        $this->saveGeoTabs();
        $this->newGeoTab = '';
    }

    public function removeGeoTab(string $code): void
    {
        $this->geoTabs = array_values(array_filter($this->geoTabs, fn($t) => $t !== $code));
        $this->saveGeoTabs();
    }

    public function addMessengerKind(): void
    {
        $kind = $this->resolveMessengerKind($this->newMessengerKind);
        if (!$kind || in_array($kind, $this->messengerKinds, true)) {
            $this->newMessengerKind = '';
            return;
        }

        $this->messengerKinds[] = $kind;
        $this->saveMessengerKinds();
        $this->newMessengerKind = '';
    }

    public function requestRemoveMessengerKind(string $kind): void
    {
        $kind = $this->resolveMessengerKind($kind);
        if (!$kind || !in_array($kind, $this->messengerKinds, true)) {
            return;
        }

        $this->authorize('update', $this->site);

        $meta = ContactEntry::MSG_KINDS[$kind] ?? null;
        $label = $meta['label'] ?? ucfirst(str_replace(['-', '_'], ' ', $kind));

        $this->confirmingAction = true;
        $this->confirmAction = 'remove-messenger-kind';
        $this->confirmEntryId = null;
        $this->confirmGeoCode = null;
        $this->confirmMessengerKind = $kind;
        $this->confirmSiteStatus = null;
        $this->confirmTitle = 'Видалити платформу?';
        $this->confirmSubject = $label;
        $this->confirmMessage = 'Платформа зникне з перемикача месенджерів для цього сайту і не повернеться після оновлення сторінки.';
        $this->confirmButtonLabel = 'Видалити';
        $this->confirmIsDanger = true;
    }

    public function removeMessengerKind(string $kind): void
    {
        $kind = strtolower(trim($kind));
        $this->messengerKinds = array_values(array_filter($this->messengerKinds, fn($k) => $k !== $kind));
        $this->saveMessengerKinds();
    }

    public function addGeoRule(): void
    {
        $groupA = $this->normalizeGeoRuleGroup($this->newRuleA);
        $groupB = $this->normalizeGeoRuleGroup($this->newRuleB);

        if (empty($groupA) || empty($groupB) || array_intersect($groupA, $groupB)) return;

        $this->geoRules[] = [
            'id'     => $this->nextRuleId++,
            'groups' => [$groupA, $groupB],
        ];
        $this->saveGeoRules();
        $this->newRuleA = [];
        $this->newRuleB = [];
    }

    private function normalizeGeoRuleGroup(array $codes): array
    {
        return collect($codes)
            ->map(fn($code) => strtoupper(trim((string) $code)))
            ->filter(fn($code) => in_array($code, $this->geoTabs, true))
            ->unique()
            ->values()
            ->all();
    }

    public function removeGeoRule(int $id): void
    {
        $this->geoRules = array_values(array_filter($this->geoRules, fn($r) => $r['id'] !== $id));
        $this->saveGeoRules();
    }

    public function render()
    {
        $canPhones = $this->canEntryType('phone');
        $canMessengers = $this->canEntryType('messenger');
        $canPrices = $this->canEntryType('price');
        $canAddresses = $this->canEntryType('address');
        $canSocials = $this->canEntryType('social');
        $canCustom = $this->canEntryType('custom');

        $allPhones = $canPhones
            ? $this->site->contactEntries()->where('type', 'phone')->where('visible', true)->orderBy('order')->with('backups')->get()
            : collect();
        $allMsgs = $canMessengers
            ? $this->site->contactEntries()->where('type', 'messenger')->where('visible', true)->orderBy('order')->with('backups')->get()
            : collect();

        // Overview: what each geo group sees + "all" universal column
        $overviewByGeo = [];
        // "Всі" — universal phones (geo_mode=all)
        $activePhones = $allPhones
            ->filter(fn($e) => is_null($e->parent_id) && $e->role === 'primary')
            ->values();
        $activeMsgs = $allMsgs
            ->filter(fn($e) => is_null($e->parent_id) && $e->role === 'primary')
            ->values();
        $overviewByGeo['all'] = [
            'label'        => 'Всі',
            'phones'       => $activePhones->filter(fn($e) => $e->visibleForGeo('world'))->values(),
            'messengers'   => $activeMsgs->filter(fn($e) => $e->visibleForGeo('world'))->values(),
        ];
        $overviewByGeo['all']['label'] = "\u{0412}\u{0441}\u{0456}";
        // One column per geoTab — tag-based
        foreach ($this->geoTabs as $geoCode) {
            $fp = $this->filterByGeoTag($activePhones, $geoCode);
            $fm = $this->filterByGeoTag($activeMsgs,   $geoCode);
            $overviewByGeo[$geoCode] = [
                'label'        => $geoCode,
                'phones'       => $fp->values(),
                'messengers'   => $fm->values(),
            ];
        }

        // Geo visibility matrix — tag-based (geo_mode=only + country tag)
        $primaryPhones = $activePhones;
        $geoMatrix = $primaryPhones->map(function ($phone) {
            $vis = [];
            foreach ($this->geoTabs as $gc) {
                $vis[$gc] = $phone->visibleForGeo($gc);
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
                // Global numbers (geo_mode all/except) are intentionally world-visible — never a conflict.
                // Only numbers explicitly restricted to specific countries ("Тільки") can clash with isolation rules.
                if ($phone->geo_mode !== 'only') {
                    continue;
                }
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
        $allPhonesAll = $canPhones
            ? $this->site->contactEntries()->where('type', 'phone')->orderBy('order')->with('backups')->get()
            : collect();
        $allMsgsAll = $canMessengers
            ? $this->site->contactEntries()->where('type', 'messenger')->orderBy('order')->with('backups')->get()
            : collect();
        $allPricesAll = $canPrices
            ? $this->site->contactEntries()->where('type', 'price')->orderBy('order')->get()
            : collect();

        $phonePrimariesByGeo = [];
        $hiddenPhonesByGeo = [];
        $msgPrimariesByGeo   = [];
        $hiddenMsgsByGeo   = [];
        foreach (array_merge(['all'], $this->geoTabs) as $gk) {
            $fp = $this->filterByPreviewTab($allPhones, $gk);
            $phonePrimariesByGeo[$gk] = $fp->filter(fn($e) => is_null($e->parent_id))->values();
            $fh = $this->filterByPreviewTab($allPhonesAll, $gk);
            $hiddenPhonesByGeo[$gk] = $fh->filter(fn($e) => !$e->visible && is_null($e->parent_id))->values();
            $fm = $this->filterByPreviewTab($allMsgs, $gk);
            $msgPrimariesByGeo[$gk] = $fm->filter(fn($e) => is_null($e->parent_id))->values();
            $fmh = $this->filterByPreviewTab($allMsgsAll, $gk);
            $hiddenMsgsByGeo[$gk] = $fmh->filter(fn($e) => !$e->visible && is_null($e->parent_id))->values();
        }
        // Settings tab still uses $phonePrimaries (all geo, visible primaries)
        $phonePrimaries = $allPhonesAll
            ->filter(fn($e) => $e->visible && is_null($e->parent_id) && $e->role === 'primary')
            ->values();
        $msgPrimaries   = $allMsgs->filter(fn($e) => is_null($e->parent_id))->values();

        // Socials + addresses — flat (no reserves), sliced by the same preview geo tabs.
        $allSocialsAll = $canSocials
            ? $this->site->contactEntries()->where('type', 'social')->orderBy('order')->get()
            : collect();
        $allAddressesAll = $canAddresses
            ? $this->site->contactEntries()->where('type', 'address')->orderBy('order')->get()
            : collect();
        $socialPrimariesByGeo = [];
        $hiddenSocialsByGeo   = [];
        $addressPrimariesByGeo = [];
        $hiddenAddressesByGeo  = [];
        $socialKindCountsByGeo = [];
        foreach (array_merge(['all'], $this->geoTabs) as $gk) {
            $fs = $this->filterByPreviewTab($allSocialsAll, $gk);
            $socialPrimariesByGeo[$gk]  = $fs->filter(fn($e) => $e->visible)->values();
            $hiddenSocialsByGeo[$gk]    = $fs->filter(fn($e) => !$e->visible)->values();
            $socialKindCountsByGeo[$gk] = $fs->filter(fn($e) => $e->visible)->groupBy('kind')->map(fn($g) => $g->count())->all();
            $fa = $this->filterByPreviewTab($allAddressesAll, $gk);
            $addressPrimariesByGeo[$gk] = $fa->filter(fn($e) => $e->visible)->values();
            $hiddenAddressesByGeo[$gk]  = $fa->filter(fn($e) => !$e->visible)->values();
        }
        $socialKinds = $allSocialsAll->pluck('kind')->filter()->unique()->values()->all();

        // Prices
        $allPrices = $canPrices
            ? $this->site->contactEntries()->where('type', 'price')->where('visible', true)->get()
            : collect();
        $priceBySku = $allPrices->groupBy('sku');
        $allCustomAll = $canCustom
            ? $this->site->contactEntries()->where('type', 'custom')->orderBy('order')->get()
            : collect();
        $overviewExtrasByGeo = [];
        foreach (array_merge(['all'], $this->geoTabs) as $gk) {
            $overviewExtrasByGeo[$gk] = [
                'label' => $gk === 'all' ? "\u{0412}\u{0441}\u{0456}" : $gk,
                'addresses' => $this->filterByPreviewTab($allAddressesAll, $gk)->filter(fn($e) => $e->visible)->values(),
                'prices' => $this->filterByPreviewTab($allPricesAll, $gk)->filter(fn($e) => $e->visible)->values(),
                'socials' => $this->filterByPreviewTab($allSocialsAll, $gk)->filter(fn($e) => $e->visible)->values(),
                'custom' => $this->filterByPreviewTab($allCustomAll, $gk)->filter(fn($e) => $e->visible)->values(),
            ];
        }

        // Activity
        // Per-site feed from the unified read-model: owen-it diffs + activity_log,
        // semantic codes + real old/new (PM-T07).
        $activityLogs = \App\Services\AuditFeed::collect([
            'site_id' => $this->site->id,
            'allowed_entry_types' => auth()->user()?->readableEntryTypes() ?? [],
        ])->take(40);

        $failoverLogs = ActivityLog::where('subject_type', Site::class)
            ->where('subject_id', $this->site->id)
            ->where('action', 'like', '%failover%')
            ->with('user')
            ->latest()
            ->take(20)
            ->get();
        $siteGroups = SiteGroup::orderBy('name')->get();

        // All entries including hidden (for full Data tab list)
        // Extra categories
        $addressCount = $allAddressesAll->count();
        $socialCount  = $allSocialsAll->count();
        $customCount  = $allCustomAll->count();

        // Messenger kinds grouped (for platform pills)
        $msgKindCountsByGeo = [];
        foreach (array_merge(['all'], $this->geoTabs) as $gk) {
            $msgKindCountsByGeo[$gk] = $this->filterByPreviewTab($allMsgsAll, $gk)
                ->groupBy('kind')
                ->map(fn($entries) => $entries->count())
                ->all();
        }
        $availableMessengerKinds = collect(\App\Models\ContactEntry::MSG_KINDS)
            ->reject(fn($_meta, $kind) => in_array($kind, $this->messengerKinds, true))
            ->all();

        // Prices grouped by SKU (all including hidden)
        $priceBySkuAll = $allPricesAll->groupBy('sku');

        // Counts
        $phoneCount = $allPhones->count();
        $msgCount   = $allMsgs->count();
        $priceCount = $allPrices->count();

        $geoTabs = $this->geoTabs;
        $messengerKinds = $this->messengerKinds;
        $visibleDataCategories = $this->visibleDataCategories();
        $initialDataCat = $visibleDataCategories[0] ?? '';
        $dataCount = $phoneCount + $msgCount + $priceCount + $addressCount + $socialCount + $customCount;

        $geoRules = $this->geoRules;
        $newRuleA = $this->newRuleA;
        $newRuleB = $this->newRuleB;

        return view('livewire.sites.show', compact(
            'overviewByGeo', 'overviewExtrasByGeo', 'geoMatrix', 'conflicts', 'conflictPhoneIds',
            'allPhones', 'allMsgs', 'allPhonesAll', 'allMsgsAll', 'allPricesAll',
            'phonePrimaries', 'msgPrimaries',
            'phonePrimariesByGeo', 'hiddenPhonesByGeo', 'msgPrimariesByGeo', 'hiddenMsgsByGeo',
            'msgKindCountsByGeo', 'messengerKinds', 'availableMessengerKinds',
            'priceBySku', 'priceBySkuAll',
            'activityLogs',
            'failoverLogs',
            'siteGroups',
            'phoneCount', 'msgCount', 'priceCount', 'dataCount', 'initialDataCat',
            'addressCount', 'socialCount',
            'allSocialsAll', 'allAddressesAll', 'allCustomAll',
            'socialPrimariesByGeo', 'hiddenSocialsByGeo', 'addressPrimariesByGeo', 'hiddenAddressesByGeo',
            'socialKindCountsByGeo', 'socialKinds',
            'customCount',
            'geoTabs', 'visibleDataCategories', 'geoRules', 'newRuleA', 'newRuleB',
        ));
    }
}
