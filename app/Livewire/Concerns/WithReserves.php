<?php

namespace App\Livewire\Concerns;

use App\Models\ContactEntry;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

/**
 * "Add brand-new reserve numbers to a primary" flow for the data browser — type
 * one number per line and they become reserves of the chosen primary (same site,
 * type, kind). Authorised + audited + plugin-synced. Extracted from DataBrowser
 * to keep that component focused.
 *
 * Host must provide: applyVisibility(), selectedQuery(), syncSites(), and the
 * WithBulkSelection state (selected, selectAllMatching, clearSelected()).
 */
trait WithReserves
{
    /** Add brand-new reserve numbers (typed in) to a chosen primary. */
    public bool $addingReserve = false;
    public ?int $reserveParentId = null;
    public string $reserveParentValue = '';
    public string $reserveNumbers = '';

    /** Bottom-bar entry: add new reserves to the one selected primary. */
    public function openAddReserve(): void
    {
        if ($this->selectAllMatching || count($this->selected) !== 1) {
            $this->dispatch('toast', type: 'error', message: 'Оберіть один основний запис');

            return;
        }

        $only = $this->selectedQuery()->first();
        if (! $only || ! is_null($only->parent_id) || $only->role === 'backup') {
            $this->dispatch('toast', type: 'error', message: 'Додавання резервів — лише до основного запису');

            return;
        }

        $this->openAddReserveFor((int) $only->id);
    }

    /** Open the "type new reserves" drawer for a specific primary (failover panel / single-select). */
    public function openAddReserveFor(int $primaryId): void
    {
        $primary = $this->applyVisibility(ContactEntry::query())->find($primaryId);
        if (! $primary || ! is_null($primary->parent_id)) {
            $this->dispatch('toast', type: 'error', message: 'Оберіть основний запис');

            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->can('update', $primary)) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на цей запис');

            return;
        }

        $this->reserveParentId = $primaryId;
        $this->reserveParentValue = (string) $primary->value;
        $this->reserveNumbers = '';
        $this->addingReserve = true;
    }

    public function closeAddReserve(): void
    {
        $this->addingReserve = false;
        $this->reserveParentId = null;
        $this->reserveParentValue = '';
        $this->reserveNumbers = '';
    }

    public function applyAddReserve(): void
    {
        $primary = $this->reserveParentId
            ? $this->applyVisibility(ContactEntry::query())->find($this->reserveParentId)
            : null;
        if (! $primary || ! is_null($primary->parent_id)) {
            $this->dispatch('toast', type: 'error', message: 'Оберіть основний запис');

            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->can('update', $primary)) {
            $this->dispatch('toast', type: 'error', message: 'Немає прав на цей запис');

            return;
        }

        // One value per line — each becomes a reserve of the primary on its site.
        $values = collect(preg_split('/[\r\n]+/', trim($this->reserveNumbers)) ?: [])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values();

        if ($values->isEmpty()) {
            $this->dispatch('toast', type: 'error', message: 'Введіть хоча б один номер');

            return;
        }

        $order = (int) ContactEntry::where('parent_id', $primary->id)->where('site_id', $primary->site_id)->max('order');
        $created = [];

        ContactEntry::disableAuditing();
        try {
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
        } finally {
            ContactEntry::enableAuditing();
        }

        ActivityLogService::log('entry.bulk.attached', null, [
            'done' => count($created), 'parent_id' => $primary->id, 'created' => true,
        ], context: 'bulk');
        $this->syncSites([(int) $primary->site_id]);

        $this->closeAddReserve();
        $this->clearSelected();

        $this->dispatch('toast',
            type: 'success',
            message: 'Додано резервів: '.count($created),
            action: 'bulkPurgeCreated',
            actionLabel: 'Відмінити',
            actionData: ['ids' => $created],
        );
    }
}
