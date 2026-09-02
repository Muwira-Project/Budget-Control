@php
    $titles = [
        'akuns' => 'Export Account',
        'realisasi' => 'Export Actual',
        'vs' => 'Export Account Detail',
        'receivables' => 'Export Receivables (AR)',
        'payables' => 'Export Payables (AP)',
        'cashflows' => 'Export Cash In/Out',
    ];
    $columns = [
        'akuns' => 'Account Code, Account Name, Type, Category',
        'realisasi' => 'Project, Account, Category, Date, Vendor, Amount, Description',
        'vs' => 'Account Code, Account Name, Budget, Allocation, Total Actual, Remaining (Variance), Percentage (%)',
        'receivables' => 'Project Code, Project Name, PO Number, Invoice No., Date, Due Date, Party Type, Party, Amount, Paid, Outstanding, Status, Description',
        'payables' => 'Project Code, Project Name, Invoice No., Date, Due Date, Party Type, Party, Amount, Paid, Outstanding, Status, Description',
        'cashflows' => 'Date, Type, Source, Cash Account, Account (COA), Project, Party Type, Party, Amount, Description, Status, Created By',
    ];
@endphp

<div class="py-12">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ $titles[$type] }}</h2>

        <div class="mt-6 rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800">Report Filters</h3>
                <p class="mt-1 text-sm text-gray-500">Column laporan: <span class="font-medium text-gray-700">{{ $columns[$type] }}</span></p>
            </div>

            <div class="p-6">
                {{-- Common filters for all types except akuns --}}
                @if ($type !== 'akuns')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="project_id" :value="__('Project')" />
                        <select id="project_id" wire:model.live="projectId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Projects</option>
                            @foreach ($this->projects as $project)
                                <option value="{{ $project->id }}">{{ $project->kode }} - {{ $project->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date range --}}
                    <div>
                        <x-input-label for="start_date" :value="__('Start Date')" />
                        <x-text-input id="start_date" class="mt-1 block w-full" type="date" wire:model="startDate" />
                    </div>

                    <div>
                        <x-input-label for="end_date" :value="__('End Date')" />
                        <x-text-input id="end_date" class="mt-1 block w-full" type="date" wire:model="endDate" />
                    </div>

                    {{-- AR/AP specific filters --}}
                    @if (in_array($type, ['receivables', 'payables']))
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" wire:model.live="status" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @if ($type === 'receivables')
                                @foreach ($this->arStatuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            @else
                                @foreach ($this->apStatuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div>
                        <x-input-label for="aging" :value="__('Aging')" />
                        <select id="aging" wire:model.live="aging" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($this->agingBuckets as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- AR Category filter (only for receivables) --}}
                    @if ($type === 'receivables')
                    <div>
                        <x-input-label for="ar_category" :value="__('AR Category')" />
                        <select id="ar_category" wire:model.live="arCategory" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($this->arCategories as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="po_number" :value="__('PO Number')" />
                        <input id="po_number" type="text" wire:model.live="poNumber" placeholder="e.g. PO-2026-001" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    @endif

                    {{-- Amount range filter --}}
                    <div>
                        <x-input-label for="amount_min" :value="__('Min Amount')" />
                        <input id="amount_min" type="number" step="0.01" min="0" wire:model.live="amountMin" placeholder="e.g. 1000000" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>

                    <div>
                        <x-input-label for="amount_max" :value="__('Max Amount')" />
                        <input id="amount_max" type="number" step="0.01" min="0" wire:model.live="amountMax" placeholder="e.g. 100000000" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    @endif
                </div>
                @endif

                {{-- Cashflow specific filters --}}
                @if ($type === 'cashflows')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Date range --}}
                    <div>
                        <x-input-label for="start_date" :value="__('Start Date')" />
                        <x-text-input id="start_date" class="mt-1 block w-full" type="date" wire:model="startDate" />
                    </div>

                    <div>
                        <x-input-label for="end_date" :value="__('End Date')" />
                        <x-text-input id="end_date" class="mt-1 block w-full" type="date" wire:model="endDate" />
                    </div>

                    <div>
                        <x-input-label for="jenis" :value="__('Type')" />
                        <select id="jenis" wire:model.live="jenis" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($this->cashflowJenisOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="sumber" :value="__('Source')" />
                        <select id="sumber" wire:model.live="sumber" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($this->cashflowSumberOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="cash_account_id" :value="__('Cash Account')" />
                        <select id="cash_account_id" wire:model.live="cashAccountId" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Cash Accounts</option>
                            @foreach ($this->cashAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->kode }} - {{ $account->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Amount range filter --}}
                    <div>
                        <x-input-label for="amount_min" :value="__('Min Amount')" />
                        <input id="amount_min" type="number" step="0.01" min="0" wire:model.live="amountMin" placeholder="e.g. 1000000" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>

                    <div>
                        <x-input-label for="amount_max" :value="__('Max Amount')" />
                        <input id="amount_max" type="number" step="0.01" min="0" wire:model.live="amountMax" placeholder="e.g. 100000000" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                </div>
                @endif

                {{-- Export buttons --}}
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    @if ($type === 'monitoring-summary')
                        <a href="{{ $this->downloadUrl('xlsx') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Export Excel (.xlsx)
                        </a>
                        <a href="{{ $this->downloadUrl('csv') }}" class="inline-flex items-center justify-center rounded-lg border border-green-600 bg-white px-4 py-2 text-sm font-semibold text-green-600 shadow-sm hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            Export CSV
                        </a>
                        <a href="{{ $this->downloadUrl('pdf') }}" class="inline-flex items-center justify-center rounded-lg border border-blue-600 bg-white px-4 py-2 text-sm font-semibold text-blue-600 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Export PDF
                        </a>
                    @else
                        <a href="{{ $this->downloadUrl('xlsx') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Export Excel (.xlsx)
                        </a>
                        <a href="{{ $this->downloadUrl('csv') }}" class="inline-flex items-center justify-center rounded-lg border border-green-600 bg-white px-4 py-2 text-sm font-semibold text-green-600 shadow-sm hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            Export CSV
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>