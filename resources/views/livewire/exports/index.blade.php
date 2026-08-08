@php
    $titles = [
        'akuns' => 'Export Account',
        'realisasi' => 'Export Actual',
        'vs' => 'Export Account vs Actual',
    ];
    $columns = [
        'akuns' => 'Account Code, Account Name, Type, Category',
        'realisasi' => 'Project, Account, Category, Date, Vendor, Amount, Description',
        'vs' => 'Project, Account Code, Account Name, Budget, Allocation, Total Actual, Remaining (Variance), Percentage (%)',
    ];
@endphp

<div class="py-12">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ $titles[$type] }}</h2>

        <div class="mt-6 rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <div class="border-b border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800">Report Filters</h3>
                <p class="mt-1 text-sm text-gray-500">Column laporan: <span class="font-medium text-gray-700">{{ $columns[$type] }}</span></p>
            </div>

            <div class="p-6">
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

                    <div>
                        <x-input-label for="status" :value="__('Project Status')" />
                        <select id="status" wire:model.live="status" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="start_date" :value="__('Start Date')" />
                        <x-text-input id="start_date" class="mt-1 block w-full" type="date" wire:model="startDate" />
                    </div>

                    <div>
                        <x-input-label for="end_date" :value="__('End Date')" />
                        <x-text-input id="end_date" class="mt-1 block w-full" type="date" wire:model="endDate" />
                    </div>
                </div>
                @endif

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $this->downloadUrl('xlsx') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Export Excel (.xlsx)
                    </a>
                    <a href="{{ $this->downloadUrl('pdf') }}" class="inline-flex items-center justify-center rounded-lg border border-blue-600 bg-white px-4 py-2 text-sm font-semibold text-blue-600 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Export PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>