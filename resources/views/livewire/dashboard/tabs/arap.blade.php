{{-- ============ AR BREAKDOWN ============ --}}
<div class="app-card overflow-hidden mb-6">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="receipt" class="h-5 w-5 text-brand-600" /> Receivable (AR) Breakdown</h3>
            <p class="mt-0.5 text-sm text-slate-500">Outstanding AR by category</p>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Billed --}}
        <a href="{{ route('receivables.index', ['ar_category' => 'billed']) }}" wire:navigate class="rounded-lg bg-emerald-50 p-3 ring-1 ring-emerald-100 transition hover:bg-emerald-100/70">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Billed</p>
            <p class="mt-1 text-xl font-bold text-emerald-800">{{ format_idr($stats['ar_breakdown']['billed']['outstanding'] ?? 0) }}</p>
            <p class="text-xs text-emerald-600">{{ $stats['ar_breakdown']['billed']['count'] ?? 0 }} invoices</p>
        </a>
        {{-- Unbilled --}}
        <a href="{{ route('receivables.index', ['ar_category' => 'unbilled']) }}" wire:navigate class="rounded-lg bg-amber-50 p-3 ring-1 ring-amber-100 transition hover:bg-amber-100/70">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Unbilled</p>
            <p class="mt-1 text-xl font-bold text-amber-800">{{ format_idr($stats['ar_breakdown']['unbilled']['outstanding'] ?? 0) }}</p>
            <p class="text-xs text-amber-600">{{ $stats['ar_breakdown']['unbilled']['count'] ?? 0 }} invoices</p>
        </a>
        {{-- In Progress --}}
        <a href="{{ route('receivables.index', ['ar_category' => 'inprogress']) }}" wire:navigate class="rounded-lg bg-blue-50 p-3 ring-1 ring-blue-100 transition hover:bg-blue-100/70">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">In Progress</p>
            <p class="mt-1 text-xl font-bold text-blue-800">{{ format_idr($stats['ar_breakdown']['inprogress']['outstanding'] ?? 0) }}</p>
            <p class="text-xs text-blue-600">{{ $stats['ar_breakdown']['inprogress']['count'] ?? 0 }} invoices</p>
        </a>
        {{-- Grand Total --}}
        <a href="{{ route('receivables.index') }}" wire:navigate class="rounded-lg bg-brand-50 p-3 ring-1 ring-brand-100 transition hover:bg-brand-100/70">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">Total AR</p>
            <p class="mt-1 text-xl font-bold text-brand-800">{{ format_idr($stats['ar_breakdown']['total']['outstanding'] ?? 0) }}</p>
            <p class="text-xs text-brand-600">{{ $stats['ar_breakdown']['total']['count'] ?? 0 }} invoices</p>
        </a>
    </div>
</div>

{{-- ============ AP BREAKDOWN ============ --}}
<div class="app-card overflow-hidden mb-6">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="credit-card" class="h-5 w-5 text-red-600" /> Payable (AP) Breakdown</h3>
            <p class="mt-0.5 text-sm text-slate-500">Outstanding AP by party type</p>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2 lg:grid-cols-5">
        {{-- Vendor --}}
        @php $vendor = $stats['ap_breakdown']['VENDOR'] ?? ['outstanding' => 0, 'count' => 0, 'label' => 'Vendor']; @endphp
        <a href="{{ route('payables.index', ['party_type' => 'VENDOR']) }}" wire:navigate class="rounded-lg bg-red-50 p-3 ring-1 ring-red-100 transition hover:bg-red-100/70">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-red-700">{{ $vendor['label'] }}</p>
            <p class="mt-1 text-xl font-bold text-red-800">{{ format_idr($vendor['outstanding'] ?? 0) }}</p>
            <p class="text-xs text-red-600">{{ $vendor['count'] ?? 0 }} payables</p>
        </a>
        {{-- Supplier --}}
        @php $supplier = $stats['ap_breakdown']['SUPPLIER'] ?? ['outstanding' => 0, 'count' => 0, 'label' => 'Supplier']; @endphp
        <a href="{{ route('payables.index', ['party_type' => 'SUPPLIER']) }}" wire:navigate class="rounded-lg bg-amber-50 p-3 ring-1 ring-amber-100 transition hover:bg-amber-100/70">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">{{ $supplier['label'] }}</p>
            <p class="mt-1 text-xl font-bold text-amber-800">{{ format_idr($supplier['outstanding'] ?? 0) }}</p>
            <p class="text-xs text-amber-600">{{ $supplier['count'] ?? 0 }} payables</p>
        </a>
        {{-- Mandor --}}
        @php $mandor = $stats['ap_breakdown']['MANDOR'] ?? ['outstanding' => 0, 'count' => 0, 'label' => 'Mandor']; @endphp
        <a href="{{ route('payables.index', ['party_type' => 'MANDOR']) }}" wire:navigate class="rounded-lg bg-sky-50 p-3 ring-1 ring-sky-100 transition hover:bg-sky-100/70">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-sky-700">{{ $mandor['label'] }}</p>
            <p class="mt-1 text-xl font-bold text-sky-800">{{ format_idr($mandor['outstanding'] ?? 0) }}</p>
            <p class="text-xs text-sky-600">{{ $mandor['count'] ?? 0 }} payables</p>
        </a>
        {{-- Investor --}}
        @php $investor = $stats['ap_breakdown']['INVESTOR'] ?? ['outstanding' => 0, 'count' => 0, 'label' => 'Investor']; @endphp
        <a href="{{ route('payables.index', ['party_type' => 'INVESTOR']) }}" wire:navigate class="rounded-lg bg-purple-50 p-3 ring-1 ring-purple-100 transition hover:bg-purple-100/70">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-purple-700">{{ $investor['label'] }}</p>
            <p class="mt-1 text-xl font-bold text-purple-800">{{ format_idr($investor['outstanding'] ?? 0) }}</p>
            <p class="text-xs text-purple-600">{{ $investor['count'] ?? 0 }} payables</p>
        </a>
        {{-- Grand Total --}}
        @php $total = $stats['ap_breakdown']['total'] ?? ['outstanding' => 0, 'count' => 0, 'label' => 'Total']; @endphp
        <a href="{{ route('payables.index') }}" wire:navigate class="rounded-lg bg-brand-50 p-3 ring-1 ring-brand-100 transition hover:bg-brand-100/70">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">{{ $total['label'] }}</p>
            <p class="mt-1 text-xl font-bold text-brand-800">{{ format_idr($total['outstanding'] ?? 0) }}</p>
            <p class="text-xs text-brand-600">{{ $total['count'] ?? 0 }} payables</p>
        </a>
    </div>
</div>

{{-- ============ PAYMENT SUMMARY ============ --}}
<div class="app-card overflow-hidden">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="banknotes" class="h-5 w-5 text-brand-600" /> Payment Summary</h3>
            <p class="mt-0.5 text-sm text-slate-500">Outstanding AR & AP totals</p>
        </div>
        <a href="{{ route('payments.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all &rarr;</a>
    </div>
    <div class="grid grid-cols-2 gap-3 p-5">
        <div class="rounded-lg bg-emerald-50 p-3 ring-1 ring-emerald-100">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Total AR Outstanding</p>
            <p class="mt-1 text-xl font-bold text-emerald-800">{{ format_idr($stats['outstanding_ar'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg bg-red-50 p-3 ring-1 ring-red-100">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-red-700">Total AP Outstanding</p>
            <p class="mt-1 text-xl font-bold text-red-800">{{ format_idr($stats['outstanding_ap'] ?? 0) }}</p>
        </div>
    </div>
</div>