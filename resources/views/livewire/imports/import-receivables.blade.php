<div class="py-12">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Import Receivables (AR)') }}</h2>
            <a href="{{ route('imports.receivables.template') }}" class="inline-flex items-center justify-center rounded-lg border border-blue-600 bg-white px-4 py-2 text-sm font-semibold text-blue-600 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Download Template
            </a>
        </div>

        <div class="mt-6 rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
            <form wire:submit="import" class="p-6">
                <p class="text-sm text-gray-500">
                    Column template: <span class="font-medium text-gray-700">Project Code, Party Type Code, Party Name, Date, Invoice No., Due Date, Amount, Paid, Description</span>.
                    Party Type Code: <code class="bg-gray-100 px-1 rounded">vendor</code>, <code class="bg-gray-100 px-1 rounded">supplier</code>, <code class="bg-gray-100 px-1 rounded">mandor</code>, <code class="bg-gray-100 px-1 rounded">investor</code>.
                    Date format: YYYY-MM-DD. Invoice No. must be unique per project.
                </p>

                <input type="file" wire:model="file" accept=".xlsx,.xls,.csv" class="mt-4 block w-full text-sm text-gray-700 file:me-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100" />
                <x-input-error :messages="$errors->get('file')" class="mt-2" />

                <div class="mt-6">
                    <x-primary-button wire:loading.attr="disabled" wire:target="import">
                        <span wire:loading.remove wire:target="import">Import</span>
                        <span wire:loading wire:target="import">Processing...</span>
                    </x-primary-button>
                </div>
            </form>
        </div>

        @include('livewire.imports._report', ['report' => $this->report])
    </div>
</div>