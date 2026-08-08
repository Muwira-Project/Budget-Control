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
        <x-icon name="chevron-down" class="h-4 w-4 shrink-0 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
    </button>

    <div x-show="open" x-cloak class="mt-1 space-y-0.5 border-l border-white/10 ps-3 ms-4">
        {{ $slot }}
    </div>
</div>