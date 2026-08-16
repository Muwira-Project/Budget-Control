<div class="py-6 lg:py-8" wire:poll.45s>
@php
    $stats = $this->statistics;
    $invalid = $this->dateRangeInvalid;
    $totalRealisasi = (int) ($stats['total_realisasi'] ?? 0);
    $totalBudget = (float) ($stats['total_budget'] ?? 0);
    $totalSisa = (float) ($stats['total_sisa'] ?? 0);
    $variance = $totalRealisasi - $totalBudget;
    $usagePercent = $totalBudget > 0 ? min(100, round($totalRealisasi / $totalBudget * 100, 1)) : 0;
    $kategoriTotal = array_sum($stats['kategori_breakdown'] ?? []);
    $categoryHues = ['emerald','teal','cyan','sky','indigo','fuchsia'];
@endphp

<div class="space-y-6">
    {{-- ============ SECTION 1: WELCOME HEADER ============ --}}
    <div class="app-card overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-brand-950 text-white">
        <div class="flex flex-col gap-6 p-6 lg:flex-row lg:items-center lg:justify-between lg:p-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-300">Financial Workspace</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{{ $companyName ?? 'MyFinance' }} Ã¢â‚¬â€ Budget Control</h1>
                <p class="mt-1.5 max-w-xl text-sm text-emerald-100/90">Financial overview and budget monitoring across projects, accounts, and periods.</p>
                <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-emerald-100/90">
                    <span class="inline-flex items-center gap-1.5">
                        <x-icon name="calendar" class="h-4 w-4 text-emerald-300" />
                        @if ($this->activePeriod)
                            {{ $this->activePeriod->nomor }} Ãƒâ€šÃ‚Â· {{ $this->activePeriod->periode_label }}
                        @else
                            No active period
                        @endif
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-icon name="briefcase" class="h-4 w-4 text-emerald-300" />
                        @if ($this->activeProject)
                            {{ $this->activeProject->kode }} Ãƒâ€šÃ‚Â· {{ $this->activeProject->nama }}
                        @else
                            No project yet
                        @endif
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-icon name="clock" class="h-4 w-4 text-emerald-300" />
                        Last update {{ $this->lastUpdate->diffForHumans() }}
                    </span>
                </div>
            </div>

            {{-- Date range filter --}}
            <div class="shrink-0 rounded-xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="start_date" class="block text-[11px] font-semibold uppercase tracking-wider text-emerald-200">Start Date</label>
                        <input id="start_date" type="date" wire:model.live="startDate" class="mt-1 block w-full rounded-lg border-transparent bg-white/95 text-sm text-slate-800 focus:border-white focus:ring-2 focus:ring-white/40" />
                    </div>
                    <div>
                        <label for="end_date" class="block text-[11px] font-semibold uppercase tracking-wider text-emerald-200">End Date</label>
                        <input id="end_date" type="date" wire:model.live="endDate" class="mt-1 block w-full rounded-lg border-transparent bg-white/95 text-sm text-slate-800 focus:border-white focus:ring-2 focus:ring-white/40" />
                    </div>
                </div>
                @if ($this->startDate || $this->endDate)
                    <div class="mt-2 flex items-center justify-between gap-2 text-xs text-emerald-100">
                        <span>Filter active: <strong>{{ $this->startDate ?? '...' }}</strong> Ã¢â€ â€™ <strong>{{ $this->endDate ?? '...' }}</strong></span>
                        <button type="button" wire:click="$set('startDate', null); $set('endDate', null)" class="rounded-md bg-white/10 px-2 py-0.5 font-medium hover:bg-white/20">Clear</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($invalid)
        <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <p class="font-semibold">Invalid date range</p>
                <p class="mt-0.5 text-red-600/90">Start date must be before or equal to end date.</p>
            </div>
        </div>
    @endif

    @unless ($invalid)
        {{-- ============ SECTION 2: FINANCIAL SUMMARY (4 metric cards) ============ --}}
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-metric-card label="Total Budget" :value="format_idr($stats['total_budget'])" icon="clipboard" tone="brand" hint="Accrual: approved project budget" :href="auth()->user()->isAdmin() ? route('budget-plans.index') : null" />
            <x-metric-card label="Total Actual" :value="format_idr($stats['total_realisasi'])" icon="trending-up" tone="amber" hint="Accrual: recorded transactions" :href="auth()->user()->isAdmin() ? route('realisasi.index') : null" />
            <x-metric-card label="Remaining Budget" :value="format_idr($stats['total_sisa'])" icon="wallet" :tone="$totalSisa >= 0 ? 'emerald' : 'red'" :hint="$totalSisa >= 0 ? 'Accrual: budget not yet used' : 'Accrual: over budget'" :href="auth()->user()->isAdmin() ? route('realisasi.index') : null" />
            <x-metric-card label="Variance" :value="format_idr($variance)" icon="scale" :tone="$variance <= 0 ? 'brand' : 'red'" :hint="$variance <= 0 ? 'Accrual: actual vs budget' : 'Over budget by ' . format_idr($variance)" href="{{ route('monitoring.index') }}" />
        </section>

        {{-- ============ SECTION 3: BUDGET UTILIZATION + CASHFLOW ============ --}}
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
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
                    <a href="{{ route('cashflows.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all Ã¢â€ â€™</a>
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

        {{-- ============ SECTION 4: PROJECT OVERVIEW + RECENT ACTIVITY ============ --}}
        <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            {{-- Project Overview --}}
            <div class="app-card overflow-hidden xl:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="briefcase" class="h-5 w-5 text-brand-600" /> Project Overview</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Budget, actual, variance, and progress per project (Accrual basis) Ã¢â‚¬â€ klik baris untuk rincian</p>
                    </div>
                    <a href="{{ route('projects.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all Ã¢â€ â€™</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table min-w-full">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th class="text-right">Budget</th>
                                <th class="text-right">Actual</th>
                                <th class="text-right">Variance</th>
                                <th class="w-40">Progress</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (collect($stats['profit_projects'] ?? [])->take(6) as $project)
                                @php
                                    $pBudget = $project['nilai'];
                                    $pActual = (float) $project['realisasi'];
                                    $pVariance = $pBudget - $pActual;
                                    $pPct = $pBudget > 0 ? min(100, round($pActual / $pBudget * 100, 1)) : 0;
                                    $status = \App\Models\Project::find($project['project_id'])?->status?->label() ?? 'Active';
                                @endphp
                                <tr class="cursor-pointer" wire:click="showProjectDetail({{ $project['project_id'] }})">
                                    <td>
                                        <p class="font-medium text-slate-900">{{ $project['kode'] }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $project['nama'] }}</p>
                                    </td>
                                    <td class="text-right tabular-nums text-slate-700">{{ format_idr($pBudget) }}</td>
                                    <td class="text-right tabular-nums text-slate-700">{{ format_idr($pActual) }}</td>
                                    <td class="text-right tabular-nums font-medium {{ $pVariance >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ format_idr($pVariance) }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1"><x-progress-bar :value="$pPct" :tone="$pVariance >= 0 ? 'emerald' : 'red'" /></div>
                                            <span class="text-xs font-semibold tabular-nums text-slate-600">{{ number_format($pPct, 1) }}%</span>
                                        </div>
                                    </td>
                                    <td><x-status-badge :text="$status" tone="blue" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-empty-state icon="briefcase" title="No projects yet" description="Add a project to start monitoring budget and actuals." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="app-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="history" class="h-5 w-5 text-brand-600" /> Recent Activity</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Latest actions in the workspace</p>
                    </div>
                    <a href="{{ route('audit-log.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all Ã¢â€ â€™</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($this->recentActivities as $activity)
                        @php
                            $icon = match ($activity->action) { 'created' => 'check-circle', 'updated' => 'edit', 'deleted' => 'trash', default => 'cursor-click' };
                            $tone = match ($activity->action) { 'created' => 'text-emerald-600 bg-emerald-50', 'updated' => 'text-blue-600 bg-blue-50', 'deleted' => 'text-red-600 bg-red-50', default => 'text-slate-500 bg-slate-100' };
                        @endphp
                        <div class="flex items-start gap-3 px-5 py-3.5">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $tone }}"><x-icon :name="$icon" class="h-4 w-4" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-1 text-sm text-slate-700">{{ $activity->description ?: ucfirst($activity->action) . ' ' . class_basename($activity->subject_type) }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $activity->user?->name ?? 'System' }} Ãƒâ€šÃ‚Â· {{ $activity->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <x-empty-state icon="history" title="No activity yet" description="Actions will appear here as you use the workspace." />
                    @endforelse
                </div>
            </div>
        </section>

        {{-- Actual per Category (kept feature) --}}
        <div class="app-card overflow-hidden">
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="tag" class="h-5 w-5 text-brand-600" /> Actual per Category</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Budgeting categories Ã¢â‚¬â€ klik baris untuk rincian transaksi</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">Total {{ format_idr($totalRealisasi) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table min-w-full">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-right">Actual</th>
                            <th class="text-right">Share</th>
                            <th class="w-1/3">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['kategori_breakdown'] as $nama => $total)
                            @php $percent = $kategoriTotal > 0 ? ($total / $kategoriTotal) * 100 : 0; $hue = $categoryHues[$loop->index % count($categoryHues)]; @endphp
                            <tr class="cursor-pointer" wire:click="showCategoryDetail('{{ addslashes($nama) }}')">
                                <td class="font-medium text-slate-900">{{ $nama }}</td>
                                <td class="text-right tabular-nums text-slate-700">{{ format_idr($total) }}</td>
                                <td class="text-right tabular-nums font-medium text-slate-600">{{ number_format($percent, 1) }}%</td>
                                <td><div class="h-2 w-full overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-{{ $hue }}-500 transition-all duration-700" style="width: {{ $percent }}%"></div></div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============ SECTION 6: PAYMENT + AR/AP ============ --}}
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Cash Activity --}}
            <div class="app-card overflow-hidden">
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
            {{-- Receivable & Payable --}}
            <div class="app-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="receipt" class="h-5 w-5 text-brand-600" /> Receivable & Payable</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Outstanding AR and AP balances</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 p-5">
                    <a href="{{ route('receivables.index') }}" wire:navigate class="rounded-lg bg-brand-50 p-3 ring-1 ring-brand-100 transition hover:bg-brand-100/70">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">Outstanding Receivable</p>
                        <p class="mt-1 text-xl font-bold text-brand-800">{{ format_idr($stats['outstanding_ar']) }}</p>
                    </a>
                    <a href="{{ route('payables.index') }}" wire:navigate class="rounded-lg bg-red-50 p-3 ring-1 ring-red-100 transition hover:bg-red-100/70">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-red-700">Outstanding Payable</p>
                        <p class="mt-1 text-xl font-bold text-red-800">{{ format_idr($stats['outstanding_ap']) }}</p>
                    </a>
                </div>
            </div>
        </section>
    @endunless
