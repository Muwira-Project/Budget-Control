{{-- ============ SECTION 1: FINANCIAL SUMMARY (4 metric cards) ============ --}}
<section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-metric-card label="Total Budget" :value="format_idr($stats['total_budget'])" icon="clipboard" tone="brand" hint="Accrual: approved project budget" :href="auth()->user()->isAdmin() ? route('budgeting.index') : null" />
    <x-metric-card label="Total Actual" :value="format_idr($stats['total_realisasi'])" icon="trending-up" tone="amber" hint="Accrual: recorded transactions" :href="auth()->user()->isAdmin() ? route('realisasi.index') : null" />
    <x-metric-card label="Remaining Budget" :value="format_idr($stats['total_sisa'])" icon="wallet" :tone="$totalSisa >= 0 ? 'emerald' : 'red'" :hint="$totalSisa >= 0 ? 'Accrual: budget not yet used' : 'Accrual: over budget'" :href="auth()->user()->isAdmin() ? route('realisasi.index') : null" />
    <x-metric-card label="Variance" :value="format_idr($variance)" icon="scale" :tone="$variance <= 0 ? 'brand' : 'red'" :hint="$variance <= 0 ? 'Accrual: actual vs budget' : 'Over budget by ' . format_idr($variance)" href="{{ route('monitoring.index') }}" />
</section>

{{-- ============ SECTION 2: SUBMIT & REVISI PROJECTS ============ --}}
<section class="grid grid-cols-1 gap-4 lg:grid-cols-2 mb-6">
    {{-- Submit Projects (Draft) --}}
    <div class="app-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="paper-plane" class="h-5 w-5 text-blue-600" /> Submit Project (Draft)</h3>
                <p class="mt-0.5 text-sm text-slate-500">Projects ready for submission</p>
            </div>
            <a href="{{ route('projects.index', ['status' => 'draft']) }}" wire:navigate class="text-xs font-semibold text-blue-600 hover:text-blue-700">View all &rarr;</a>
        </div>
        <div class="p-5">
            @php $submitCount = $this->submitRevisiCounts['submit_count'] ?? 0; $submitProjects = $this->projectsToSubmit; @endphp
            @if ($submitCount > 0)
                <div class="mb-4 flex items-center justify-between">
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-bold text-blue-700 ring-1 ring-blue-100">{{ $submitCount }} project(s)</span>
                </div>
                <div class="space-y-3 max-h-60 overflow-y-auto">
                    @foreach ($submitProjects as $project)
                        <a href="{{ route('projects.edit', $project['project_id']) }}" wire:navigate class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-100 transition hover:bg-slate-100">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-slate-900 truncate">{{ $project['kode'] }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $project['nama'] }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if ($project['division'])
                                    <span class="text-xs text-slate-500 px-2 py-0.5 rounded bg-white">{{ $project['division'] }}</span>
                                @endif
                                <x-status-badge :text="$project['status']" tone="blue" />
                                <p class="text-sm font-semibold text-slate-700 tabular-nums">{{ format_idr($project['nilai_total']) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <x-empty-state icon="paper-plane" title="No draft projects" description="All projects have been submitted." />
            @endif
        </div>
    </div>

    {{-- Revisi Projects --}}
    <div class="app-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="arrow-path" class="h-5 w-5 text-amber-600" /> Revisi Project</h3>
                <p class="mt-0.5 text-sm text-slate-500">Projects needing revision</p>
            </div>
            <a href="{{ route('projects.index', ['status' => 'revisi']) }}" wire:navigate class="text-xs font-semibold text-amber-600 hover:text-amber-700">View all &rarr;</a>
        </div>
        <div class="p-5">
            @php $revisiCount = $this->submitRevisiCounts['revisi_count'] ?? 0; $revisiProjects = $this->projectsToRevisi; @endphp
            @if ($revisiCount > 0)
                <div class="mb-4 flex items-center justify-between">
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-bold text-amber-700 ring-1 ring-amber-100">{{ $revisiCount }} project(s)</span>
                </div>
                <div class="space-y-3 max-h-60 overflow-y-auto">
                    @foreach ($revisiProjects as $project)
                        <a href="{{ route('projects.edit', $project['project_id']) }}" wire:navigate class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-100 transition hover:bg-slate-100">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-slate-900 truncate">{{ $project['kode'] }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $project['nama'] }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if ($project['division'])
                                    <span class="text-xs text-slate-500 px-2 py-0.5 rounded bg-white">{{ $project['division'] }}</span>
                                @endif
                                <x-status-badge :text="$project['status']" tone="amber" />
                                <p class="text-sm font-semibold text-slate-700 tabular-nums">{{ format_idr($project['nilai_total']) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <x-empty-state icon="arrow-path" title="No projects in revision" description="No projects currently need revision." />
            @endif
        </div>
    </div>
</section>

{{-- ============ SECTION 3: BUDGET UTILIZATION + CASHFLOW ============ --}}
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

{{-- ============ SECTION 4: PROJECT OVERVIEW + RECENT ACTIVITY ============ --}}
<section class="grid grid-cols-1 gap-4 xl:grid-cols-3 mb-6">
    {{-- Project Overview --}}
    <div class="app-card overflow-hidden xl:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="briefcase" class="h-5 w-5 text-brand-600" /> Project Overview</h3>
                <p class="mt-0.5 text-sm text-slate-500">Budget, actual, variance, and progress per project (Accrual basis) — klik baris untuk rincian</p>
            </div>
            <a href="{{ route('projects.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all &rarr;</a>
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
            <a href="{{ route('audit-log.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all &rarr;</a>
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
                        <p class="mt-0.5 text-xs text-slate-400">{{ $activity->user?->name ?? 'System' }} · {{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <x-empty-state icon="history" title="No activity yet" description="Actions will appear here as you use the workspace." />
            @endforelse
        </div>
    </div>
</section>

{{-- ============ SECTION 5: ACTUAL PER CATEGORY ============ --}}
<div class="app-card overflow-hidden mb-6">
    <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="tag" class="h-5 w-5 text-brand-600" /> Actual per Category</h3>
            <p class="mt-0.5 text-sm text-slate-500">Budgeting categories — klik baris untuk rincian transaksi</p>
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