<div class="max-w-3xl mx-auto space-y-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Company Settings</h1>
        <p class="mt-1 text-sm text-slate-500">Configure company name, logo, and login page illustration.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="save" class="space-y-6" enctype="multipart/form-data">
        {{-- Company Name --}}
        <div>
            <x-input-label for="companyName" :value="__('Company Name')" />
            <x-text-input wire:model="companyName" id="companyName" class="mt-1.5 block w-full" required autocomplete="off" placeholder="MyFinance" />
            <x-input-error :messages="$errors->get('companyName')" class="mt-2" />
        </div>

        {{-- Company Address --}}
        <div>
            <x-input-label for="companyAddress" :value="__('Company Address')" />
            <textarea wire:model="companyAddress" id="companyAddress" rows="3" class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 p-3 text-sm" placeholder="Jl. Sudirman No. 123, Jakarta"></textarea>
            <x-input-error :messages="$errors->get('companyAddress')" class="mt-2" />
        </div>

        {{-- Logo --}}
        <div>
            <x-input-label for="logo" :value="__('Company Logo')" />
            <p class="mt-1.5 text-sm text-slate-500">Recommended: square PNG/SVG, max 2MB. Used in sidebar & login header.</p>

            @if ($existingLogoUrl)
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ $existingLogoUrl }}" alt="Current logo" class="h-16 w-16 object-contain rounded-lg border border-slate-200" />
                    <span class="text-sm text-slate-500">Current logo (upload new to replace)</span>
                </div>
            @endif

            <div class="mt-2">
                <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100" />
            </div>
            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
        </div>

        {{-- Login Page Illustration --}}
        <div>
            <x-input-label for="loginIllustration" :value="__('Login Page Illustration')" />
            <p class="mt-1.5 text-sm text-slate-500">Upload your own image (JPG/PNG), max 4MB. Shown on left side of login page. Leave empty to keep current.</p>

            <div class="mt-3 flex flex-col gap-4 sm:flex-row">
                {{-- Upload --}}
                <div class="flex-1">
                    <input type="file" wire:model="loginIllustration" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100" />
                    <x-input-error :messages="$errors->get('loginIllustration')" class="mt-2" />
                </div>
            </div>

            {{-- Fit & Position --}}
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="illustrationFit" :value="__('Image Fit')" />
                    <select wire:model="illustrationFit" id="illustrationFit" class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="cover">Fill (cover) — crop to fill, may cut edges</option>
                        <option value="contain">Fit (contain) — show entire image, letterbox</option>
                        <option value="fill">Stretch (fill) — stretch to fill</option>
                    </select>
                    <x-input-error :messages="$errors->get('illustrationFit')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="illustrationPosition" :value="__('Image Position')" />
                    <select wire:model="illustrationPosition" id="illustrationPosition" class="mt-1.5 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="top">Top</option>
                        <option value="center">Center</option>
                        <option value="bottom">Bottom</option>
                    </select>
                    <x-input-error :messages="$errors->get('illustrationPosition')" class="mt-2" />
                </div>
            </div>

            {{-- Live Preview --}}
            @php
                $previewUrl = $loginIllustration
                    ? $loginIllustration->temporaryUrl()
                    : ($existingIllustrationUrl ?? null);
            @endphp
            @if ($previewUrl)
                <div class="mt-4">
                    <p class="text-sm font-medium text-slate-700 mb-2">Preview (exact fit/position as on login page):</p>
                    <div class="relative h-48 w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                        <img src="{{ $previewUrl }}" alt="Login illustration preview" class="h-full w-full"
                             style="object-fit: {{ $illustrationFit }}; object-position: {{ $illustrationPosition }};" />
                    </div>
                    <p class="mt-1 text-xs text-slate-400">
                        Current setting: {{ $illustrationFit }} / {{ $illustrationPosition }} — adjust and save to apply.
                    </p>
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
            <x-secondary-button type="button" wire:click="$reset('companyName', 'companyAddress', 'logo', 'loginIllustration')">
                {{ __('Reset') }}
            </x-secondary-button>
            <x-primary-button class="h-12">
                {{ __('Save Settings') }}
            </x-primary-button>
        </div>
    </form>
</div>