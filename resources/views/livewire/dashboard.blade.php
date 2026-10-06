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
            <div class="flex flex-col gap-6 p-6 2xl:flex-row 2xl:items-center 2xl:justify-between 2xl:p-8">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-300">Financial Workspace</p>
                    <h1 class="mt-1 break-words text-2xl font-bold tracking-tight sm:text-3xl">{{ $companyName ?? 'MyFinance' }} &mdash; Budget Control</h1>
                    <p class="mt-1.5 max-w-xl text-sm text-emerald-100/90">Financial overview and budget monitoring across projects, accounts, and periods.</p>
                    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-emerald-100/90">
                        <span class="inline-flex items-center gap-1.5">
                            <x-icon name="calendar" class="h-4 w-4 text-emerald-300" />
                            @if ($this->activePeriod)
                            {{ $this->activePeriod->nomor }} &middot; {{ $this->activePeriod->periode_label }}
                            @else
                            No active period
                            @endif
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <x-icon name="briefcase" class="h-4 w-4 text-emerald-300" />
                            @if ($this->activeProject)
                            {{ $this->activeProject->kode }} &middot; {{ $this->activeProject->nama }}
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
                <div class="w-full min-w-0 rounded-xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur 2xl:w-[22rem] 2xl:shrink-0">
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
                        <span>Filter: <strong>{{ $this->startDate ?? '...' }}</strong> &rarr; <strong>{{ $this->endDate ?? '...' }}</strong></span>
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
            <x-metric-card label="Total Budget" :value="format_idr($stats['total_budget'])" :full-value="format_idr($stats['total_budget'])" icon="clipboard" tone="brand" hint="Includes project and non-project budgets" :href="auth()->user()->isAdmin() ? route('budgeting.index') : null" />
            <x-metric-card label="Total Actual" :value="format_idr($stats['total_realisasi'])" :full-value="format_idr($stats['total_realisasi'])" icon="trending-up" tone="amber" hint="Includes project and non-project spending" :href="auth()->user()->isAdmin() ? route('realisasi.index') : null" />
            <x-metric-card label="Remaining Budget" :value="format_idr($stats['total_sisa'])" :full-value="format_idr($stats['total_sisa'])" icon="wallet" :tone="$totalSisa >= 0 ? 'emerald' : 'red'" :hint="$totalSisa >= 0 ? 'Accrual: budget not yet used' : 'Accrual: over budget'" :href="auth()->user()->isAdmin() ? route('realisasi.index') : null" />
            <x-metric-card label="Variance" :value="format_idr($variance)" :full-value="format_idr($variance)" icon="scale" :tone="$variance <= 0 ? 'brand' : 'red'" :hint="$variance <= 0 ? 'Accrual: actual vs budget' : 'Over budget by ' . format_idr($variance)" href="{{ route('monitoring.index') }}" />
        </section>

        {{-- ============ SECTION 2B: SUBMIT & REVISI PROJECTS (separate widgets) ============ --}}
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Submit Projects (Draft) --}}
            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="paper-plane" class="h-5 w-5 text-blue-600" /> Draft Projects</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Projects ready for submission</p>
                    </div>
                    <a href="{{ route('projects.index', ['status' => 'draft']) }}" wire:navigate class="text-xs font-semibold text-blue-600 hover:text-blue-700">View all →</a>
                </div>
                <div class="p-5">
                    @php $submitCount = $this->submitRevisiCounts['submit_count'] ?? 0; $submitProjects = $this->projectsToSubmit; @endphp
                    @if ($submitCount > 0)
                    <div class="mb-4 flex items-center justify-between">
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-bold text-blue-700 ring-1 ring-blue-100">{{ $submitCount }} project(s)</span>
                    </div>
                    <div class="space-y-3 max-h-60 overflow-y-auto">
                        @foreach ($submitProjects as $project)
                        <a href="{{ route('projects.edit', $project['project_id']) }}" wire:navigate class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-100 transition hover:bg-slate-100 sm:flex-nowrap">
                            <div class="min-w-0 flex-1">
                                <p class="break-words font-medium text-slate-900" title="{{ $project['kode'] }}">{{ $project['kode'] }}</p>
                                <p class="break-words text-xs leading-4 text-slate-500" title="{{ $project['nama'] }}">{{ $project['nama'] }}</p>
                            </div>
                            <div class="flex min-w-0 max-w-full flex-wrap items-center gap-2 sm:shrink-0">
                                @if ($project['division'])
                                <span class="text-xs text-slate-500 px-2 py-0.5 rounded bg-white">{{ $project['division'] }}</span>
                                @endif
                                <x-status-badge :text="$project['status']" tone="blue" />
                                <p class="whitespace-nowrap text-sm font-semibold tabular-nums text-slate-700" title="{{ format_idr($project['nilai_total']) }}">{{ format_idr($project['nilai_total']) }}</p>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    @else
                    <x-empty-state icon="paper-plane" title="No draft projects" description="All projects have been submitted." />
                    @endif
                </div>
            </div>

            {{-- Projects for Revision --}}
            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="arrow-path" class="h-5 w-5 text-amber-600" /> Projects for Revision</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Projects needing revision</p>
                    </div>
                    <a href="{{ route('projects.index', ['status' => 'revisi']) }}" wire:navigate class="text-xs font-semibold text-amber-600 hover:text-amber-700">View all →</a>
                </div>
                <div class="p-5">
                    @php $revisiCount = $this->submitRevisiCounts['revisi_count'] ?? 0; $revisiProjects = $this->projectsToRevisi; @endphp
                    @if ($revisiCount > 0)
                    <div class="mb-4 flex items-center justify-between">
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-bold text-amber-700 ring-1 ring-amber-100">{{ $revisiCount }} project(s)</span>
                    </div>
                    <div class="space-y-3 max-h-60 overflow-y-auto">
                        @foreach ($revisiProjects as $project)
                        <a href="{{ route('projects.edit', $project['project_id']) }}" wire:navigate class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-100 transition hover:bg-slate-100 sm:flex-nowrap">
                            <div class="min-w-0 flex-1">
                                <p class="break-words font-medium text-slate-900" title="{{ $project['kode'] }}">{{ $project['kode'] }}</p>
                                <p class="break-words text-xs leading-4 text-slate-500" title="{{ $project['nama'] }}">{{ $project['nama'] }}</p>
                            </div>
                            <div class="flex min-w-0 max-w-full flex-wrap items-center gap-2 sm:shrink-0">
                                @if ($project['division'])
                                <span class="text-xs text-slate-500 px-2 py-0.5 rounded bg-white">{{ $project['division'] }}</span>
                                @endif
                                <x-status-badge :text="in_array($project['status'], ['revisi', 'Revisi'], true) ? 'Revision' : $project['status']" tone="amber" />
                                <p class="whitespace-nowrap text-sm font-semibold tabular-nums text-slate-700" title="{{ format_idr($project['nilai_total']) }}">{{ format_idr($project['nilai_total']) }}</p>
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
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Budget Utilization --}}
            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="chart-pie" class="h-5 w-5 text-brand-600" /> Budget Utilization</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Budget used against approved budget</p>
                    </div>
                    <span class="rounded-full bg-brand-50 px-3 py-1 text-sm font-bold text-brand-700 ring-1 ring-brand-100">{{ $usagePercent }}%</span>
                </div>
                <div class="space-y-4 p-5">
                    <x-progress-bar :value="$usagePercent" tone="brand" />
                    <div class="grid grid-cols-2 gap-3">
                        <div class="min-w-0 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200/60">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 truncate">Budget Used</p>
                            <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-slate-900" title="{{ format_idr($totalRealisasi) }}">{{ format_idr($totalRealisasi) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 truncate">{{ $usagePercent }}% of budget</p>
                        </div>
                        <div class="min-w-0 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200/60">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 truncate">Remaining Budget</p>
                            <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-slate-900" title="{{ format_idr($totalSisa) }}">{{ format_idr($totalSisa) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 truncate">{{ round(100 - $usagePercent, 1) }}% remaining</p>
                        </div>
                    </div>
                    <div class="grid gap-1 rounded-lg bg-slate-50/80 px-3 py-2 text-xs ring-1 ring-slate-100 sm:grid-cols-[auto_1fr] sm:items-center sm:gap-3">
                        <span class="text-slate-600">Approved Allocation</span>
                        <span class="flex flex-wrap gap-x-2 font-semibold leading-5 text-slate-800 sm:justify-end" title="Project: {{ format_idr($stats['project_allocation'] ?? 0) }} | Non-project: {{ format_idr($stats['non_project_allocation'] ?? 0) }}">
                            <span class="whitespace-nowrap">Project: {{ format_idr($stats['project_allocation'] ?? 0) }}</span>
                            <span class="whitespace-nowrap">Non-project: {{ format_idr($stats['non_project_allocation'] ?? 0) }}</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Cashflow Summary --}}
            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                            <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="coins" class="h-5 w-5 text-brand-600" /> Cashflow Summary</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Cash basis - opening, income, expense, and closing balance</p>
                    </div>
                    <a href="{{ route('cashflows.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all →</a>
                </div>
                <div class="space-y-4 p-5">
                    <div class="grid grid-cols-2 gap-3 2xl:grid-cols-4">
                        <div class="min-w-0 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 truncate">Opening</p>
                            <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-slate-800" title="{{ format_idr($stats['opening_balance'] ?? 0) }}">{{ format_idr($stats['opening_balance'] ?? 0) }}</p>
                        </div>
                        <div class="min-w-0 rounded-lg bg-emerald-50 p-3 ring-1 ring-emerald-100">
                            <p class="flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wider text-emerald-700 truncate"><x-icon name="arrow-up-right" class="h-3.5 w-3.5 shrink-0" /> Cash In</p>
                            <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-emerald-800" title="{{ format_idr($stats['cash_in'] ?? 0) }}">{{ format_idr($stats['cash_in'] ?? 0) }}</p>
                        </div>
                        <div class="min-w-0 rounded-lg bg-red-50 p-3 ring-1 ring-red-100">
                            <p class="flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wider text-red-700 truncate"><x-icon name="arrow-down-right" class="h-3.5 w-3.5 shrink-0" /> Cash Out</p>
                            <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-red-800" title="{{ format_idr($stats['cash_out'] ?? 0) }}">{{ format_idr($stats['cash_out'] ?? 0) }}</p>
                        </div>
                        <div class="min-w-0 rounded-lg bg-brand-50 p-3 ring-1 ring-brand-100">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700 truncate">Closing Balance</p>
                            @if (!empty($stats['cash_accounts']))
                            <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-brand-800" title="{{ format_idr($stats['current_balance'] ?? 0) }}">{{ format_idr($stats['current_balance'] ?? 0) }}</p>
                            @else
                            <p class="mt-1 text-sm font-semibold leading-tight text-amber-700" title="Add an active cash account to calculate this balance">Unavailable</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        @php $cashMax = max(1, ($stats['cash_in'] ?? 0) + ($stats['cash_out'] ?? 0)); @endphp
                        <div class="h-full bg-emerald-500" style="width: {{ (($stats['cash_in'] ?? 0) / $cashMax) * 100 }}%"></div>
                        <div class="h-full bg-red-500" style="width: {{ (($stats['cash_out'] ?? 0) / $cashMax) * 100 }}%"></div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                        <span>Cash In vs. Cash Out</span>
                        <span class="text-right font-semibold text-slate-700">Net Cashflow: <span class="whitespace-nowrap" title="{{ format_idr($stats['saldo_kas'] ?? 0) }}">{{ format_idr($stats['saldo_kas'] ?? 0) }}</span></span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ SECTION 4: PROJECT OVERVIEW + RECENT ACTIVITY ============ --}}
        <section class="grid grid-cols-1 gap-4 2xl:grid-cols-3">
            {{-- Project Overview --}}
            <div class="app-card overflow-hidden 2xl:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="briefcase" class="h-5 w-5 text-brand-600" /> Project Overview</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Budget, actuals, variance, and progress by project (accrual basis). Select a row to view details.</p>
                    </div>
                    <a href="{{ route('projects.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all →</a>
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
                            $pBudget = (float) ($project['budget'] ?? 0);
                            $pActual = (float) $project['realisasi'];
                            $pVariance = $pBudget - $pActual;
                            $pPct = $pBudget > 0 ? min(100, round($pActual / $pBudget * 100, 1)) : 0;
                            $status = \App\Models\Project::find($project['project_id'])?->status?->label() ?? 'Active';
                            $status = $status === 'Revisi' ? 'Revision' : $status;
                            @endphp
                            <tr class="cursor-pointer" wire:click="showProjectDetail({{ $project['project_id'] }})">
                                <td>
                                    <p class="font-medium text-slate-900">{{ $project['kode'] }}</p>
                                    <p class="max-w-[14rem] break-words text-xs text-slate-500" title="{{ $project['nama'] }}">{{ $project['nama'] }}</p>
                                </td>
                                <td class="whitespace-nowrap text-right tabular-nums text-slate-700">{{ format_idr($pBudget) }}</td>
                                <td class="whitespace-nowrap text-right tabular-nums text-slate-700">{{ format_idr($pActual) }}</td>
                                <td class="whitespace-nowrap text-right tabular-nums font-medium {{ $pVariance >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ format_idr($pVariance) }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1"><x-progress-bar :value="$pPct" :tone="$pVariance >= 0 ? 'emerald' : 'red'" /></div>
                                        <span class="text-xs font-semibold tabular-nums text-slate-600">{{ number_format($pPct, 1) }}%</span>
                                    </div>
                                </td>
                                <td><x-status-badge :text="$status" tone="blue" /></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6"><x-empty-state icon="briefcase" title="No projects yet" description="Add a project to start monitoring budget and actuals." /></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="history" class="h-5 w-5 text-brand-600" /> Recent Activity</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Latest actions in the workspace</p>
                    </div>
                    <a href="{{ route('audit-log.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all →</a>
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
                            <p class="break-words text-sm text-slate-700" title="{{ $activity->description ?: ucfirst($activity->action) . ' ' . class_basename($activity->subject_type) }}">{{ $activity->description ?: ucfirst($activity->action) . ' ' . class_basename($activity->subject_type) }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $activity->user?->name ?? 'System' }} · {{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @empty
                    <x-empty-state icon="history" title="No activity yet" description="Actions will appear here as you use the workspace." />
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ============ SECTION 4B: NON-PROJECT BUDGETING & OVERHEAD ============ --}}
        @if (!empty($stats['non_project_allocations']) || ($stats['non_project_budget'] ?? 0) > 0)
        <div class="app-card overflow-hidden">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="flex items-center gap-2 font-semibold text-slate-900">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-purple-100 text-purple-700 text-xs font-bold">NP</span>
                        Non-Project Budget & Allocations
                    </h3>
                    <p class="mt-0.5 text-sm text-slate-500">Operating budget, office overhead, supplier payables, and non-project receivables</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="whitespace-nowrap rounded-full bg-purple-50 px-3 py-1 text-xs font-semibold text-purple-700 ring-1 ring-purple-100" title="Total Allocation {{ format_idr($stats['non_project_allocation'] ?? 0) }}">
                        Total Allocation {{ format_idr($stats['non_project_allocation'] ?? 0) }}
                    </span>
                    <a href="{{ route('budgeting.index', ['projectId' => 'non-project']) }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View All Non-Project →</a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table min-w-full">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Description / Party</th>
                            <th class="text-right">Budget</th>
                            <th class="text-right">Approved Allocation</th>
                            <th class="text-right">Actual</th>
                            <th class="text-right">Remaining Allocation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stats['non_project_allocations'] ?? [] as $np)
                        <tr>
                            <td class="font-medium text-slate-900">{{ $np['akun_kode'] }} - {{ $np['akun_nama'] }}</td>
                            <td>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ match ($np['type']) {
                                        'ap' => 'bg-red-100 text-red-700',
                                        'ar' => 'bg-green-100 text-green-700',
                                        'other_income' => 'bg-blue-100 text-blue-700',
                                        'other_outcome' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    } }}">
                                    {{ match ($np['type']) { 'ap' => 'Payable', 'ar' => 'Receivable', 'other_income' => 'Other Income', 'other_outcome' => 'Other Expense', default => $np['type_label'] } }}
                                </span>
                            </td>
                            <td class="text-slate-700">{{ $np['display_name'] ?: '—' }}</td>
                            <td class="whitespace-nowrap text-right tabular-nums text-slate-700">{{ format_idr($np['budget']) }}</td>
                            <td class="whitespace-nowrap text-right tabular-nums font-semibold text-slate-900">{{ format_idr($np['allocation']) }}</td>
                            <td class="whitespace-nowrap text-right tabular-nums text-slate-700">{{ format_idr($np['realisasi']) }}</td>
                            <td class="whitespace-nowrap text-right tabular-nums font-medium {{ $np['sisa'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ format_idr($np['sisa']) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-xs text-slate-400">No approved non-project allocations yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Actual per Category (kept feature) --}}
        <div class="app-card overflow-hidden">
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="tag" class="h-5 w-5 text-brand-600" /> Actual per Category</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Actuals by budgeting category. Select a row to view transactions.</p>
                </div>
                <span class="whitespace-nowrap rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100" title="Total {{ format_idr($totalRealisasi) }}">Total {{ format_idr($totalRealisasi) }}</span>
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
                        <tr @class(['cursor-pointer' => $nama !== 'Uncategorized']) @if ($nama !== 'Uncategorized') wire:click="showCategoryDetail('{{ addslashes($nama) }}')" @endif>
                            <td class="font-medium text-slate-900">{{ $nama }}</td>
                            <td class="whitespace-nowrap text-right tabular-nums text-slate-700">{{ format_idr($total) }}</td>
                            <td class="text-right tabular-nums font-medium text-slate-600">{{ number_format($percent, 1) }}%</td>
                            <td>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-{{ $hue }}-500 transition-all duration-700" style="width: {{ $percent }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============ SECTION 6: PAYMENT + AR/AP ============ --}}
        <div class="space-y-4">
            {{-- Top Row: Cash Activity & Receivable (2 Columns on lg) --}}
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                {{-- Cash Activity --}}
                <div class="app-card overflow-hidden">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="wallet" class="h-5 w-5 text-brand-600" /> Cash Accounts & Actions</h3>
                            <p class="mt-0.5 text-sm text-slate-500">Account balances and cash management shortcuts</p>
                        </div>
                        <a href="{{ route('cashflows.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all &rarr;</a>
                    </div>
                    <div class="space-y-4 p-5">
                        {{-- Mini Account Balances --}}
                        @if (!empty($stats['cash_accounts']))
                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5">
                            <div class="mb-2 flex items-center justify-between text-xs font-semibold text-slate-600">
                                <span>Cash and bank balances by account</span>
                                <a href="{{ route('cashflows.index') }}" wire:navigate class="text-brand-600 hover:text-brand-700 text-[11px]">View report details &rarr;</a>
                            </div>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 max-h-36 overflow-y-auto pr-1">
                                @foreach ($stats['cash_accounts'] as $acc)
                                <div class="flex items-center justify-between rounded-lg bg-white p-2.5 shadow-sm ring-1 ring-slate-100">
                                    <div class="min-w-0 flex-1 pr-2">
                                        <p class="break-words text-xs font-semibold text-slate-800" title="{{ $acc['kode'] }} - {{ $acc['nama'] }}">{{ $acc['kode'] }} - {{ $acc['nama'] }}</p>
                                        <p class="text-[10px] text-slate-400">{{ str_replace('Kas', 'Cash', $acc['jenis']) }}</p>
                                    </div>
                                    <p class="shrink-0 whitespace-nowrap text-[clamp(0.6rem,0.8vw,0.75rem)] font-bold tabular-nums text-slate-900" title="{{ format_idr($acc['saldo']) }}">{{ format_idr($acc['saldo']) }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @else
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-sm text-amber-900">
                            <p class="font-semibold">No active cash accounts</p>
                            <p class="mt-1 text-xs text-amber-800">Closing balance is unavailable. Net cashflow below still reflects posted cash transactions.</p>
                            <a href="{{ route('cash-accounts.index') }}" wire:navigate class="mt-2 inline-flex text-xs font-semibold text-amber-900 underline">Review cash accounts</a>
                        </div>
                        @endif

                        {{-- Quick Actions --}}
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <a href="{{ route('cashflows.create', ['mode' => 'masuk']) }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700">
                                + Add Cash In
                            </a>
                            <a href="{{ route('cashflows.create', ['mode' => 'keluar']) }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-700">
                                + Add Cash Out
                            </a>
                            @if (auth()->user()->isAdmin())
                            <a href="{{ route('fund-transfers.create') }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-slate-800">
                                <x-icon name="arrow-path" class="h-3.5 w-3.5" /> Transfer Funds
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Receivable (AR) Breakdown --}}
                <div class="app-card overflow-hidden">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="receipt" class="h-5 w-5 text-brand-600" /> Receivable (AR) Breakdown</h3>
                            <p class="mt-0.5 text-sm text-slate-500">Outstanding AR by category</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-4">
                        {{-- Billed --}}
                        <a href="{{ route('receivables.index', ['ar_category' => 'billed']) }}" wire:navigate class="min-w-0 rounded-lg bg-emerald-50 p-3 ring-1 ring-emerald-100 transition hover:bg-emerald-100/70">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700 truncate">Billed</p>
                            <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-emerald-800" title="{{ format_idr($stats['ar_breakdown']['billed']['outstanding'] ?? 0) }}">{{ format_idr($stats['ar_breakdown']['billed']['outstanding'] ?? 0) }}</p>
                            <p class="mt-0.5 text-xs text-emerald-600 truncate">{{ $stats['ar_breakdown']['billed']['count'] ?? 0 }} invoices</p>
                        </a>
                        {{-- Unbilled --}}
                        <a href="{{ route('receivables.index', ['ar_category' => 'unbilled']) }}" wire:navigate class="min-w-0 rounded-lg bg-amber-50 p-3 ring-1 ring-amber-100 transition hover:bg-amber-100/70">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-700 truncate">Unbilled</p>
                            <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-amber-800" title="{{ format_idr($stats['ar_breakdown']['unbilled']['outstanding'] ?? 0) }}">{{ format_idr($stats['ar_breakdown']['unbilled']['outstanding'] ?? 0) }}</p>
                            <p class="mt-0.5 text-xs text-amber-600 truncate">{{ $stats['ar_breakdown']['unbilled']['count'] ?? 0 }} invoices</p>
                        </a>
                        {{-- In Progress --}}
                        <a href="{{ route('receivables.index', ['ar_category' => 'inprogress']) }}" wire:navigate class="min-w-0 rounded-lg bg-blue-50 p-3 ring-1 ring-blue-100 transition hover:bg-blue-100/70">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-blue-700 truncate">In Progress</p>
                            <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-blue-800" title="{{ format_idr($stats['ar_breakdown']['inprogress']['outstanding'] ?? 0) }}">{{ format_idr($stats['ar_breakdown']['inprogress']['outstanding'] ?? 0) }}</p>
                            <p class="mt-0.5 text-xs text-blue-600 truncate">{{ $stats['ar_breakdown']['inprogress']['count'] ?? 0 }} invoices</p>
                        </a>
                        {{-- Grand Total --}}
                        <a href="{{ route('receivables.index') }}" wire:navigate class="min-w-0 rounded-lg bg-brand-50 p-3 ring-1 ring-brand-100 transition hover:bg-brand-100/70">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700 truncate">Total AR</p>
                            <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-brand-800" title="{{ format_idr($stats['ar_breakdown']['total']['outstanding'] ?? 0) }}">{{ format_idr($stats['ar_breakdown']['total']['outstanding'] ?? 0) }}</p>
                            <p class="mt-0.5 text-xs text-brand-600 truncate">{{ $stats['ar_breakdown']['total']['count'] ?? 0 }} invoices</p>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Bottom Row: Payable (AP) Breakdown by Type (Full Width) --}}
            <div class="app-card overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="credit-card" class="h-5 w-5 text-red-600" /> Payable (AP) Breakdown</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Outstanding AP by party type</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-3 lg:grid-cols-5">
                    {{-- Vendor --}}
                    @php $vendor = $stats['ap_breakdown']['VENDOR'] ?? ['outstanding' => 0, 'count' => 0, 'label' => 'Vendor']; @endphp
                    <a href="{{ route('payables.index', ['party_type' => 'VENDOR']) }}" wire:navigate class="min-w-0 rounded-lg bg-red-50 p-3 sm:p-4 ring-1 ring-red-100 transition hover:bg-red-100/70">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-red-700 truncate">{{ $vendor['label'] }}</p>
                        <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-red-800" title="{{ format_idr($vendor['outstanding'] ?? 0) }}">{{ format_idr($vendor['outstanding'] ?? 0) }}</p>
                        <p class="mt-0.5 text-xs text-red-600 truncate">{{ $vendor['count'] ?? 0 }} payables</p>
                    </a>
                    {{-- Supplier --}}
                    @php $supplier = $stats['ap_breakdown']['SUPPLIER'] ?? ['outstanding' => 0, 'count' => 0, 'label' => 'Supplier']; @endphp
                    <a href="{{ route('payables.index', ['party_type' => 'SUPPLIER']) }}" wire:navigate class="min-w-0 rounded-lg bg-amber-50 p-3 sm:p-4 ring-1 ring-amber-100 transition hover:bg-amber-100/70">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-700 truncate">{{ $supplier['label'] }}</p>
                        <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-amber-800" title="{{ format_idr($supplier['outstanding'] ?? 0) }}">{{ format_idr($supplier['outstanding'] ?? 0) }}</p>
                        <p class="mt-0.5 text-xs text-amber-600 truncate">{{ $supplier['count'] ?? 0 }} payables</p>
                    </a>
                    {{-- Mandor --}}
                    @php $mandor = $stats['ap_breakdown']['MANDOR'] ?? ['outstanding' => 0, 'count' => 0, 'label' => 'Mandor']; @endphp
                    <a href="{{ route('payables.index', ['party_type' => 'MANDOR']) }}" wire:navigate class="min-w-0 rounded-lg bg-sky-50 p-3 sm:p-4 ring-1 ring-sky-100 transition hover:bg-sky-100/70">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-sky-700 truncate">{{ str_replace('Mandor', 'Foreman', $mandor['label']) }}</p>
                        <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-sky-800" title="{{ format_idr($mandor['outstanding'] ?? 0) }}">{{ format_idr($mandor['outstanding'] ?? 0) }}</p>
                        <p class="mt-0.5 text-xs text-sky-600 truncate">{{ $mandor['count'] ?? 0 }} payables</p>
                    </a>
                    {{-- Investor --}}
                    @php $investor = $stats['ap_breakdown']['INVESTOR'] ?? ['outstanding' => 0, 'count' => 0, 'label' => 'Investor']; @endphp
                    <a href="{{ route('payables.index', ['party_type' => 'INVESTOR']) }}" wire:navigate class="min-w-0 rounded-lg bg-purple-50 p-3 sm:p-4 ring-1 ring-purple-100 transition hover:bg-purple-100/70">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-purple-700 truncate">{{ $investor['label'] }}</p>
                        <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-purple-800" title="{{ format_idr($investor['outstanding'] ?? 0) }}">{{ format_idr($investor['outstanding'] ?? 0) }}</p>
                        <p class="mt-0.5 text-xs text-purple-600 truncate">{{ $investor['count'] ?? 0 }} payables</p>
                    </a>
                    {{-- Grand Total --}}
                    @php $total = $stats['ap_breakdown']['total'] ?? ['outstanding' => 0, 'count' => 0, 'label' => 'Total']; @endphp
                    <a href="{{ route('payables.index') }}" wire:navigate class="col-span-2 sm:col-span-1 min-w-0 rounded-lg bg-brand-50 p-3 sm:p-4 ring-1 ring-brand-100 transition hover:bg-brand-100/70">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700 truncate">{{ $total['label'] }}</p>
                        <p class="mt-1 whitespace-nowrap text-[clamp(0.625rem,0.9vw,0.875rem)] font-bold leading-tight tabular-nums tracking-tight text-brand-800" title="{{ format_idr($total['outstanding'] ?? 0) }}">{{ format_idr($total['outstanding'] ?? 0) }}</p>
                        <p class="mt-0.5 text-xs text-brand-600 truncate">{{ $total['count'] ?? 0 }} payables</p>
                    </a>
                </div>
            </div>
        </div>
        @endunless
    </div>

    {{-- ============ PROJECT DETAIL MODAL ============ --}}
    @if ($this->selectedProjectId && $this->selectedProject)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50" wire:click="closeDetails"></div>
        <div class="relative z-10 w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-card-hover">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-600">Project Detail</p>
                    <h3 class="mt-0.5 break-words font-semibold text-slate-900">{{ $this->selectedProject->kode }} &mdash; {{ $this->selectedProject->nama }}</h3>
                </div>
                <button type="button" wire:click="closeDetails" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100"><x-icon name="x-mark" class="h-5 w-5" /></button>
            </div>
            <div class="grid grid-cols-1 gap-3 border-b border-slate-100 bg-slate-50/60 px-6 py-4 sm:grid-cols-2 2xl:grid-cols-4">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Contract</p>
                    <p class="mt-1 whitespace-nowrap text-[clamp(0.65rem,1vw,1rem)] font-semibold text-slate-900">{{ format_idr($this->selectedProject->nilai_total) }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Budget</p>
                    <p class="mt-1 whitespace-nowrap text-[clamp(0.65rem,1vw,1rem)] font-semibold text-slate-900">{{ format_idr($this->selectedProject->budget_total ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Actual</p>
                    <p class="mt-1 whitespace-nowrap text-[clamp(0.65rem,1vw,1rem)] font-semibold text-slate-900">{{ format_idr($this->projectRealisations->sum('nominal')) }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Transactions</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $this->projectRealisations->count() }}</p>
                </div>
            </div>
            <div class="max-h-[60vh] overflow-y-auto">
                <table class="data-table min-w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Category</th>
                            <th>Party</th>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->projectRealisations as $realisasi)
                        <tr>
                            <td class="whitespace-nowrap text-slate-700">{{ $realisasi->tanggal->format('d M Y') }}</td>
                            <td class="text-slate-700">{{ $realisasi->akun->kode_akun }} - {{ $realisasi->akun->nama_akun }}</td>
                            <td>@if ($realisasi->kategori)<x-status-badge :text="$realisasi->kategori->nama" tone="blue" />@else <span class="text-slate-400">-</span> @endif</td>
                            <td class="text-slate-700">{{ $realisasi->pihak ?: '-' }}</td>
                            <td class="text-slate-500">{{ $realisasi->keterangan }}</td>
                            <td class="whitespace-nowrap text-right font-medium text-slate-900">{{ format_idr($realisasi->nominal) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6"><x-empty-state icon="document" title="No transactions" description="No actuals have been recorded for this project." /></td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5">Total</td>
                            <td class="whitespace-nowrap text-right">{{ format_idr($this->projectRealisations->sum('nominal')) }}</td>
                        </tr>
                    </tfoot>
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
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-600">Category Detail</p>
                    <h3 class="mt-0.5 font-semibold text-slate-900">{{ $this->selectedCategoryName }}</h3>
                    <p class="mt-1 text-xs text-slate-500">Filter: {{ $this->startDate ?? '...' }} &rarr; {{ $this->endDate ?? '...' }}</p>
                </div>
                <button type="button" wire:click="closeDetails" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100"><x-icon name="x-mark" class="h-5 w-5" /></button>
            </div>
            <div class="max-h-[60vh] overflow-y-auto">
                <table class="data-table min-w-full">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Project</th>
                            <th>Account</th>
                            <th>Party</th>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->categoryRealisations as $realisasi)
                        <tr>
                            <td class="whitespace-nowrap text-slate-700">{{ $realisasi->tanggal->format('d M Y') }}</td>
                            <td class="text-slate-700">{{ $realisasi->project->kode }} - {{ $realisasi->project->nama }}</td>
                            <td class="text-slate-700">{{ $realisasi->akun->kode_akun }} - {{ $realisasi->akun->nama_akun }}</td>
                            <td class="text-slate-700">{{ $realisasi->pihak ?: '-' }}</td>
                            <td class="text-slate-500">{{ $realisasi->keterangan }}</td>
                            <td class="whitespace-nowrap text-right font-medium text-slate-900">{{ format_idr($realisasi->nominal) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6"><x-empty-state icon="document" title="No transactions" description="No actuals have been recorded for this project." /></td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5">Total</td>
                            <td class="whitespace-nowrap text-right">{{ format_idr($this->categoryRealisations->sum('nominal')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
