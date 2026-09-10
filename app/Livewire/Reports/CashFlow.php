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

    /**
     * Build the download URL for the given format with the current date filters.
     */
    public function downloadUrl(string $format): string
    {
        return route('exports.cash-flow-report', array_filter([
            'format'     => $format,
            'start_date' => $this->startDate,
            'end_date'   => $this->endDate,
        ]));
    }

    public function render()
    {
        return view('livewire.reports.cash-flow');
    }
}
