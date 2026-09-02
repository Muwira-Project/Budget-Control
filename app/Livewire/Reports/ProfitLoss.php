<?php

namespace App\Livewire\Reports;

use App\Models\Project;
use App\Services\ReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProfitLoss extends Component
{
    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $projectId = null;

    #[Computed]
    public function report(): array
    {
        return app(ReportService::class)->profitLoss($this->startDate, $this->endDate, $this->projectId);
    }

    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    public function render()
    {
        return view('livewire.reports.profit-loss');
    }
}
