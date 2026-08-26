{{-- ============ PROJECT OVERVIEW (Full) ============ --}}
<div class="app-card overflow-hidden mb-6">
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
                @forelse (collect($stats['profit_projects'] ?? []) as $project)
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

{{-- ============ ACTUAL PER CATEGORY (Full) ============ --}}
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

{{-- ============ SUBMIT & REVISI PROJECTS ============ --}}
<section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
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