</div>

    {{-- ============ PROJECT DETAIL MODAL ============ --}}
    @if ($this->selectedProjectId && $this->selectedProject)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" wire:click="closeDetails"></div>
            <div class="relative z-10 w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-card-hover">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-600">Project Detail</p>
                        <h3 class="mt-0.5 font-semibold text-slate-900">{{ $this->selectedProject->kode }} Ã¢â‚¬â€ {{ $this->selectedProject->nama }}</h3>
                    </div>
                    <button type="button" wire:click="closeDetails" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100"><x-icon name="x-mark" class="h-5 w-5" /></button>
                </div>
                <div class="grid grid-cols-1 gap-2 border-b border-slate-100 bg-slate-50/60 px-6 py-4 sm:grid-cols-4">
                    <div><p class="text-xs uppercase tracking-wider text-slate-500">Contract</p><p class="mt-1 text-lg font-semibold text-slate-900">{{ format_idr($this->selectedProject->nilai_total) }}</p></div>
                    <div><p class="text-xs uppercase tracking-wider text-slate-500">Budget</p><p class="mt-1 text-lg font-semibold text-slate-900">{{ format_idr($this->selectedProject->budget_total ?? 0) }}</p></div>
                    <div><p class="text-xs uppercase tracking-wider text-slate-500">Actual</p><p class="mt-1 text-lg font-semibold text-slate-900">{{ format_idr($this->projectRealisations->sum('nominal')) }}</p></div>
                    <div><p class="text-xs uppercase tracking-wider text-slate-500">Transactions</p><p class="mt-1 text-lg font-semibold text-slate-900">{{ $this->projectRealisations->count() }}</p></div>
                </div>
                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="data-table min-w-full">
                        <thead><tr><th>Date</th><th>Account</th><th>Category</th><th>Party</th><th>Description</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                            @forelse ($this->projectRealisations as $realisasi)
                                <tr>
                                    <td class="whitespace-nowrap text-slate-700">{{ $realisasi->tanggal->format('d M Y') }}</td>
                                    <td class="text-slate-700">{{ $realisasi->akun->kode_akun }} - {{ $realisasi->akun->nama_akun }}</td>
                                    <td>@if ($realisasi->kategori)<x-status-badge :text="$realisasi->kategori->nama" tone="blue" />@else <span class="text-slate-400">-</span> @endif</td>
                                    <td class="text-slate-700">{{ $realisasi->pihak ?: '-' }}</td>
                                    <td class="text-slate-500">{{ $realisasi->keterangan }}</td>
                                    <td class="text-right font-medium text-slate-900">{{ format_idr($realisasi->nominal) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-empty-state icon="document" title="Tidak ada transaksi" description="Belum ada transaksi realisasi untuk project ini." /></td></tr>
                            @endforelse
                        </tbody>
                        <tfoot><tr><td colspan="5">Total</td><td class="text-right">{{ format_idr($this->projectRealisations->sum('nominal')) }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ CATEGORY DETAIL MODAL ============ --}}
    @if ($this->selectedCategoryName)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/50" wire:click="closeDetails"></div>
            <div class="relative z-10 w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-card-hover">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-600">Category Detail</p>
                        <h3 class="mt-0.5 font-semibold text-slate-900">{{ $this->selectedCategoryName }}</h3>
                        <p class="mt-1 text-xs text-slate-500">Filter {{ $this->startDate ?? '...' }} Ã¢â€ â€™ {{ $this->endDate ?? '...' }}</p>
                    </div>
                    <button type="button" wire:click="closeDetails" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100"><x-icon name="x-mark" class="h-5 w-5" /></button>
                </div>
                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="data-table min-w-full">
                        <thead><tr><th>Date</th><th>Project</th><th>Account</th><th>Party</th><th>Description</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                            @forelse ($this->categoryRealisations as $realisasi)
                                <tr>
                                    <td class="whitespace-nowrap text-slate-700">{{ $realisasi->tanggal->format('d M Y') }}</td>
                                    <td class="text-slate-700">{{ $realisasi->project->kode }} - {{ $realisasi->project->nama }}</td>
                                    <td class="text-slate-700">{{ $realisasi->akun->kode_akun }} - {{ $realisasi->akun->nama_akun }}</td>
                                    <td class="text-slate-700">{{ $realisasi->pihak ?: '-' }}</td>
                                    <td class="text-slate-500">{{ $realisasi->keterangan }}</td>
                                    <td class="text-right font-medium text-slate-900">{{ format_idr($realisasi->nominal) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-empty-state icon="document" title="Tidak ada transaksi" description="Belum ada transaksi pada kategori ini." /></td></tr>
                            @endforelse
                        </tbody>
                        <tfoot><tr><td colspan="5">Total</td><td class="text-right">{{ format_idr($this->categoryRealisations->sum('nominal')) }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>