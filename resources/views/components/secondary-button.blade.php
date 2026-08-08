<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:shadow-sm focus:outline-none focus:ring-4 focus:ring-blue-500/10 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
