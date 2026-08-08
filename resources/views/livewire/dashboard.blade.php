<div class="py-8 lg:py-10" wire:poll.45s>
@php
    $stats = $this->statistics;
    $invalid = $this->dateRangeInvalid;
    $chartMax = max(
        collect($stats['chart_budget_realisasi'] ?? [])->max(fn ($r) => max($r['budget'], $r['realisasi'])) ?? 0,
        1
    );
    $totalRealisasi = (int) ($stats['total_realisasi'] ?? 0);
    $usagePercent = $stats['total_budget'] > 0 ? min(100, round($stats['total_realisasi'] / $stats['total_budget'] * 100, 1)) : 0;
    $kategoriTotal = array_sum($stats['kategori_breakdown'] ?? []);
    $categoryHues = ['emerald','teal','cyan','sky','indigo','fuchsia'];
@endphp

<div x-data="{ tab: '' }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
    {{-- Header --}}
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Overview</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ __('Dashboard') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ __('Summary of projects, budget, actuals, and financial performance.') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 ring-1 ring-slate-200">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                {{ now()->translatedFormat('l, d F Y') }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 ring-1 ring-slate-200">
                <x-icon name="history" class="h-3.5 w-3.5 text-slate-400" />
                <span x-text="(new Date()).toLocaleTimeString('{{ app()->getLocale() === 'id' ? 'id-ID' : 'en-GB' }}', { hour: '2-digit', minute: '2-digit' })"></span>
            </span>
        </div>
    </header>

    {{-- Date Range Filter --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_8px_24px_rgb(15_23_42/0.04)]">
        <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-2 lg:max-w-2xl">
                <div>
                    <x-input-label for="start_date" :value="__('Start Date')" />
                    <x-text-input id="start_date" class="mt-1 block w-full" type="date" wire:model.live="startDate" />
                </div>
                <div>
                    <x-input-label for="end_date" :value="__('End Date')" />
                    <x-text-input id="end_date" class="mt-1 block w-full" type="date" wire:model.live="endDate" />
                </div>
            </div>
            @if ($this->startDate || $this->endDate)
                <div class="flex items-center gap-2 text-sm text-emerald-700">
                    <x-icon name="check" class="h-4 w-4" />
                    <span>{{ __('Filter active') }}: <strong>{{ $this->startDate ?? '...' }}</strong> → <strong>{{ $this->endDate ?? '...' }}</strong></span>
                </div>
            @endif
        </div>
        @if ($this->startDate || $this->endDate)
            <p class="border-t border-slate-100 bg-slate-50/60 px-5 py-2.5 text-xs text-slate-500">
                <x-icon name="scale" class="mb-0.5 mr-1 inline h-3.5 w-3.5" />
                {{ __('Total Actual, Remaining Budget, and Cash Balance are calculated based on the selected date range.') }}
            </p>
        @endif
    </div>

    @if ($invalid)
        <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50/80 p-5 text-sm text-red-700">
            <x-icon name="x-mark" class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <p class="font-semibold">{{ __('Invalid date range') }}</p>
                <p class="mt-1 text-red-600/90">{{ __('Start date must be before or equal to end date.') }}</p>
            </div>
        </div>
    @endif

    @unless ($invalid)
        {{-- KPI Cards --}}
        @php
            $kpis = [
                [
                    'label' => __('Total Projects'),
                    'value' => number_format($stats['total_projects']),
                    'hint' => __('Goods') . ' ' . $stats['projects_barang'] . ' · ' . __('Services') . ' ' . $stats['projects_jasa'],
                    'icon' => 'folder',
                    'tone' => 'emerald',
                ],
                [
                    'label' => __('Total Budget'),
                    'value' => format_idr($stats['total_budget']),
                    'hint' => __('Total approved budget'),
                    'icon' => 'banknotes',
                    'tone' => 'sky',
                ],
                [
                    'label' => __('Total Actual'),
                    'value' => format_idr($stats['total_realisasi']),
                    'hint' => __('Budget utilization') . ' ' . format_idr($stats['total_realisasi']),
                    'icon' => 'trending-up',
                    'tone' => 'amber',
                ],
                [
                    'label' => __('Remaining Budget'),
                    'value' => format_idr($stats['total_sisa']),
                    'hint' => $stats['total_sisa'] >= 0 ? __('Remaining budget') : __('Over budget'),
                    'icon' => 'wallet',
                    'tone' => $stats['total_sisa'] >= 0 ? 'emerald' : 'red',
                    'isOver' => $stats['total_sisa'] < 0,
                ],
                [
                    'label' => __('Cash Balance'),
                    'value' => format_idr($stats['saldo_kas']),
                    'hint' => __('In') . ' ' . format_idr($stats['cash_in']) . ' · ' . __('Out') . ' ' . format_idr($stats['cash_out']),
                    'icon' => 'credit-card',
                    'tone' => 'green',
                ],
                [
                    'label' => __('Outstanding AR'),
                    'value' => format_idr($stats['outstanding_ar']),
                    'hint' => __('Uncollected receivables'),
                    'icon' => 'banknotes',
                    'tone' => 'amber',
                ],
                [
                    'label' => __('Outstanding AP'),
                    'value' => format_idr($stats['outstanding_ap']),
                    'hint' => __('Unpaid payables'),
                    'icon' => 'receipt',
                    'tone' => 'red',
                ],
                [
                    'label' => __('Profit'),
                    'value' => format_idr($stats['total_profit'] ?? 0),
                    'hint' => __('Contract') . ' - ' . __('Actual'),
                    'icon' => 'scale',
                    'tone' => ($stats['total_profit'] ?? 0) >= 0 ? 'emerald' : 'red',
                ],
            ];
        @endphp

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($kpis as $i => $kpi)
                <article
                    class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_8px_24px_rgb(15_23_42/0.04)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_12px_32px_rgb(15_23_42/0.07)]"
                    style="animation: fadeUp 0.5s ease-out {{ $i * 0.05 }}s both;"
                >
                    <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-{{ $kpi['tone'] }}-100/60 blur-2xl"></div>
                    <div class="relative">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $kpi['label'] }}</p>
                                <p @class(['mt-2 truncate text-2xl font-bold tracking-tight sm:text-[1.65rem]', 'text-'.($kpi['isOver'] ?? false ? 'red-600' : $kpi['tone'].'-700')])>
                                    {{ $kpi['value'] }}
                                </p>
                                <p class="mt-2 truncate text-xs text-slate-500">{{ $kpi['hint'] }}</p>
                            </div>
                            <x-icon :name="$kpi['icon']" class="mt-1 h-8 w-8 text-{{ $kpi['tone'] }}-200" />
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        {{-- Top Projects by Approved Budget --}}
        @if (count($stats['chart_budget_realisasi']) > 0)
            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_8px_24px_rgb(15_23_42/0.04)]">
                <div class="border-b border-slate-100 p-5">
                    <h2 class="font-bold tracking-tight text-slate-900">{{ __('Top Projects by Budget') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Budget vs Actual comparison') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <div class="min-w-full p-5">
                        <div class="space-y-4">
                            @foreach ($stats['chart_budget_realisasi'] as $project)
                                <div>
                                    <div class="mb-1 flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <p class="truncate font-medium text-slate-900">{{ $project['kode'] }} - {{ $project['nama'] }}</p>
                                        </div>
                                        <div class="ml-2 flex items-center gap-2 text-xs font-semibold tabular-nums">
                                            <span class="text-emerald-700">{{ format_idr($project['budget']) }}</span>
                                            <span class="text-slate-400">/</span>
                                            <span class="text-amber-700">{{ format_idr($project['realisasi']) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full bg-emerald-500" style="width: {{ min(100, ($project['budget'] > 0 ? ($project['budget'] / $chartMax) * 100 : 0)) }}%"></div>
                                        <div class="h-full bg-amber-500" style="width: {{ min(100, ($project['realisasi'] > 0 ? ($project['realisasi'] / $chartMax) * 100 : 0)) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Profit by Project --}}
        @if (count($stats['profit_projects']) > 0)
            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_8px_24px_rgb(15_23_42/0.04)]">
                <div class="border-b border-slate-100 p-5">
                    <h2 class="font-bold tracking-tight text-slate-900">{{ __('Profit by Project') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Contract value minus actual costs') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50/80 text-[11px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold">{{ __('Project') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Contract Value') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Actual') }}</th>
                                <th class="px-5 py-3 text-right font-semibold">{{ __('Profit') }}</th>
                                <th class="px-5 py-3 text-center font-semibold">{{ __('Margin') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($stats['profit_projects'] as $project)
                                @php
                                    $margin = $project['nilai'] > 0 ? ($project['profit'] / $project['nilai']) * 100 : 0;
                                    $isPositive = $project['profit'] >= 0;
                                @endphp
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="px-5 py-3 font-medium text-slate-900">{{ $project['kode'] }} - {{ $project['nama'] }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-slate-700">{{ format_idr($project['nilai']) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-slate-700">{{ format_idr($project['realisasi']) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums font-semibold" :class="{'text-emerald-700': {{ $isPositive ? 'true' : 'false' }}, 'text-red-700': {{ $isPositive ? 'false' : 'true' }}}">
                                        {{ format_idr($project['profit']) }}
                                    </td>
                                    <td class="px-5 py-3 text-center tabular-nums font-medium" :class="{'text-emerald-700': {{ $isPositive ? 'true' : 'false' }}, 'text-red-700': {{ $isPositive ? 'false' : 'true' }}}">
                                        {{ number_format($margin, 1) }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">
                                        {{ __('No projects found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Actual per Category --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_8px_24px_rgb(15_23_42/0.04)]">
            <div class="flex flex-col gap-1 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold tracking-tight text-slate-900">{{ __('Actual per Category') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('6 budgeting categories per meeting notes') }}</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ __('Total') }} {{ format_idr($totalRealisasi) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50/80 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">{{ __('Category') }}</th>
                            <th class="px-5 py-3 text-right font-semibold">{{ __('Actual') }}</th>
                            <th class="px-5 py-3 text-right font-semibold">{{ __('Share') }}</th>
                            <th class="px-5 py-3 text-left font-semibold w-1/3">{{ __('Distribution') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($stats['kategori_breakdown'] as $nama => $total)
                            @php $percent = $kategoriTotal > 0 ? ($total / $kategoriTotal) * 100 : 0; $hue = $categoryHues[$loop->index % count($categoryHues)]; @endphp
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $nama }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-slate-700">{{ format_idr($total) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums font-medium text-slate-600">{{ number_format($percent, 1) }}%</td>
                                <td class="px-5 py-3">
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-{{ $hue }}-500 transition-all duration-700" style="width: {{ $percent }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Budget Usage Summary --}}
        <section class="overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 p-6 text-white shadow-[0_12px_32px_rgb(5_46_22/0.4)]">
            <div class="grid gap-6 md:grid-cols-2 md:items-center">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-100">{{ __('Budget Usage') }}</p>
                    <p class="mt-2 text-3xl font-bold leading-tight sm:text-4xl">{{ $usagePercent }}%</p>
                    <p class="mt-2 text-sm text-emerald-50/90">{{ format_idr($stats['total_realisasi']) }} of {{ format_idr($stats['total_budget']) }}</p>
                </div>
                <div>
                    <div class="flex h-3 w-full overflow-hidden rounded-full bg-white/20 ring-1 ring-white/30">
                        <div class="h-full rounded-full bg-white transition-all duration-700 ease-out" style="width: {{ $usagePercent }}%"></div>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-xs text-emerald-50/90">
                        <span>{{ $usagePercent }}%</span>
                        <span>{{ round(100 - $usagePercent, 1) }}% {{ __('remaining') }}</span>
                    </div>
                </div>
            </div>
        </section>
    @endunless
</div>

<style>
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
</div>
