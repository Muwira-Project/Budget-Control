@props([
    'label' => '',
    'value' => '0',
    'fullValue' => null,
    'icon' => 'banknotes',
    'tone' => 'brand',
    'hint' => null,
    'href' => null,
    'loading' => false,
])

@php
$tones = [
    'brand' => ['bg' => 'bg-brand-50', 'text' => 'text-brand-700', 'icon' => 'text-brand-600', 'ring' => 'ring-brand-100'],
    'sky' => ['bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'icon' => 'text-sky-600', 'ring' => 'ring-sky-100'],
    'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'icon' => 'text-amber-600', 'ring' => 'ring-amber-100'],
    'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'icon' => 'text-emerald-600', 'ring' => 'ring-emerald-100'],
    'red' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'icon' => 'text-red-600', 'ring' => 'ring-red-100'],
    'slate' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'icon' => 'text-slate-500', 'ring' => 'ring-slate-200'],
];
$tone = $tones[$tone] ?? $tones['brand'];
$displayHint = $hint ? str_replace('Rp ', "Rp\u{00A0}", $hint) : null;
@endphp

<div
    @if ($href) onclick="window.location.href='{{ $href }}'" @endif
    @if ($href || $loading) wire:navigate @endif
    {{ $attributes->merge(['class' => 'app-card app-card-hover ' . ($href ? 'cursor-pointer' : '')]) }}
>
    <div class="p-4 sm:p-5">
        <div class="flex min-w-0 items-start justify-between gap-3">
            <p class="line-clamp-2 min-w-0 text-[11px] font-semibold uppercase leading-4 tracking-wider text-slate-500" title="{{ $label }}">{{ $label }}</p>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $tone['bg'] }} {{ $tone['icon'] }} ring-1 {{ $tone['ring'] }}">
                <x-icon :name="$icon" class="h-5 w-5" />
            </span>
        </div>
        <p class="mt-3 whitespace-nowrap text-[clamp(0.625rem,1vw,1.125rem)] font-bold leading-tight tracking-tight tabular-nums {{ $tone['text'] }}" title="{{ $fullValue ?? $value }}">{{ $value }}</p>
        @if ($hint)
            <p class="mt-2 line-clamp-2 text-xs leading-5 text-slate-500" title="{{ $hint }}">{{ $displayHint }}</p>
        @endif
    </div>
</div>
