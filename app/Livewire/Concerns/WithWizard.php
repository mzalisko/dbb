<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * v4 full-screen step wizard: Тип → Значення → Сайти → Дія → Підтвердити.
 *
 * Pure orchestration over the existing, audited bulk actions (applyEdit,
 * applyReplace, applyGeo, applyRole, applyMove, applyDuplicate, bulkDelete):
 * it only sets the matching state + drives the selection, then calls the
 * proven apply* method. No new write logic — logging and undo are unchanged.
 */
trait WithWizard
{
    /** Page mode: 'wizard' (guided, default) | 'browse' (the classic list). */
    #[Url]
    public string $mode = 'wizard';

    /** Current step, 1..5. */
    public int $wizStep = 1;

    /** The action chosen on step 4 — key of wizActionLabels(). */
    public string $wizAction = '';

    /** Human labels for the action picker, step rail and summaries. */
    public function wizActionLabels(): array
    {
        return [
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
        $this->wizAction = '';
        $this->clearSelected();
    }

    /** Jump via the progress rail — never ahead of the prerequisites. */
    public function wizGoto(int $step): void
    {
        $step = max(1, min(5, $step));
        if ($step >= 3 && $this->pickedValue === '') {
            $step = 2;                 // need a value before choosing where
        }
        if ($step >= 4 && ! $this->hasSelection()) {
            $step = min($step, 3);     // need a selection before choosing an action
        }
        $this->wizStep = $step;
    }

    public function wizBack(): void
    {
        $this->wizStep = max(1, $this->wizStep - 1);
    }

    public function wizNext(): void
    {
        switch ($this->wizStep) {
            case 2:
                if ($this->pickedValue === '') {
                    $this->dispatch('toast', type: 'error', message: 'Оберіть значення зі списку');

                    return;
                }
                break;
            case 3:
                if (! $this->hasSelection()) {
                    $this->dispatch('toast', type: 'error', message: 'Позначте хоча б одне входження');

                    return;
                }
                break;
            case 4:
                if ($this->wizAction === '') {
                    $this->dispatch('toast', type: 'error', message: 'Оберіть дію');

                    return;
                }
                break;
        }
        $this->wizStep = min(5, $this->wizStep + 1);
    }

    /** Pick the action on step 4 and seed its detail inputs with defaults. */
    public function setWizAction(string $action): void
    {
        if (! array_key_exists($action, $this->wizActionLabels())) {
            return;
        }
        $this->wizAction = $action;

        switch ($action) {
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

    /** Final step — run the chosen action over the selection via its apply* method. */
    public function wizConfirm(): void
    {
        if ($this->wizAction === '') {
            $this->dispatch('toast', type: 'error', message: 'Оберіть дію');

            return;
        }
        if (! $this->hasSelection()) {
            $this->dispatch('toast', type: 'error', message: 'Нічого не обрано');
            $this->wizStep = 3;

            return;
        }

        switch ($this->wizAction) {
            case 'replace':   $this->editField = 'value'; $this->applyEdit(); break;
            case 'label':     $this->editField = 'label'; $this->applyEdit(); break;
            case 'substr':    $this->applyReplace(); break;
            case 'geo':       $this->applyGeo(); break;
            case 'state':     $this->applyRole(); break;
            case 'move':      $this->applyMove(); break;
            case 'duplicate': $this->applyDuplicate(); break;
            case 'delete':    $this->bulkDelete(); break;
        }

        // apply* clears the selection on success; if it's still there the action
        // bounced on validation — keep the user on the details step to fix it.
        if ($this->hasSelection()) {
            $this->wizStep = 4;
        } else {
            $this->wizStep = 1;
            $this->wizAction = '';
        }
    }
}
