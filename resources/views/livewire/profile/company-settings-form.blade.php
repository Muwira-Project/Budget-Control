<?php

use App\Services\CompanySettingService;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?string $companyName = null;

    public ?string $companyAddress = null;

    #[Validate('nullable|image|mimes:jpeg,png,jpg,webp|max:2048', message: [
        'image' => 'The logo file must be an image.',
        'mimes' => 'The logo must be in JPG, PNG, or WEBP format.',
        'max' => 'The maximum logo size is 2 MB.',
    ])]
    public $logo;

    /**
     * Load the current company settings.
     */
    public function mount(CompanySettingService $service): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $settings = $service->get();

        $this->companyName = $settings->company_name;
        $this->companyAddress = $settings->company_address;
    }

    /**
     * Save the company settings.
     */
    public function save(CompanySettingService $service): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->validate();

        $service->update($service->get(), [
            'company_name' => $this->companyName,
            'company_address' => $this->companyAddress,
        ], $this->logo);

        session()->flash('company-status', 'Company settings saved successfully.');

        $this->reset('logo');
    }
};
?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Company Settings') }}</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Company name, address, and logo are used for the browser tab title, favicon, and app branding.') }}
        </p>
    </header>

    @if (session('company-status'))
        <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
            {{ session('company-status') }}
        </div>
    @endif

    <form wire:submit="save" class="mt-6 space-y-6">
        <div>
            <x-input-label for="company_name" :value="__('Company Name')" />
                        <x-text-input id="company_name" class="mt-1 block w-full" type="text" wire:model="companyName" placeholder="e.g. PT Maju Bersama" />
            <x-input-error :messages="$errors->get('companyName')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="company_address" :value="__('Company Address')" />
            <textarea id="company_address" wire:model="companyAddress" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Address lengkap perusahaan"></textarea>
            <x-input-error :messages="$errors->get('companyAddress')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="logo" :value="__('Company Logo (optional)')" />
            @php
                $companySettings = app(CompanySettingService::class)->get();
            @endphp
            @if ($companySettings->logo_url)
                <div class="mt-2">
                    <img src="{{ $companySettings->logo_url }}" alt="Company logo" class="h-16 w-16 rounded-lg object-contain ring-1 ring-gray-200" />
                </div>
            @endif
            <x-text-input id="logo" class="mt-2 block w-full" type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" />
            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </div>
    </form>
</section>
