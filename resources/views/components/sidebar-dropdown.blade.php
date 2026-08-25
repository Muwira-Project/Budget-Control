@props([
    'label' => '',
    'icon' => null,
    'active' => false,
])

<div x-data="{ isOpen: @js($active) }" {{ $attributes }}>
    <button
        type="button"
        @click="isOpen = ! isOpen"
        aria-expanded="false"
        :aria-expanded="isOpen ? 'true' : 'false'"
        @class([
            'group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition',
            'font-semibold text-white' => $active,
            'font-medium text-slate-300 hover:bg-white/5 hover:text-white' => ! $active,
        ])
    >
        @if ($icon)
            <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
        @endif
        <span class="flex-1 truncate text-start">{{ $label }}</span>
        <x-icon name="chevron-down" class="h-4 w-4 shrink-0 transition-transform" x-bind:class="isOpen ? 'rotate-180' : ''" />
    </button>

    <div x-show="isOpen" x-cloak class="mt-1 space-y-0.5 border-l border-white/10 ps-3 ms-4">
        {{ $slot }}
    </div>
</div>