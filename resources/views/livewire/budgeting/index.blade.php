<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Budgeting') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Budget Plan (admin) dan Allocation (staff & admin) dalam satu menu.</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($this->tab === 'allocation')
                    <a href="{{ route('allokasis.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        + Add Allocation
                    </a>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="mt-6 flex flex-wrap gap-2">
            <button type="button" wire:click="$set('tab', 'plan')"
                class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition {{ $this->tab === 'plan' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' }}">
                Budget Plan
            </button>
            <button type="button" wire:click="$set('tab', 'allocation')"
                class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition {{ $this->tab === 'allocation' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' }}">
                Allocation
            </button>
        </div>

        <div class="mt-6">
            @if ($this->tab === 'plan' && auth()->user()->isAdmin())
                <livewire:budget-plans.index />
            @elseif ($this->tab === 'plan')
                <div class="rounded-2xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-200">
                    <x-icon name="lock-closed" class="mx-auto h-10 w-10 text-gray-300" />
                    <p class="mt-3 text-sm font-medium text-gray-700">Budget Plan hanya untuk admin.</p>
                </div>
            @else
                <livewire:allokasis.index />
            @endif
        </div>
    </div>
</div>
