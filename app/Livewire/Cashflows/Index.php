<?php

namespace App\Livewire\Cashflows;

use App\Enums\CashflowJenis;
use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\CashAccount;
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

    public string $sumberFilter = '';

    public ?int $cashAccountId = null;

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

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function updatedJenisFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSumberFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCashAccountId(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of cash activity entries.
     */
    #[Computed]
    public function cashflows(): LengthAwarePaginator
    {
        if ($this->dateRangeInvalid) {
            return Cashflow::query()->whereRaw('0 = 1')->paginate(10);
        }

        return app(CashflowService::class)->paginate(
            $this->startDate,
            $this->endDate,
            $this->jenisFilter !== '' ? $this->jenisFilter : null,
            $this->sumberFilter !== '' ? $this->sumberFilter : null,
            $this->cashAccountId,
            $this->perPage,
        );
    }

    /**
     * Cash activity totals for the selected filters.
     *
     * @return array{total_masuk: float, total_keluar: float, saldo: float, saldo_rekening: float|null}
     */
    #[Computed]
    public function stats(): array
    {
        if ($this->dateRangeInvalid) {
            return ['total_masuk' => 0.0, 'total_keluar' => 0.0, 'saldo' => 0.0, 'saldo_rekening' => null];
        }

        return app(CashflowService::class)->statistics(
            $this->startDate,
            $this->endDate,
            $this->jenisFilter !== '' ? $this->jenisFilter : null,
            $this->sumberFilter !== '' ? $this->sumberFilter : null,
            $this->cashAccountId,
        );
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
     * The cashflow sumber options for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function sumberOptions(): array
    {
        return [
            'payment_request' => 'Payment Request',
            'pendapatan' => 'Income',
            'pelunasan_ar' => 'AR Settlement',
            'pelunasan_ap' => 'AP Settlement',
            'non_project_expense' => 'Non-Project Expense',
        ];
    }

    /**
     * The cash accounts available for filtering.
     */
    #[Computed]
    public function cashAccounts()
    {
        return CashAccount::query()->orderBy('kode')->get();
    }

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
