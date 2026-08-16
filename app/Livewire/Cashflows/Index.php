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

    public string $tab = 'cash-in';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $sumberFilter = '';

    public ?int $cashAccountId = null;

    public ?int $voucherId = null;

    /**
     * Reset pagination when the active tab changes.
     */
    public function updatedTab(): void
    {
        $this->resetPage();
    }

    /**
     * Show the voucher for a posted cash entry.
     */
    public function viewVoucher(int $id): void
    {
        $this->voucherId = $id;
    }

    /**
     * Close the voucher modal.
     */
    public function closeVoucher(): void
    {
        $this->voucherId = null;
    }

    /**
     * Submit a draft manual cashflow entry for admin approval.
     */
    public function submit(Cashflow $cashflow, CashflowService $service): void
    {
        if (! $cashflow->isManual()) {
            session()->flash('error', 'Records created automatically cannot be submitted.');

            return;
        }

        try {
            $service->submit($cashflow);
            session()->flash('status', 'Cash record submitted for approval.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    /**
     * Delete a non-posted manual cashflow entry.
     */
    public function delete(Cashflow $cashflow, CashflowService $service): void
    {
        if (! $cashflow->isManual()) {
            session()->flash('error', 'Records created automatically from settlements cannot be deleted.');

            return;
        }

        try {
            $service->delete($cashflow);
            session()->flash('status', 'Cash record deleted successfully.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
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
     * The jenis derived from the active tab (null for non-cash tabs).
     */
    #[Computed]
    public function jenisForTab(): ?string
    {
        return match ($this->tab) {
            'cash-in' => CashflowJenis::Masuk->value,
            'cash-out' => CashflowJenis::Keluar->value,
            default => null,
        };
    }

    /**
     * The paginated list of cash activity entries for the active tab.
     */
    #[Computed]
    public function cashflows(): LengthAwarePaginator
    {
        if ($this->jenisForTab === null || $this->dateRangeInvalid) {
            return Cashflow::query()->whereRaw('0 = 1')->paginate(10);
        }

        return app(CashflowService::class)->paginate(
            $this->startDate,
            $this->endDate,
            $this->jenisForTab,
            $this->sumberFilter !== '' ? $this->sumberFilter : null,
            $this->cashAccountId,
            $this->perPage,
        );
    }

    /**
     * Cash activity totals for the selected tab and filters.
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
            $this->jenisForTab,
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
     * The cashflow sumber options for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function sumberOptions(): array
    {
        return [
            'pendapatan' => 'Income',
            'pelunasan_ar' => 'AR Settlement',
            'pelunasan_ap' => 'AP Settlement',
            'pengeluaran_lain' => 'Other Expense',
        ];
    }

    /**
     * The cash accounts available for filtering.
     */
    #[Computed]
    public function cashAccounts()
    {
        return CashAccount::query()->where('status', 'active')->orderBy('kode')->get();
    }

    protected function bulkCollectionProperty(): string
    {
        return 'cashflows';
    }

    /**
     * Bulk delete non-posted manual cash entries.
     */
    public function deleteSelected(CashflowService $service): void
    {
        $deleted = 0;
        foreach ($this->selectedIds as $id) {
            if (! $cashflow = Cashflow::find($id)) {
                continue;
            }
            if (! $cashflow->isManual() || $cashflow->isPosted()) {
                continue;
            }
            $service->delete($cashflow);
            $deleted++;
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' cash record(s) deleted.');
    }

    public function render()
    {
        return view('livewire.cashflows.index');
    }
}
