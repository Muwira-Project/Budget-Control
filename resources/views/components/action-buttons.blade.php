@props([
    'editHref' => null,
    'deleteId' => null,
    'showDelete' => true,
    'extra' => null,
])

<div class="flex items-center justify-end gap-1.5">
    @if ($slot)
        {{ $slot }}
    @endif

    @if ($editHref)
        <a href="{{ $editHref }}" wire:navigate title="Edit"
           class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600">
            <x-icon name="edit" class="h-4 w-4" />
        </a>
    @endif

    @if ($showDelete && $deleteId !== null)
        <button type="button" @click.stop="$dispatch('open-confirm-modal', {{ $deleteId }})" title="Delete"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-red-300 hover:bg-red-50 hover:text-red-600">
            <x-icon name="trash" class="h-4 w-4" />
        </button>
    @endif
</div>
