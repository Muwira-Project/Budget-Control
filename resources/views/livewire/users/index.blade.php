<div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ __('User Master') }}</h2>
            <a href="{{ route('users.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                + Add User
            </a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <x-confirm-modal message="Are you sure you want to delete this user?" confirm-label="Delete">
            <x-bulk-actions :paginator="$this->users" :selected-ids="$this->selectedIds" />
                <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-100 p-6">
                    <div class="max-w-sm">
                        <x-input-label for="search" :value="__('Search User')" />
                        <x-text-input id="search" class="mt-1 block w-full" type="text" wire:model.live.debounce.300ms="search" placeholder="Search name or email..." />
                    </div>
                </div>

                @if ($this->users->isEmpty())
                    <p class="p-6 text-sm text-gray-500">
                        {{ $this->search !== '' ? 'No users match your search.' : 'No users yet.' }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="w-8 px-6 py-3"><input type="checkbox" disabled class="rounded border-gray-300 text-blue-600 cursor-not-allowed" aria-hidden="true" /></th>
                                    <th class="px-6 py-3">Photo</th>
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Department</th>
                                    <th class="px-6 py-3">Join Date</th>
                                    <th class="px-6 py-3">Location</th>
                                    <th class="px-6 py-3">Email</th>
                                    <th class="px-6 py-3">Role</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($this->users as $user)
                                    <tr class="hover:bg-gray-50">
                                    <td class="w-8 px-6 py-4"><input type="checkbox" wire:click="toggleSelected({{ $user->id }})" @checked(in_array($user->id, $this->selectedIds, true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" /></td>
                                    
                                    <td class="px-6 py-4">
                                        @if ($user->foto)
                                            <img src="{{ asset('storage/'.ltrim($user->foto, '/')) }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover ring-1 ring-gray-200" />
                                        @else
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                                                {{ strtoupper(\Illuminate\Support\Str::of($user->name)->explode(' ')->take(2)->map(fn ($part) => $part[0] ?? '')->join('')) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $user->name }}
                                        @if ($user->id === auth()->id())
                                            <span class="ms-2 inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">You</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->departemen ?? '-' }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->tanggal_masuk?->format('d M Y') ?? '-' }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->lokasi ?? '-' }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->email }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $user->isAdmin() ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700' }}">
                                                {{ $user->role->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500">{{ $user->created_at->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <x-action-buttons :edit-href="route('users.edit', $user)" :delete-id="$user->id" :show-delete="$user->id !== auth()->id()" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-6 py-4">
                        <x-pagination-footer :paginator="$this->users" />
                    </div>
                @endif
            </div>
        </x-confirm-modal>
    </div>
</div>
