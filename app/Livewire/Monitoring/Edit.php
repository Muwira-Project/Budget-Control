<?php

namespace App\Livewire\Monitoring;

use App\Http\Requests\MonitoringPeriod\UpdateMonitoringPeriodRequest;
use App\Models\MonitoringPeriod;
use App\Models\Project;
use App\Services\MonitoringPeriodService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public MonitoringPeriod $monitoringPeriod;

    public ?int $projectId = null;

    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public function mount(MonitoringPeriod $monitoringPeriod): void
    {
        abort_unless(Gate::allows('manageMonitoring', $monitoringPeriod), 403);

        $this->monitoringPeriod = $monitoringPeriod;
        $this->projectId = $monitoringPeriod->project_id;
        $this->tanggalMulai = $monitoringPeriod->tanggal_mulai->format('Y-m-d');
        $this->tanggalSelesai = $monitoringPeriod->tanggal_selesai->format('Y-m-d');
    }

    public function save(MonitoringPeriodService $service): void
    {
        $validated = Validator::make(
            [
                'project_id' => $this->projectId,
                'tanggal_mulai' => $this->tanggalMulai,
                'tanggal_selesai' => $this->tanggalSelesai,
            ],
            (new UpdateMonitoringPeriodRequest)->rules(),
        )->validate();

        $service->update($this->monitoringPeriod, $validated);

        session()->flash('status', 'Monitoring period updated successfully.');

        $this->redirectRoute('monitoring.index', navigate: true);
    }

    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    public function render()
    {
        return view('livewire.monitoring.edit');
    }
}
