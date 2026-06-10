<?php

namespace App\Livewire\Concerns;

use App\Models\ContactEntry;
use App\Services\ActivityLogService;
use App\Services\BulkActionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * v4 full-screen step wizard. After Тип, the manager picks an INTENT and the
 * flow branches:
 *   edit    : Тип → Намір → Значення → Сайти → Дія → Підтвердити
 *   create  : Тип → Намір → Дані → Сайти → Підтвердити
 *   reserve : Тип → Намір → Основний → Сайти → Резерви → Підтвердити
 *
 * Pure orchestration over the existing, audited actions (applyEdit / applyReplace
 * / applyGeo / applyRole / applyMove / applyDuplicate / bulkDelete / applyCreate)
 * plus one small new bulk helper for "add a reserve to N primaries at once".
 * No new edit logic — logging and undo are unchanged.
 */
trait WithWizard
{
    /** Page mode: 'wizard' (guided, default) | 'browse' (the classic list). */
    #[Url]
    public string $mode = 'wizard';

    /** Current step (1-based index into wizSteps()). */
    public int $wizStep = 1;

    /** Branch: edit existing | create new | attach reserve to primaries. */
    public string $wizIntent = 'edit';

    /** The edit action chosen on the 'action' step. */
    public string $wizAction = '';

    /** Multi-value filter (edit intent) — operate on several values at once. */
    public array $wizValues = [];

    /** Sites touched during the wizard — pushed to the plugin only on "Готово". */
    public array $wizPendingSites = [];

    /** Multi-action change-set — fields to change at once: value|label|state|geo. */
    public array $chg = [];
    public string $chgLabel = '';   // new label (value→editValue, state→roleValue, geo→geoMode/geoCountries)

    /** Step labels per intent (drives the progress rail). */
    public function wizSteps(): array
    {
        return match ($this->wizIntent) {
            'create'  => ['Тип', 'Намір', 'Дані', 'Сайти', 'Підтвердити'],
            'reserve' => ['Тип', 'Намір', 'Основний', 'Сайти', 'Резерви', 'Підтвердити', 'Порядок'],
            default   => ['Тип', 'Намір', 'Значення', 'Сайти', 'Дія', 'Підтвердити'],
        };
    }

    /** Stable per-step keys (drives guards + which panel renders). */
    public function wizStepKeys(): array
    {
        return match ($this->wizIntent) {
            'create'  => ['type', 'intent', 'data', 'csites', 'confirm'],
            'reserve' => ['type', 'intent', 'value', 'sites', 'resnums', 'confirm', 'order'],
            default   => ['type', 'intent', 'value', 'sites', 'action', 'confirm'],
        };
    }

    public function wizKey(): string
    {
        return $this->wizStepKeys()[$this->wizStep - 1] ?? 'type';
    }

    /**
     * Whether the value step is multi-select. Multi works on text values; prices
     * group by amount+currency (and reserves attach to one primary), so those
     * stay single-select.
     */
    public function wizMultiValue(): bool
    {
        return $this->wizIntent === 'edit' && $this->typeFilter !== 'price';
    }

    public function wizIndexOf(string $key): int
    {
        $i = array_search($key, $this->wizStepKeys(), true);

        return $i === false ? 1 : $i + 1;
    }

    public function wizActionLabels(): array
    {
        return [
            'changes'   => 'Кілька змін одразу',
            'replace'   => 'Замінити значення',
            'substr'    => 'Замінити підрядок',
            'label'     => 'Змінити мітку',
            'geo'       => 'Гео-видимість',
            'state'     => 'Стан (активний / прихований)',
            'move'      => 'Перемістити на сайт',
            'duplicate' => 'Дублювати на сайти',
            'delete'    => 'Видалити',
        ];
    }

    public function wizIntentLabels(): array
    {
        return [
            'edit'    => 'Змінити наявні',
            'create'  => 'Додати нові',
            'reserve' => 'Приєднати резерв до наявних',
        ];
    }

