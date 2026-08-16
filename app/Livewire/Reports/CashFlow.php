<?php

namespace App\Livewire\Reports;

use App\Services\ReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CashFlow extends Component
{
    public ?string $startDate = null;

    public ?string $endDate = null;

    #[Computed]
    public function report(): array
    {
        return app(ReportService::class)->cashFlow($this->startDate, $this->endDate);
    }

    public function render()
    {
        return view('livewire.reports.cash-flow');
    }
}
