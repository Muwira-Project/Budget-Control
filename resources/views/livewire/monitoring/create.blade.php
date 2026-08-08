<div class="py-12">
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('Add Monitoring Period') }}</h2>

        <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <form wire:submit="save" class="p-6 space-y-6">
                <div>
                    <x-input-label for="project_id" :value="__('Project (optional)')" />
                    <select id="project_id" wire:model="projectId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- All Projects (Global) --</option>
                        @foreach ($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->kode }} - {{ $project->nama }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="tanggal_mulai" :value="__('Start Date')" />
                        <x-text-input id="tanggal_mulai" class="mt-1 block w-full" type="date" wire:model="tanggalMulai" required />
                        <x-input-error :messages="$errors->get('tanggal_mulai')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="tanggal_selesai" :value="__('End Date')" />
                        <x-text-input id="tanggal_selesai" class="mt-1 block w-full" type="date" wire:model="tanggalSelesai" required />
                        <x-input-error :messages="$errors->get('tanggal_selesai')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                    <a href="{{ route('monitoring.index') }}" wire:navigate class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
