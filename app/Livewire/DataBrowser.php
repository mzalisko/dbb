<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithBulkSelection;
use App\Models\ContactEntry;
use App\Models\Site;
use App\Services\BulkActionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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
    use WithPagination;

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

    /** Trash mode — browse & manage soft-deleted entries (restore / purge). */
    #[Url]
    public bool $trashed = false;

    public function mount(): void
    {
        // Normalise stale/empty URLs (e.g. the old "all types" value) against the registry.
        if (! array_key_exists($this->typeFilter, ContactEntry::TYPES)) {
            $this->typeFilter = array_key_first(ContactEntry::TYPES);
        }
        // Types with kinds always have exactly one selected (no "all kinds"
        // option) — default to the first available kind.
        $kinds = $this->kindLabelsForType($this->typeFilter);
        if (! $kinds) {
            $this->kindFilter = '';
        } elseif (! array_key_exists($this->kindFilter, $kinds)) {
            $this->kindFilter = (string) array_key_first($kinds);
        }
    }

    /** Bulk "edit field" drawer state. */
    public bool $editingField = false;
    public string $editField = 'value';   // value | label
    public string $editValue = '';

    /** Selection review drawer (see / trim the cross-site set). */
    public bool $reviewingSelection = false;

    /** Find & replace (substring) drawer. */
    public bool $editingReplace = false;
    public string $findText = '';
    public string $replaceText = '';

    /** Bulk geo drawer. */
    public bool $editingGeo = false;
    public string $geoMode = 'all';        // all | only | except
    public string $geoCountries = '';      // free text: "UA, PL, DE"

    /** Bulk role/state drawer — primary | hidden (backup needs a parent). */
    public bool $editingRole = false;
    public string $roleValue = 'primary';

    /** Bulk price-fields drawer (only for the price type). */
    public bool $editingPrice = false;
    public string $priceField = 'currency'; // currency | price_unit | price | old_price
    public string $priceValue = '';

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
        $this->resetPage();
        $this->clearSelected();
    }

    public function updatingKindFilter(): void
    {
        // A kind is its own entity (a Viber link is not a Telegram link), so
        // switching it drops the selection too — you re-pick within the new kind.
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

        if (in_array($user?->role, ['owner', 'admin'], true)) {
            return $query;
        }

        return $query->whereHas('site.client', fn ($q) => $q->where('user_id', $user?->id));
    }

    /** Filtered, un-paginated base query — shared by render() and bulk selection. */
    protected function bulkQuery(): Builder
    {
        return $this->applyVisibility(ContactEntry::query())
            ->when($this->trashed, fn ($q) => $q->onlyTrashed())
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->kindFilter, fn ($q) => $q->where('kind', $this->kindFilter))
            ->when($this->roleFilter, fn ($q) => $q->where('role', $this->roleFilter))
            ->when($this->siteFilter, fn ($q) => $q->where('site_id', $this->siteFilter))
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('value', 'like', "%{$this->search}%")
                ->orWhere('label', 'like', "%{$this->search}%")
            ));
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

    /** @return array<string,string> */
    private function kindLabelsForType(string $type): array
    {
        $labels = ContactEntry::kindLabels($type);

        $dynamicKinds = $this->applyVisibility(ContactEntry::query())
            ->when($this->trashed, fn ($q) => $q->onlyTrashed())
            ->where('type', $type)
            ->whereNotNull('kind')
            ->distinct()
            ->pluck('kind')
            ->map(fn ($kind) => trim((string) $kind))
            ->filter()
            ->unique()
            ->values();

        foreach ($dynamicKinds as $kind) {
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
        $this->editingField = true;
    }

    public function closeEdit(): void
    {
        $this->editingField = false;
        $this->editField = 'value';
        $this->editValue = '';
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
        if (! in_array($field, ['value', 'label', 'currency', 'price', 'old_price', 'price_unit'], true) || empty($snapshot)) {
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
        $this->editingPrice = true;
    }

    public function closePriceEdit(): void
    {
        $this->editingPrice = false;
        $this->priceField = 'currency';
        $this->priceValue = '';
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
        $this->editingReplace = true;
    }

    public function closeReplace(): void
    {
        $this->editingReplace = false;
        $this->findText = '';
        $this->replaceText = '';
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

    // ─── Bulk: visibility (explicit show / hide) ──────────────────────────

    public function bulkSetVisible(bool $visible): void
    {
        $result = BulkActionService::apply(
            ContactEntry::class,
            $this->bulkTargetIds(),
            'update',
            fn (ContactEntry $e) => $e->update(['visible' => $visible]),
        );

        $this->clearSelected();
        $verb = $visible ? 'Показано' : 'Сховано';
        $this->dispatch('toast', type: 'success', message: $this->withSkipped("{$verb}: {$result['done']}", $result['skipped']));
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
        );

        $this->clearSelected();
        $this->dispatch('toast', type: 'success', message: $this->withSkipped("Видалено назавжди: {$result['done']}", $result['skipped']));
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

    public function render()
    {
        $entries = $this->bulkQuery()
            ->with('site')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

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
            'sites' => Site::orderBy('name')->get(['id', 'name']),
            'selectionPreview' => $selectionPreview,
            'reviewItems' => $reviewItems,
            'types' => ContactEntry::typeLabels(),
            'kinds' => $this->kindLabelsForType($this->typeFilter),
            'selectionEntities' => $this->selectionEntityLabels(),
        ]);
    }
}
