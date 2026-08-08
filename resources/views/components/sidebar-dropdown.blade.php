@props([
    'label' => '',
    'icon' => null,
    'active' => false,
])

<div x-data="{ open: @js($active) }" {{ $attributes }}>
    <button
        type="button"
        @click="open = ! open"
        aria-expanded="false"
        :aria-expanded="open ? 'true' : 'false'"
        class="group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900"
    >
        @if ($icon)
            <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
        @endif
        <span class="flex-1 truncate text-start">{{ $label }}</span>
        <x-icon name="chevron-down" class="h-4 w-4 shrink-0 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
    </button>

    <div x-show="open" x-cloak class="mt-1 space-y-1 border-l border-gray-200 ps-3 ms-5">
        {{ $slot }}
    </div>
</div>
