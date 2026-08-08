<?php

namespace App\Livewire\Monitoring;

use App\Models\MonitoringPeriod;
use App\Services\MonitoringPeriodService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Resume extends Component
{
    public MonitoringPeriod $monitoringPeriod;

    public function mount(MonitoringPeriod $monitoringPeriod): void
    {
        $this->monitoringPeriod = $monitoringPeriod->load('project');
    }

    #[Computed]
    public function breakdown(): Collection
    {
        return app(MonitoringPeriodService::class)->accountBreakdown($this->monitoringPeriod);
    }

    #[Computed]
    public function totals(): array
    {
        $service = app(MonitoringPeriodService::class);

        return [
            'budget' => $service->budgetTotal($this->monitoringPeriod),
            'actual' => $service->actualTotal($this->monitoringPeriod),
            'variance' => $service->budgetTotal($this->monitoringPeriod) - $service->actualTotal($this->monitoringPeriod),
        ];
    }

    public function render()
    {
        return view('livewire.monitoring.resume');
    }
}
