<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('AR & AP') }}</h2>
            <p class="mt-1 text-sm text-gray-500">Account Receivable (piutang project selesai) dan Account Payable (hutang) dalam satu menu.</p>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="mt-6 flex flex-wrap gap-2">
            @foreach ([
                'receivable' => 'Receivable',
                'payable' => 'Payable',
                'payment' => 'Settlement History',
            ] as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')"
                    class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition {{ $this->tab === $key ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="mt-6">
            @if ($this->tab === 'receivable')
                <livewire:receivables.index />
            @elseif ($this->tab === 'payable')
                <livewire:payables.index />
            @else
                <livewire:payments.index />
            @endif
        </div>
    </div>
</div>