    public function toBrowse(): void
    {
        $this->mode = 'browse';
    }

    public function toWizard(): void
    {
        $this->trashed = false;
        $this->mode = 'wizard';
        $this->wizReset();
    }

    public function wizReset(): void
    {
        $this->wizStep = 1;
        $this->wizIntent = 'edit';
        $this->wizAction = '';
        $this->roleFilter = '';
        $this->wizValues = [];
        $this->pickedValue = '';
        $this->pickedCurrency = '';
        $this->reserveNumbers = '';
        $this->clearSelected();
    }

    /** Toggle a value in the multi-value filter (edit intent — act on several at once). */
    public function toggleWizValue(string $value): void
    {
        $this->pickedValue = '';
        $this->pickedCurrency = '';
        $this->wizValues = in_array($value, $this->wizValues, true)
            ? array_values(array_diff($this->wizValues, [$value]))
            : array_merge($this->wizValues, [$value]);
        $this->resetPage();
        $this->clearSelected();
    }

    /** Push everything the wizard touched to the sites/plugin — runs on "Готово". */
    public function wizFlushSync(): void
    {
        if (! empty($this->wizPendingSites)) {
            $this->pushSites($this->wizPendingSites);
            $this->wizPendingSites = [];
        }
    }

    /** Finish: push the staged changes to the sites/plugin, then start fresh. */
    public function wizFinish(): void
    {
        $this->wizFlushSync();
        $this->wizReset();
    }

    /** Abort the whole wizard: drop the staged push (nothing more is sent) + reset. */
    public function wizCancel(): void
    {
        $this->wizPendingSites = [];
        $this->wizReset();
        $this->dispatch('toast', type: 'info', message: 'Скасовано — на сайти нічого не відправлено');
    }

    /** Pick the intent on step 2 and seed the right defaults. */
    public function setWizIntent(string $intent): void
    {
        if (! array_key_exists($intent, $this->wizIntentLabels())) {
            return;
        }
        $this->wizIntent = $intent;
        $this->wizAction = '';
        $this->pickedValue = '';
        $this->pickedCurrency = '';
        $this->wizValues = [];
        $this->clearSelected();

        // Attaching reserves only makes sense for primaries — focus the value
        // list + occurrence list on them.
        $this->roleFilter = $intent === 'reserve' ? 'primary' : '';

        if ($intent === 'create') {
            $this->createValue = '';
            $this->createLabel = '';
            $this->createRole = 'primary';
            $this->createSites = [];
        }
        if ($intent === 'reserve') {
            $this->reserveNumbers = '';
        }
    }

    /** Jump via the rail — backwards only, so prerequisites can't be skipped. */
    public function wizGoto(int $step): void
    {
        $step = max(1, min(count($this->wizSteps()), $step));
        if ($step <= $this->wizStep) {
            $this->wizStep = $step;
        }
    }

    public function wizBack(): void
    {
        $this->wizStep = max(1, $this->wizStep - 1);
    }

    public function wizNext(): void
    {
        switch ($this->wizKey()) {
            case 'value':
                if ($this->wizMultiValue() ? empty($this->wizValues) : $this->pickedValue === '') {
                    $this->wizErr('Оберіть хоча б одне значення зі списку'); return;
                }
                break;
            case 'sites':
                if (! $this->hasSelection()) {
                    $this->wizErr('Позначте хоча б одне входження'); return;
                }
                break;
            case 'data':
                if (trim($this->createValue) === '') {
                    $this->wizErr('Введіть значення'); return;
                }
                break;
            case 'csites':
                if (empty($this->createSites)) {
                    $this->wizErr('Оберіть хоча б один сайт'); return;
                }
                break;
            case 'resnums':
                if (trim($this->reserveNumbers) === '') {
                    $this->wizErr('Введіть хоча б один резервний номер'); return;
                }
                break;
            case 'action':
                if ($this->wizAction === '') {
                    $this->wizErr('Оберіть дію'); return;
                }
                break;
        }
        $this->wizStep = min(count($this->wizSteps()), $this->wizStep + 1);

        // Reserve: default to all sites where this number is primary — the common
        // "add to every site" case; the manager deselects to narrow it.
        if ($this->wizKey() === 'sites' && $this->wizIntent === 'reserve' && ! $this->hasSelection()) {
            $this->selectAllFiltered();
        }
    }

