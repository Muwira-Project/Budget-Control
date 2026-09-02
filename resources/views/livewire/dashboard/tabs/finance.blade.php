{{-- ============ BUDGET UTILIZATION + CASHFLOW ============ --}}
<section class="grid grid-cols-1 gap-4 lg:grid-cols-2 mb-6">
    {{-- Budget Utilization --}}
    <div class="app-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="chart-pie" class="h-5 w-5 text-brand-600" /> Budget Utilization</h3>
                <p class="mt-0.5 text-sm text-slate-500">Budget used against approved budget</p>
            </div>
            <span class="rounded-full bg-brand-50 px-3 py-1 text-sm font-bold text-brand-700 ring-1 ring-brand-100">{{ $usagePercent }}%</span>
        </div>
        <div class="space-y-4 p-5">
            <x-progress-bar :value="$usagePercent" tone="brand" />
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Budget Used</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ format_idr($totalRealisasi) }}</p>
                    <p class="text-xs text-slate-500">{{ $usagePercent }}% of budget</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Remaining Budget</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ format_idr($totalSisa) }}</p>
                    <p class="text-xs text-slate-500">{{ round(100 - $usagePercent, 1) }}% remaining</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Cashflow Summary --}}
    <div class="app-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="coins" class="h-5 w-5 text-brand-600" /> Cashflow Summary</h3>
                <p class="mt-0.5 text-sm text-slate-500">Cash basis - income, expense, and net cash position</p>
            </div>
            <a href="{{ route('cashflows.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all &rarr;</a>
        </div>
        <div class="space-y-4 p-5">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-emerald-50 p-3 ring-1 ring-emerald-100">
                    <p class="flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wider text-emerald-700"><x-icon name="arrow-up-right" class="h-3.5 w-3.5" /> Income</p>
                    <p class="mt-1 text-lg font-bold text-emerald-800">{{ format_idr($stats['cash_in']) }}</p>
                </div>
                <div class="rounded-lg bg-red-50 p-3 ring-1 ring-red-100">
                    <p class="flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wider text-red-700"><x-icon name="arrow-down-right" class="h-3.5 w-3.5" /> Expense</p>
                    <p class="mt-1 text-lg font-bold text-red-800">{{ format_idr($stats['cash_out']) }}</p>
                </div>
                <div class="rounded-lg bg-brand-50 p-3 ring-1 ring-brand-100">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">Net Cashflow</p>
                    <p class="mt-1 text-lg font-bold text-brand-800">{{ format_idr($stats['saldo_kas']) }}</p>
                </div>
            </div>
            <div class="flex h-2 w-full overflow-hidden rounded-full bg-slate-100">
                @php $cashMax = max(1, $stats['cash_in'] + $stats['cash_out']); @endphp
                <div class="h-full bg-emerald-500" style="width: {{ $stats['cash_in'] / $cashMax * 100 }}%"></div>
                <div class="h-full bg-red-500" style="width: {{ $stats['cash_out'] / $cashMax * 100 }}%"></div>
            </div>
            <p class="text-xs text-slate-500">Proportion of cash in vs cash out.</p>
        </div>
    </div>
</section>

{{-- ============ CASH ACTIVITY ============ --}}
<div class="app-card overflow-hidden mb-6">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="wallet" class="h-5 w-5 text-brand-600" /> Cash Activity</h3>
            <p class="mt-0.5 text-sm text-slate-500">Cash In, Cash Out & Fund Transfer</p>
        </div>
        <a href="{{ route('cashflows.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all &rarr;</a>
    </div>
    <div class="grid grid-cols-2 gap-3 p-5">
        <a href="{{ route('cashflows.index') }}" wire:navigate class="rounded-lg bg-emerald-50 p-3 text-center ring-1 ring-emerald-100 transition hover:bg-emerald-100/70">
            <p class="text-2xl font-bold text-emerald-700">{{ format_idr($stats['cash_in'] ?? 0) }}</p>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600">Cash In</p>
        </a>
        <a href="{{ route('cashflows.index') }}" wire:navigate class="rounded-lg bg-red-50 p-3 text-center ring-1 ring-red-100 transition hover:bg-red-100/70">
            <p class="text-2xl font-bold text-red-700">{{ format_idr($stats['cash_out'] ?? 0) }}</p>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-red-600">Cash Out</p>
        </a>
    </div>
</div>

{{-- ============ FINANCIAL SUMMARY (4 metric cards) ============ --}}
<section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-metric-card label="Total Budget" :value="format_idr($stats['total_budget'])" icon="clipboard" tone="brand" hint="Accrual: approved project budget" :href="auth()->user()->isAdmin() ? route('budgeting.index') : null" />
    <x-metric-card label="Total Actual" :value="format_idr($stats['total_realisasi'])" icon="trending-up" tone="amber" hint="Accrual: recorded transactions" :href="auth()->user()->isAdmin() ? route('realisasi.index') : null" />
    <x-metric-card label="Remaining Budget" :value="format_idr($stats['total_sisa'])" icon="wallet" :tone="$totalSisa >= 0 ? 'emerald' : 'red'" :hint="$totalSisa >= 0 ? 'Accrual: budget not yet used' : 'Accrual: over budget'" :href="auth()->user()->isAdmin() ? route('realisasi.index') : null" />
    <x-metric-card label="Variance" :value="format_idr($variance)" icon="scale" :tone="$variance <= 0 ? 'brand' : 'red'" :hint="$variance <= 0 ? 'Accrual: actual vs budget' : 'Over budget by ' . format_idr($variance)" href="{{ route('monitoring.index') }}" />
</section>