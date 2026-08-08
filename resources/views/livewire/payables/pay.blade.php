<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Payable Payment') }}</h2>

        <div class="mt-6 max-w-2xl bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <form wire:submit="save" class="p-6">
                <div class="mt-6 grid grid-cols-1 gap-6">
                    <div class="rounded-xl bg-gray-50 p-4 text-sm">
                        <p class="text-gray-500">Project: <span class="font-medium text-gray-900">{{ $this->payable->project->kode }} - {{ $this->payable->project->nama }}</span></p>
                        <p class="mt-1 text-gray-500">Party: <span class="font-medium text-gray-900">{{ $this->payable->pihak }}</span></p>
                        <p class="mt-1 text-gray-500">Amount Payable: <span class="font-medium text-gray-900">{{ format_idr($this->payable->nominal) }}</span></p>
                        <p class="mt-1 text-gray-500">Remaining: <span class="font-medium text-gray-900">{{ format_idr($this->payable->sisa) }}</span></p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="tanggal" :value="__('Date')" />
                            <x-text-input id="tanggal" class="mt-1 block w-full" type="date" wire:model="tanggal" required />
                            <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="nominal" :value="__('Amount (Rp)')" />
                            <x-text-input id="nominal" class="mt-1 block w-full" type="text" wire:model="nominal" placeholder="e.g. 25000000" required />
                            <x-input-error :messages="$errors->get('nominal')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="keterangan" :value="__('Description (optional)')" />
                        <textarea id="keterangan" wire:model="keterangan" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-4">
                    <x-primary-button>{{ __('Save Payment') }}</x-primary-button>
                    <a href="{{ route('payables.index') }}" wire:navigate class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
