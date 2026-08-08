@props([
    'paginator',
    'selectedIds' => [],
])

@if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator)
    @php
        $rowIds = $paginator->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selectedIds = array_map('intval', is_array($selectedIds) ? $selectedIds : []);
        $selectedOnPage = count(array_intersect($rowIds, $selectedIds));
        $totalSelected = count($selectedIds);
    @endphp
    <div class="flex flex-col gap-2 border-b border-gray-100 bg-gray-50 px-6 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3 text-sm text-gray-700">
            <input
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                @checked($totalSelected > 0 && $selectedOnPage === count($rowIds))
                @disabled($rowIds === [])
                wire:click="toggleAllVisible"
                aria-label="Select all rows on this page"
            />
            <span>
                <span class="font-medium">{{ $selectedOnPage }}</span> selected on this page
                @if ($totalSelected > $selectedOnPage)
                    <span class="text-gray-500">({{ $totalSelected }} total)</span>
                @endif
            </span>
            @if ($totalSelected > 0)
                <button type="button" wire:click="clearSelection" class="text-xs font-medium text-gray-500 hover:text-gray-700">Clear</button>
            @endif
        </div>

        @if ($totalSelected > 0)
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    wire:click="deleteSelected"
                    onclick="return confirm('Delete {{ $totalSelected }} row(s)? Rows still in use will be skipped.')"
                    class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    Delete selected ({{ $totalSelected }})
                </button>
            </div>
        @endif
    </div>
@endif