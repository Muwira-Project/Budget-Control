<?php

namespace App\Livewire\Reports;

use App\Services\ReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Aging extends Component
{
    public string $asOf = '';

    public function mount(): void
    {
        $this->asOf = now()->format('Y-m-d');
    }

    #[Computed]
    public function report(): array
    {
        return app(ReportService::class)->aging($this->asOf ?: null);
    }

    public function render()
    {
        return view('livewire.reports.aging');
    }
}