    /** Pick the edit action on the 'action' step and seed its detail inputs. */
    public function setWizAction(string $action): void
    {
        if (! array_key_exists($action, $this->wizActionLabels())) {
            return;
        }
        $this->wizAction = $action;

        switch ($action) {
            case 'changes':
                $this->chg = [];
                $this->editValue = '';
                $this->chgLabel = '';
                $this->geoMode = 'all';
                $this->geoCountries = '';
                $this->roleValue = 'primary';
                break;
            case 'replace':
                $this->editField = 'value';
                $this->editValue = '';
                break;
            case 'label':
                $this->editField = 'label';
                $this->editValue = '';
                break;
            case 'substr':
                $this->findText = $this->pickedValue;
                $this->replaceText = '';
                break;
            case 'geo':
                $this->geoMode = 'all';
                $this->geoCountries = '';
                break;
            case 'state':
                $this->roleValue = 'hidden';
                break;
            case 'move':
                $this->moveSite = '';
                break;
            case 'duplicate':
                $this->dupSites = [];
                break;
        }
    }

    /** Final step — run the chosen flow via its proven apply* method. */
    public function wizConfirm(): void
    {
        if ($this->wizIntent === 'create') {
            if (trim($this->createValue) === '') {
                $this->wizErr('Введіть значення'); return;
            }
            if (empty($this->createSites)) {
                $this->wizErr('Оберіть хоча б один сайт'); return;
            }
            if (! $this->valueValidForType($this->typeFilter, trim($this->createValue))) {
                $this->wizStep = $this->wizIndexOf('data');
                $this->wizErr('Телефон має містити лише цифри (без тексту)'); return;
            }
            $this->createKind = $this->kindFilter;
            $this->applyCreate();
            $this->wizFinish();

            return;
        }

        if ($this->wizIntent === 'reserve') {
            if (! $this->hasSelection()) {
                $this->wizStep = $this->wizIndexOf('sites');

                $this->wizErr('Оберіть основні номери'); return;
            }
            if (trim($this->reserveNumbers) === '') {
                $this->wizErr('Введіть хоча б один резервний номер'); return;
            }
            $this->applyWizardReserve();
            // On success the selection is cleared → land on the "order" step so the
            // manager sees each primary with its reserves and can set the queue.
            if ($this->hasSelection()) {
                $this->wizStep = $this->wizIndexOf('resnums');
            } else {
                $this->reserveNumbers = '';
                $this->wizStep = $this->wizIndexOf('order');
            }

            return;
        }

        // edit
        if ($this->wizAction === '') {
            $this->wizErr('Оберіть дію'); return;
        }
        if (! $this->hasSelection()) {
            $this->wizStep = $this->wizIndexOf('sites');

            $this->wizErr('Нічого не обрано'); return;
        }

        switch ($this->wizAction) {
            case 'changes':   $this->applyChanges(); break;
            case 'replace':   $this->editField = 'value'; $this->applyEdit(); break;
            case 'label':     $this->editField = 'label'; $this->applyEdit(); break;
            case 'substr':    $this->applyReplace(); break;
            case 'geo':       $this->applyGeo(); break;
            case 'state':     $this->applyRole(); break;
            case 'move':      $this->applyMove(); break;
            case 'duplicate': $this->applyDuplicate(); break;
            case 'delete':    $this->bulkDelete(); break;
        }

        // apply* clears the selection on success; if it survived the action
        // bounced on validation — keep the user on the action step to fix it.
        if ($this->hasSelection()) {
            $this->wizStep = $this->wizIndexOf('action');
        } else {
            $this->wizFinish();
        }
    }

