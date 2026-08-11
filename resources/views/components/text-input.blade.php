@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-slate-200 bg-white shadow-sm transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10']) }}>
