@props([
    'href' => '#',
    'label' => '',
    'icon' => null,
    'active' => false,
    'disabled' => false,
    'navigate' => true,
])

@php
$classes = $active
    ? 'group flex items-center gap-3 rounded-xl bg-blue-500/15 px-3 py-2.5 text-sm font-semibold text-blue-50 shadow-sm ring-1 ring-inset ring-blue-400/20'
    : 'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-blue-500/10 hover:text-blue-100';
@endphp

@if ($disabled)
    <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500">
        @if ($icon)
            <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
        @endif
        <span class="flex-1 truncate">{{ $label }}</span>
        <span class="rounded bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold text-slate-400">Segera</span>
    </span>
@else
    <a
        href="{{ $href }}"
        @if ($navigate) wire:navigate @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >
        @if ($icon)
            <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
        @endif
        <span class="flex-1 truncate">{{ $label }}</span>
        {{ $slot }}
    </a>
@endif
