<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Add Budget Plan') }}</h2>

        <div class="mt-6 max-w-4xl bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <form wire:submit="save" class="p-6">
                @include('livewire.budget-plans._form')

                <div class="mt-6 flex items-center gap-4">
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                    <a href="{{ route('budget-plans.index') }}" wire:navigate class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
