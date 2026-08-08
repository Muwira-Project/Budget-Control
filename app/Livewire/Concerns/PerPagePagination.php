<?php

namespace App\Livewire\Concerns;

/**
 * Adds a per-page selector (10/25/50/100) and a "jump to page" input
 * to Livewire components that already use the WithPagination trait.
 */
trait PerPagePagination
{
    /**
     * The number of rows shown per page.
     */
    public int $perPage = 10;

    /**
     * The page number typed in the "jump to" input.
     */
    public int $jumpToPage = 1;

    /**
     * Reset the paginator when the page size changes.
     */
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Jump to the page number typed in the "jump to" input.
     */
    public function jumpTo(): void
    {
        $this->setPage(max(1, (int) $this->jumpToPage));
    }
}
