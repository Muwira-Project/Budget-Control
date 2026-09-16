<?php

namespace App\Livewire\Cashflows;

use App\Enums\CashflowJenis;
use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\BudgetPlan;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\Payment;
use App\Services\CashflowService;
use App\Services\PaymentService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
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

    public ?string $budgetNumberFilter = null;

    // Void settlement
    public ?int $voidingId = null;

    public string $voidReason = '';

    public ?int $approvingId = null;

    /**
     * Reset pagination when the active tab changes and prevent staff from
     * switching to admin-only tabs.
     */
    public function updatedTab(string $value): void
    {
        $this->resetPage();
        $this->clearSelection();

        if (! auth()->user()->isAdmin() && in_array($value, ['fund-transfer', 'cash-account'], true)) {
            $this->tab = 'cash-in';
        }
    }

    /** Open void settlement request modal (staff). */
    public function requestVoid(int $cashflowId): void
    {
        $cashflow = Cashflow::find($cashflowId);
        if ($cashflow === null || $cashflow->payment_id === null) {
            return;
        }
        $this->voidingId = $cashflow->payment_id;
        $this->voidReason = '';
    }

    /** Confirm void settlement request (staff). */
    public function confirmVoid(PaymentService $service): void
    {
        if ($this->voidingId === null) {
            return;
        }

        if (trim($this->voidReason) === '') {
            session()->flash('error', 'Void reason is required.');

            return;
        }

        $payment = Payment::find($this->voidingId);

        if ($payment === null) {
            $this->reset('voidingId', 'voidReason');

            return;
        }

        try {
            $service->requestVoid($payment, trim($this->voidReason));
            session()->flash('status', 'Cancellation requested. Waiting for admin approval.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }

        $this->reset('voidingId', 'voidReason');
    }

    /** Approve void settlement (admin only). */
    public function approveVoid(int $paymentId, PaymentService $service): void
    {
        if (! Gate::allows('manageSettlements', Payment::class)) {
            session()->flash('error', 'Only admins can approve cancellations.');

            return;
        }

        $payment = Payment::find($paymentId);

        if ($payment === null) {
            return;
        }

        try {
            $service->approveVoid($payment);
            session()->flash('status', 'Settlement cancelled and balances restored.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }

        $this->reset('approvingId');
    }

    /**
     * Delete a posted cashflow entry is not allowed; only non-posted can be deleted.
     */
    /** Delete a cashflow entry (manual only). */
    public function delete(Cashflow $cashflow, CashflowService $service): void
    {
        if (! $cashflow->isManual()) {
            session()->flash('error', 'Records created automatically from settlements cannot be deleted.');

            return;
        }

        try {
            $id = (int) $cashflow->id;
            $service->delete($cashflow);
            $this->selectedIds = array_values(array_diff(array_map('intval', $this->selectedIds), [$id]));
            session()->flash('status', 'Cash record deleted successfully.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    /** Bulk delete selected cashflow entries (manual entries only). */
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
            try {
                $service->delete($cashflow);
                $deleted++;
            } catch (\LogicException) {
                $skipped++;
            }
        }
        $this->selectedIds = [];
        if ($deleted > 0) {
            session()->flash('status', $deleted.' cash record(s) deleted.');
        }
        if ($skipped > 0) {
            session()->flash('error', $skipped.' record(s) could not be deleted (auto-generated from settlements).');
        }
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSumberFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedCashAccountId(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedBudgetNumberFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
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
            $this->budgetNumberFilter,
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
            null,
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
            'pengeluaran_lain' => 'Other Outcome',
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

    /**
     * Available budget numbers for filter dropdown.
     */
    #[Computed]
    public function budgetNumberOptions(): Collection
    {
        return BudgetPlan::query()
            ->select('nomor')
            ->whereNotNull('nomor')
            ->distinct()
            ->orderBy('nomor')
            ->pluck('nomor');
    }

    protected function bulkCollectionProperty(): string
    {
        return 'cashflows';
    }

    /**
     * IDs of manual (deletable) cashflow entries currently visible on this page.
     *
     * @return array<int>
     */
    #[Computed]
    public function selectableIds(): array
    {
        return $this->cashflows->getCollection()
            ->filter(fn (Cashflow $item) => $item->isManual())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Toggle only manual entries on the currently visible page.
     */
    public function toggleAllVisible(): void
    {
        $ids = $this->selectableIds;
        $selectedIds = array_map('intval', $this->selectedIds);
        $intersect = array_intersect($ids, $selectedIds);

        if (count($intersect) === count($ids) && count($ids) > 0) {
            $this->selectedIds = array_values(array_diff($selectedIds, $ids));
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($selectedIds, $ids)));
        }
    }

    /**
     * Guard toggleSelected so auto-generated entries cannot be selected for bulk deletion.
     */
    public function toggleSelected(int $id): void
    {
        $id = (int) $id;
        $cashflow = Cashflow::find($id);
        if ($cashflow && ! $cashflow->isManual()) {
            return;
        }

        $this->selectedIds = array_map('intval', $this->selectedIds);

        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));
        } else {
            $this->selectedIds[] = $id;
        }
    }

    public function render()
    {
        return view('livewire.cashflows.index');
    }
}
