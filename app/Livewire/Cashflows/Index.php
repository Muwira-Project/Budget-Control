<?php

namespace App\Livewire\Cashflows;

use App\Enums\CashflowJenis;
use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Cashflow;
use App\Services\CashflowService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $jenisFilter = '';

    /**
     * Delete a manual cashflow entry.
     */
    public function delete(Cashflow $cashflow, CashflowService $service): void
    {
        if (! $cashflow->isManual()) {
            session()->flash('error', 'Records created automatically from payment requests cannot be deleted.');

            return;
        }

        $service->delete($cashflow);

        session()->flash('status', 'Cash record deleted successfully.');
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
     * Reset the pagination when the jenis filter changes.
     */
    public function updatedJenisFilter(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of cashflow entries.
     */
    #[Computed]
    public function cashflows(): LengthAwarePaginator
    {
        if ($this->dateRangeInvalid) {
            return Cashflow::query()->whereRaw('0 = 1')->paginate(10);
        }

        return app(CashflowService::class)->paginate($this->startDate, $this->endDate, $this->jenisFilter !== '' ? $this->jenisFilter : null, $this->perPage);
    }

    /**
     * Cashflow totals for the selected date range.
     *
     * @return array{total_masuk: float, total_keluar: float, saldo: float}
     */
    #[Computed]
    public function stats(): array
    {
        if ($this->dateRangeInvalid) {
            return ['total_masuk' => 0.0, 'total_keluar' => 0.0, 'saldo' => 0.0];
        }

        return app(CashflowService::class)->statistics($this->startDate, $this->endDate);
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
     * The cashflow jenis options for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function jenisOptions(): array
    {
        return [
            'masuk' => CashflowJenis::Masuk->label(),
            'keluar' => CashflowJenis::Keluar->label(),
        ];
    }

    /**
     * Render the cashflow index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'cashflows';
    }

    public function deleteSelected(CashflowService $service): void
    {
        $deleted = 0;
        $skipped = 0;
        foreach ($this->selectedIds as $id) {
            if (! $cashflow = Cashflow::find($id)) {
                continue;
            }
            if (! $cashflow->isManual()) {
                $skipped++;

                continue;
            }
            $service->delete($cashflow);
            $deleted++;
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' cash record(s) deleted.');
        if ($skipped > 0) {
            session()->flash('error', $skipped.' automatic record(s) cannot be deleted.');
        }
    }

    public function render()
    {
        return view('livewire.cashflows.index');
    }
}
