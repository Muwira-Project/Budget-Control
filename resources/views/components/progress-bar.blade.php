@props([
    'value' => 0,
    'tone' => 'brand',
])

@php
$tones = [
    'brand' => 'bg-brand-500',
    'emerald' => 'bg-emerald-500',
    'amber' => 'bg-amber-500',
    'red' => 'bg-red-500',
    'sky' => 'bg-sky-500',
];
$bar = $tones[$tone] ?? $tones['brand'];
$pct = max(0, min(100, (float) $value));
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
        <div class="h-full rounded-full {{ $bar }} transition-all duration-700 ease-out" style="width: {{ $pct }}%"></div>
    </div>
</div>