    /** Toggle a field in the multi-action change-set (value|label|state|geo). */
    public function toggleChg(string $field): void
    {
        if (! in_array($field, ['value', 'label', 'state', 'geo'], true)) {
            return;
        }
        $this->chg = in_array($field, $this->chg, true)
            ? array_values(array_diff($this->chg, [$field]))
            : array_merge($this->chg, [$field]);
    }

    /**
     * Multi-action: apply SEVERAL field changes (value + label + state + geo) to
     * the whole selection in one pass — one audit, one undo, one push. State
     * keeps the three-way model (active / hidden→whole set / down→reserve).
     */
    public function applyChanges(): void
    {
        if (empty($this->chg)) {
            $this->wizErr('Позначте хоча б одну зміну'); return;
        }

        $nv = trim($this->editValue);
        if (in_array('value', $this->chg, true)) {
            if ($nv === '') {
                $this->wizErr('Введіть нове значення'); return;
            }
            if (! $this->valueValidForType($this->typeFilter, $nv)) {
                $this->wizErr('Телефон має містити лише цифри (без тексту)'); return;
            }
            $nv = $this->normalizeValueForType($this->typeFilter, $nv);
        }
        $geoCountries = [];
        if (in_array('geo', $this->chg, true)) {
            if (! in_array($this->geoMode, ['all', 'only', 'except'], true)) {
                $this->wizErr('Оберіть гео-режим'); return;
            }
            $geoCountries = $this->geoMode === 'all' ? [] : $this->parsedCountries();
            if ($this->geoMode !== 'all' && empty($geoCountries)) {
                $this->wizErr('Вкажіть хоча б одну країну'); return;
            }
        }
        if (in_array('state', $this->chg, true) && ! in_array($this->roleValue, ['primary', 'hidden', 'down'], true)) {
            $this->wizErr('Оберіть стан'); return;
        }

        $chg = $this->chg;
        $nl = trim($this->chgLabel);
        $gMode = $this->geoMode;
        $gTag = ($gMode === 'only' && count($geoCountries) === 1) ? $geoCountries[0] : null;
        $rv = $this->roleValue;

        $snapshot = [];
        $capture = function (ContactEntry $e) use (&$snapshot) {
            $snapshot[$e->id] ??= [
                'value'         => $e->value,
                'label'         => $e->label,
                'role'          => $e->role,
                'visible'       => $e->visible,
                'parent_id'     => $e->parent_id,
                'geo_tag'       => $e->geo_tag,
                'geo_mode'      => $e->geo_mode,
                'countries'     => $e->countries,
                'failover_down' => $e->failover_down,
            ];
        };

        $result = BulkActionService::apply(
            ContactEntry::class,
            $this->bulkTargetIds(),
            'update',
            function (ContactEntry $e) use (&$snapshot, $chg, $nv, $nl, $gMode, $geoCountries, $gTag, $rv, $capture) {
                $capture($e);
                $data = [];
                if (in_array('value', $chg, true)) {
                    $data['value'] = $nv;
                }
                if (in_array('label', $chg, true)) {
                    $data['label'] = $nl !== '' ? $nl : null;
                }
                if (in_array('geo', $chg, true)) {
                    $data['geo_mode'] = $gMode;
                    $data['countries'] = $geoCountries ?: null;
                    $data['geo_tag'] = $gTag;
                }
                if (in_array('state', $chg, true)) {
                    if ($rv === 'down') {
                        $e->forceFill(['failover_down' => true]);
                    } elseif ($rv === 'hidden') {
                        $data['role'] = 'hidden';
                        $data['visible'] = false;
                    } else { // active
                        $data['role'] = 'primary';
                        $data['visible'] = true;
                        $e->forceFill(['failover_down' => false]);
                        if ($e->parent_id && ! in_array('geo', $chg, true)) {
                            $parent = $e->parent;
                            $data['parent_id'] = null;
                            $data['geo_tag'] = $parent?->geo_tag;
                            $data['geo_mode'] = $parent?->geo_mode ?? 'all';
                            $data['countries'] = $parent?->countries;
                        } elseif ($e->parent_id) {
                            $data['parent_id'] = null;
                        }
                    }
                }
                $e->update($data);

                // State cascade: hiding/activating a primary carries its reserves.
                if (in_array('state', $chg, true) && is_null($e->parent_id) && in_array($rv, ['hidden', 'primary'], true)) {
                    foreach ($e->backups as $b) {
                        $capture($b);
                        $b->update(['visible' => $rv !== 'hidden']);
                    }
                }
            },
            auditAction: 'entry.bulk.updated',
            syncTouchedSites: true,
        );

        if ($result['done'] === 0) {
            $this->wizErr('Немає прав на редагування обраних'); return;
        }

        $this->clearSelected();
        $this->dispatch('toast',
            type: 'success',
            message: $this->withSkipped('Змінено: '.$result['done'], $result['skipped']),
            action: 'bulkRestoreRole',
            actionLabel: 'Відмінити',
            actionData: ['snapshot' => $snapshot],
        );
    }

