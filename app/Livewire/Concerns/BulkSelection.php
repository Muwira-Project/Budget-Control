<?php

namespace App\Livewire\Concerns;

/**
 * Adds bulk-selection state + actions to Livewire index components
 * that already expose a paginated collection via a Computed method.
 */
trait BulkSelection
{
    /**
     * The ids of rows the user has selected for bulk actions.
     *
     * @var array<int, int>
     */
    public array $selectedIds = [];

    /**
     * Remember the id-collection property of the paginated collection
     * on the implementing component. Override this to return the
     * collection property name (e.g. "akuns", "payables").
     */
    protected function bulkCollectionProperty(): string
    {
        return '';
    }

    /**
     * Toggle one id in the bulk-selection array.
     */
    public function toggleSelected(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));
        } else {
            $this->selectedIds[] = $id;
        }
    }

    /**
     * Toggle every id in the currently visible page.
     */
    public function toggleAllVisible(): void
    {
        $property = $this->bulkCollectionProperty();

        if ($property === '') {
            return;
        }

        $items = $this->{$property};
        $ids = $items->pluck('id')->all();
        $intersect = array_intersect($ids, $this->selectedIds);

        $this->selectedIds = count($intersect) === count($ids)
            ? []
            : array_values($ids);
    }

    /**
     * Clear the current selection.
     */
    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }
}
