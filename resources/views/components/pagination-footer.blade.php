@props(['paginator'])

@if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator && $paginator->hasPages())
    <div class="flex flex-col gap-3 border-t border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 text-sm text-gray-600">
            <label for="per_page" class="shrink-0">Rows</label>
            <select id="per_page" wire:model.live="perPage" class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach ([10, 25, 50, 100] as $option)
                    <option value="{{ $option }}" @selected((int) $paginator->perPage() === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>

        {{ $paginator->links() }}

        <form wire:submit="jumpTo" class="flex items-center gap-2 text-sm text-gray-600">
            <span>Go to</span>
            <input type="number" min="1" max="{{ $paginator->lastPage() }}" wire:model="jumpToPage" class="w-16 rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            <span>of {{ $paginator->lastPage() }}</span>
        </form>
    </div>
@endif