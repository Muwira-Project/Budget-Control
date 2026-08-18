<?php

namespace App\Livewire\Monitoring;

use App\Http\Requests\MonitoringPeriod\StoreMonitoringPeriodRequest;
use App\Models\Project;
use App\Services\MonitoringPeriodService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public ?int $projectId = null;

    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('manageMonitoring', MonitoringPeriod::class), 403);

        $this->tanggalMulai = now()->startOfMonth()->format('Y-m-d');
        $this->tanggalSelesai = now()->startOfMonth()->addDays(13)->format('Y-m-d');
    }

    public function save(MonitoringPeriodService $service): void
    {
        $validated = Validator::make(
            [
                'project_id' => $this->projectId,
                'tanggal_mulai' => $this->tanggalMulai,
                'tanggal_selesai' => $this->tanggalSelesai,
            ],
            (new StoreMonitoringPeriodRequest)->rules(),
        )->validate();

        $service->create($validated);

        session()->flash('status', 'Monitoring period created successfully.');

        $this->redirectRoute('monitoring.index', navigate: true);
    }

    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    public function render()
    {
        return view('livewire.monitoring.create');
    }
}
