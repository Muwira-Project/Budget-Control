<?php

namespace App\Livewire\Realisasi;

use App\Livewire\Concerns\PerPagePagination;
use App\Models\Project;
use App\Models\Realisasi;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Summary extends Component
{
    use PerPagePagination;

    public ?int $projectId = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    /**
     * Aggregated actuals grouped by project and account (staff view).
     */
    #[Computed]
    public function rows(): Collection
    {
        return Realisasi::query()
            ->with(['project', 'akun'])
            ->when($this->projectId, fn ($query) => $query->where('project_id', $this->projectId))
            ->when($this->startDate, fn ($query) => $query->whereDate('tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn ($query) => $query->whereDate('tanggal', '<=', $this->endDate))
            ->get()
            ->groupBy(fn (Realisasi $item) => $item->project_id.'-'.$item->akun_id)
            ->map(function (Collection $items): array {
                $first = $items->first();

                return [
                    'project' => $first?->project,
                    'akun' => $first?->akun,
                    'nominal' => $items->sum('nominal'),
                    'jumlah_transaksi' => $items->count(),
                ];
            })
            ->values();
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
     * The total aggregate for the current filter.
     */
    #[Computed]
    public function total(): float
    {
        return $this->rows->sum('nominal');
    }

    /**
     * Render the staff summary page.
     */
    public function render()
    {
        return view('livewire.realisasi.summary');
    }
}
