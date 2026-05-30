<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Reusable bulk-selection state for Livewire list components.
 *
 * The host component must implement bulkQuery() returning the *filtered*
 * (un-paginated) base query. "Select all matching" is stored as a flag and the
 * real ids are resolved from bulkQuery() at action time — we never hydrate
 * thousands of ids into the Livewire payload.
 */
trait WithBulkSelection
{
    /** @var array<int> */
    public array $selected = [];
    public bool $selectAllMatching = false;

    abstract protected function bulkQuery(): Builder;

    public function isSelected(int $id): bool
    {
        return in_array($id, $this->selected, true);
    }

    public function toggleSelected(int $id): void
    {
        if ($this->isSelected($id)) {
            $this->selected = array_values(array_filter($this->selected, fn ($i) => $i !== $id));
        } else {
            $this->selected[] = $id;
        }
        $this->selectAllMatching = false;
    }

    /** Toggle every id on the current page (select all / clear page). */
    public function selectPage(array $ids): void
    {
        $ids = array_map('intval', $ids);
        $pageFullySelected = empty(array_diff($ids, $this->selected));

        $this->selected = $pageFullySelected
            ? array_values(array_diff($this->selected, $ids))
            : array_values(array_unique(array_merge($this->selected, $ids)));

        $this->selectAllMatching = false;
    }

    public function selectAllMatching(): void
    {
        $this->selectAllMatching = true;
    }

    public function clearSelected(): void
    {
        $this->selected = [];
        $this->selectAllMatching = false;
    }

    public function selectedCount(): int
    {
        return $this->selectAllMatching ? $this->matchingCount() : count($this->selected);
    }

    public function hasSelection(): bool
    {
        return $this->selectAllMatching || count($this->selected) > 0;
    }

    protected function matchingCount(): int
    {
        return $this->bulkQuery()->toBase()->getCountForPagination();
    }

    /** Effective ids to act on (whole filtered set when "select all matching"). */
    protected function bulkTargetIds(): array
    {
        if ($this->selectAllMatching) {
            return $this->bulkQuery()->pluck('id')->map(fn ($i) => (int) $i)->all();
        }

        return array_map('intval', $this->selected);
    }
}
