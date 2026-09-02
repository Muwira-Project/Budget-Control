<div class="mt-6 grid grid-cols-1 gap-6">
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" class="mt-1 block w-full" type="text" wire:model="name" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="departemen" :value="__('Department')" />
        <x-text-input id="departemen" class="mt-1 block w-full" type="text" wire:model="departemen" />
        <x-input-error :messages="$errors->get('departemen')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="tanggal_masuk" :value="__('Join Date')" />
            <x-text-input id="tanggal_masuk" class="mt-1 block w-full" type="date" wire:model="tanggalMasuk" />
            <x-input-error :messages="$errors->get('tanggal_masuk')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="lokasi" :value="__('Location')" />
            <x-text-input id="lokasi" class="mt-1 block w-full" type="text" wire:model="lokasi" />
            <x-input-error :messages="$errors->get('lokasi')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input id="email" class="mt-1 block w-full" type="email" wire:model="email" required />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="foto" :value="__('Profile Photo')" />
        <input id="foto" class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700" type="file" wire:model="foto" accept="image/*" />
        <x-input-error :messages="$errors->get('foto')" class="mt-2" />

        @if (! empty($editMode) && ($user->foto ?? null))
            <p class="mt-2 text-xs text-gray-500">Current photo:</p>
            <img src="{{ asset('storage/'.ltrim($user->foto, '/')) }}" alt="{{ $user->name }}" class="mt-2 h-16 w-16 rounded-full object-cover ring-1 ring-gray-200" />
        @endif
    </div>

    <div>
        <x-input-label for="role" :value="__('Role')" />
        <select id="role" wire:model="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="staff">Staff (create drafts)</option>
            <option value="admin">Admin (approve)</option>
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" wire:model="password" autocomplete="new-password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            @if (! empty($editMode))
                <p class="mt-1 text-xs text-gray-500">Leave blank if you do not want to change the password.</p>
            @endif
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" wire:model="passwordConfirmation" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
    </div>
</div>