    /**
     * Add brand-new reserve numbers to EVERY selected primary at once — the
     * "attach a reserve to these primaries across N sites" case. Each primary
     * gets its own copies (right site, type, kind, queue order, geo inherited).
     */
    public function applyWizardReserve(): void
    {
        $values = collect(preg_split('/[\r\n]+/', trim($this->reserveNumbers)) ?: [])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values();

        if ($values->isEmpty()) {
            $this->wizErr('Введіть хоча б один резервний номер'); return;
        }
        foreach ($values as $v) {
            if (! $this->valueValidForType($this->typeFilter, (string) $v)) {
                $this->wizErr("«{$v}» — телефон має містити лише цифри (без тексту)"); return;
            }
        }

        $user = Auth::user();
        $created = [];
        $touched = [];

        ContactEntry::disableAuditing();
        try {
            $this->selectedSourceQuery()->chunkById(200, function ($rows) use ($values, $user, &$created, &$touched) {
                foreach ($rows as $primary) {
                    // Reserves attach to primaries only; skip reserves in the selection.
                    if (! is_null($primary->parent_id) || ! $user || ! $user->can('update', $primary)) {
                        continue;
                    }
                    $order = (int) ContactEntry::where('parent_id', $primary->id)
                        ->where('site_id', $primary->site_id)->max('order');
                    foreach ($values as $v) {
                        $order++;
                        $created[] = ContactEntry::create([
                            'site_id'   => $primary->site_id,
                            'type'      => $primary->type,
                            'kind'      => $primary->kind,
                            'value'     => $v,
                            'role'      => 'backup',
                            'parent_id' => $primary->id,
                            'geo_mode'  => 'all',
                            'visible'   => true,
                            'order'     => $order,
                        ])->id;
                    }
                    $touched[] = (int) $primary->site_id;
                }
            });
        } finally {
            ContactEntry::enableAuditing();
        }

        if (empty($created)) {
            $this->wizErr('Нічого не додано — оберіть основні номери'); return;
        }

        ActivityLogService::log('entry.bulk.attached', null, [
            'done' => count($created), 'created' => true,
        ], context: 'bulk');
        $this->syncSites(array_values(array_unique($touched)));
        $this->clearSelected();

        $this->dispatch('toast',
            type: 'success',
            message: 'Додано резервів: '.count($created),
            action: 'bulkPurgeCreated',
            actionLabel: 'Відмінити',
            actionData: ['ids' => $created],
        );
    }

    private function wizErr(string $message): void
    {
        $this->dispatch('toast', type: 'error', message: $message);
    }
}
