<?php

namespace App\Livewire\Trash;

use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Receivable;
use App\Services\ActualService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $tab = 'cash_accounts';

    /**
     * The entity tabs and their label.
     *
     * @var array<string, string>
     */
    protected array $tabs = [
        'cash_accounts' => 'Cash Accounts',
        'payments' => 'Payments',
        'cashflows' => 'Cashflows',
        'fund_transfers' => 'Fund Transfers',
        'receivables' => 'Receivables',
        'payables' => 'Payables',
    ];

    public function mount(): void
    {
        abort_unless(Gate::allows('accessTrash', CashAccount::class), 403);
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs)) {
            $this->tab = $tab;
        }
    }

    #[Computed]
    public function tabs(): array
    {
        return $this->tabs;
    }

    #[Computed]
    public function rows()
    {
        return match ($this->tab) {
            'payments' => Payment::onlyTrashed()->with(['receivable.project', 'payable.project'])->orderByDesc('deleted_at')->get(),
            'cashflows' => Cashflow::onlyTrashed()->with(['cashAccount', 'akun'])->orderByDesc('deleted_at')->get(),
            'fund_transfers' => FundTransfer::onlyTrashed()->with(['dariCashAccount', 'keCashAccount'])->orderByDesc('deleted_at')->get(),
            'receivables' => Receivable::onlyTrashed()->with('project')->orderByDesc('deleted_at')->get(),
            'payables' => Payable::onlyTrashed()->with(['vendor', 'supplier', 'project'])->orderByDesc('deleted_at')->get(),
            default => CashAccount::onlyTrashed()->orderByDesc('deleted_at')->get(),
        };
    }

    /**
     * Restore a soft-deleted row and reverse the side effects of its deletion.
     */
    public function restore(int $id): void
    {
        abort_unless(Gate::allows('accessTrash', CashAccount::class), 403);

        $model = $this->modelClass()::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($model): void {
            if ($model instanceof Payment) {
                // Restore the linked cashflow entries.
                Cashflow::onlyTrashed()->where('payment_id', $model->id)->restore();

                // Restore the paid amount that was decremented on delete.
                if ($model->receivable_id !== null) {
                    Receivable::whereKey($model->receivable_id)->increment('nominal_dibayar', $model->nominal);
                }

                if ($model->payable_id !== null) {
                    Payable::whereKey($model->payable_id)->increment('nominal_dibayar', $model->nominal);
                }

                // Restore the actual (Realisasi) row for AP payments.
                if ($model->payable_id !== null) {
                    app(ActualService::class)->recordFromPayablePayment($model);
                }
            }

            $model->restore();
        });

        session()->flash('status', 'Data berhasil dikembalikan.');
    }

    /**
     * Permanently delete a soft-deleted row.
     */
    public function forceDelete(int $id): void
    {
        abort_unless(Gate::allows('accessTrash', CashAccount::class), 403);

        $model = $this->modelClass()::onlyTrashed()->findOrFail($id);

        // For payments, also permanently delete the linked cashflow entries.
        if ($model instanceof Payment) {
            Cashflow::onlyTrashed()->where('payment_id', $model->id)->forceDelete();
        }

        $model->forceDelete();

        session()->flash('status', 'Data dihapus permanen.');
    }

    /**
     * Display label for a trash row.
     */
    public function rowLabel($row): string
    {
        return match (true) {
            $row instanceof CashAccount => $row->kode.' - '.$row->nama,
            $row instanceof Payment => 'Payment #'.$row->id,
            $row instanceof Cashflow => 'Cashflow #'.$row->id,
            $row instanceof FundTransfer => 'Transfer #'.$row->id,
            $row instanceof Receivable => 'Receivable #'.$row->id,
            $row instanceof Payable => 'Payable #'.$row->id,
            default => '#'.$row->id,
        };
    }

    /**
     * Secondary detail line for a trash row.
     */
    public function rowDetail($row): string
    {
        return match (true) {
            $row instanceof CashAccount => $row->jenis?->label() ?? $row->jenis,
            $row instanceof Payment => $row->receivable?->project?->kode ?? $row->payable?->project?->kode ?? '-',
            $row instanceof Cashflow => ($row->cashAccount?->nama ?? 'No account').' • '.$row->jenis?->label().' • '.number_format((float) $row->nominal, 0, ',', '.'),
            $row instanceof FundTransfer => ($row->dariCashAccount?->kode ?? '?').' → '.($row->keCashAccount?->kode ?? '?'),
            $row instanceof Receivable => ($row->project?->kode ?? '-').' • '.number_format((float) $row->nominal, 0, ',', '.'),
            $row instanceof Payable => ($row->vendor?->nama ?? $row->supplier?->nama ?? $row->project?->kode ?? '-'),
            default => '',
        };
    }

    public function render()
    {
        return view('livewire.trash.index');
    }

    /**
     * The model class for the active tab.
     */
    private function modelClass(): string
    {
        return match ($this->tab) {
            'payments' => Payment::class,
            'cashflows' => Cashflow::class,
            'fund_transfers' => FundTransfer::class,
            'receivables' => Receivable::class,
            'payables' => Payable::class,
            default => CashAccount::class,
        };
    }
}
