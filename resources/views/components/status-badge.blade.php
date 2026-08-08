@props([
    'text' => '',
    'tone' => 'slate',
])

@php
$tones = [
    'slate' => 'bg-slate-100 text-slate-600',
    'brand' => 'bg-brand-50 text-brand-700',
    'green' => 'bg-emerald-50 text-emerald-700',
    'amber' => 'bg-amber-50 text-amber-700',
    'red' => 'bg-red-50 text-red-700',
    'blue' => 'bg-blue-50 text-blue-700',
    'purple' => 'bg-purple-50 text-purple-700',
    'indigo' => 'bg-indigo-50 text-indigo-700',
];
$tone = $tones[$tone] ?? $tones['slate'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ' . $tone]) }}>
    {{ $slot }}
    @if ($text)
        {{ $text }}
    @endif
</span>