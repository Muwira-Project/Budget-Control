<?php

namespace App\Livewire\Realisasi;

use App\Livewire\Concerns\PerPagePagination;
use App\Models\Akun;
use App\Models\Project;
use App\Models\Realisasi as RealisasiModel;
use App\Services\RealisasiService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use PerPagePagination, WithPagination;

    public ?int $projectId = null;

    public ?int $akunId = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    /**
     * The paginated ledger of actual transactions (read-only).
     */
    #[Computed]
    public function realisasi(): LengthAwarePaginator
    {
        if ($this->dateRangeInvalid) {
            return RealisasiModel::query()->whereRaw('0 = 1')->paginate($this->perPage);
        }

        $project = $this->projectId ? Project::find($this->projectId) : null;
        $akun = $this->akunId ? Akun::find($this->akunId) : null;

        return app(RealisasiService::class)->paginate($project, $akun, $this->startDate, $this->endDate, $this->perPage);
    }

    /**
     * The projects available for filtering.
     */
    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    /**
     * The master akuns available for filtering.
     */
    #[Computed]
    public function akuns()
    {
        return Akun::orderBy('kode_akun')->get();
    }

    /**
     * Reset dependent filters and pagination when the project changes.
     */
    public function updatedProjectId(): void
    {
        $this->resetPage();
    }

    /**
     * Reset the pagination when the akun filter changes.
     */
    public function updatedAkunId(): void
    {
        $this->resetPage();
    }

    /**
     * Reset the pagination when the start date changes.
     */
    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    /**
     * Reset the pagination when the end date changes.
     */
    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    /**
     * Whether the selected date range is invalid (start after end).
     */
    #[Computed]
    public function dateRangeInvalid(): bool
    {
        return $this->startDate !== null
            && $this->endDate !== null
            && $this->startDate > $this->endDate;
    }

    /**
     * Render the actual ledger page.
     */
    public function render()
    {
        return view('livewire.realisasi.index');
    }
}
