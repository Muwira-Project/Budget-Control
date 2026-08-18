<?php

namespace App\Livewire\CompanySettings;

use App\Services\CompanySettingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads;

    public string $companyName = '';

    public string $companyAddress = '';

    public ?UploadedFile $logo = null;

    public ?UploadedFile $loginIllustration = null;

    public ?string $existingLogoUrl = null;

    public ?string $existingIllustrationUrl = null;

    public string $illustrationFit = 'cover';

    public string $illustrationPosition = 'center';

    public function mount(): void
    {
        abort_unless(Gate::allows('manageUsers'), 403);

        $settings = app(CompanySettingService::class)->get();
        $this->companyName = $settings->company_name ?? '';
        $this->companyAddress = $settings->company_address ?? '';
        $this->existingLogoUrl = $settings->logo_url;
        $this->existingIllustrationUrl = $settings->login_illustration_url;
        $this->illustrationFit = $settings->illustration_fit ?? 'cover';
        $this->illustrationPosition = $settings->illustration_position ?? 'center';
    }

    public function save(CompanySettingService $service): void
    {
        $this->validate([
            'companyName' => ['required', 'string', 'max:255'],
            'companyAddress' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048', 'dimensions:min_width=100,min_height=100'],
            'loginIllustration' => ['nullable', 'image', 'max:4096'],
            'illustrationFit' => ['required', 'in:cover,contain,fill'],
            'illustrationPosition' => ['required', 'in:top,center,bottom'],
        ]);

        $data = [
            'company_name' => $this->companyName,
            'company_address' => $this->companyAddress,
            'illustration_fit' => $this->illustrationFit,
            'illustration_position' => $this->illustrationPosition,
        ];

        $service->update(
            app(CompanySettingService::class)->get(),
            $data,
            $this->logo,
            $this->loginIllustration
        );

        // Refresh the preview images from the updated row.
        $settings = app(CompanySettingService::class)->get();
        $this->existingLogoUrl = $settings->logo_url;
        $this->existingIllustrationUrl = $settings->login_illustration_url;

        $this->reset('logo', 'loginIllustration');

        session()->flash('status', 'Company settings updated successfully.');

        $this->redirectRoute('company-settings.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.company-settings.index');
    }
}
