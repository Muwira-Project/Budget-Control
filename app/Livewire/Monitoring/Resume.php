<?php

namespace App\Livewire\Monitoring;

use App\Models\Akun;
use App\Models\MonitoringPeriod;
use App\Models\Realisasi;
use App\Services\MonitoringPeriodService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Resume extends Component
{
    public MonitoringPeriod $monitoringPeriod;

    public ?int $selectedAkunId = null;

    public function mount(MonitoringPeriod $monitoringPeriod): void
    {
        $this->monitoringPeriod = $monitoringPeriod->load('project');
    }

    public function showAccountDetail(int $akunId): void
    {
        $this->selectedAkunId = $akunId;
    }

    public function closeAccountDetail(): void
    {
        $this->selectedAkunId = null;
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
        $budget = $service->budgetTotal($this->monitoringPeriod);
        $actual = $service->actualTotal($this->monitoringPeriod);
        $actualIn = $service->actualInTotal($this->monitoringPeriod);

        return [
            'budget' => $budget,
            'actual_in' => $actualIn,
            'actual' => $actual,
            'variance' => $budget - $actual,
        ];
    }

    /**
     * Realisasi (actual) rows for the selected account inside the period.
     */
    #[Computed]
    public function accountRealisations(): Collection
    {
        if ($this->selectedAkunId === null) {
            return collect();
        }

        return Realisasi::query()
            ->with(['akun', 'kategori', 'pihakType', 'pihakItem', 'project'])
            ->where('akun_id', $this->selectedAkunId)
            ->when($this->monitoringPeriod->project_id, fn ($query) => $query->where('project_id', $this->monitoringPeriod->project_id))
            ->whereDate('tanggal', '>=', $this->monitoringPeriod->tanggal_mulai->format('Y-m-d'))
            ->whereDate('tanggal', '<=', $this->monitoringPeriod->tanggal_selesai->format('Y-m-d'))
            ->orderByDesc('tanggal')
            ->get();
    }

    #[Computed]
    public function selectedAkun()
    {
        if ($this->selectedAkunId === null) {
            return null;
        }

        return Akun::find($this->selectedAkunId);
    }

    public function render()
    {
        return view('livewire.monitoring.resume');
    }
}
