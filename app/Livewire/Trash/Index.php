<?php

namespace App\Livewire\Trash;

use App\Enums\SettlementStatus;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Realisasi;
use App\Models\Receivable;
use App\Models\Voucher;
use App\Services\ActualService;
use App\Services\VoucherService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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
            'payables' => Payable::onlyTrashed()->with(['pihakItem', 'pihakType', 'project'])->orderByDesc('deleted_at')->get(),
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

        if ($model instanceof Payment && $model->status === SettlementStatus::Cancelled) {
            throw ValidationException::withMessages(['trash' => 'An approved cancelled settlement cannot be restored. Record a new settlement if another payment is needed.']);
        }

        DB::transaction(function () use ($model): void {
            if ($model instanceof Payment) {
                $this->restoreSettlementParent($model);
            }

            if ($model instanceof Payable) {
                $this->restorePayableSource($model);
            }

            $model->restore();

            if ($model instanceof Receivable || $model instanceof Payable) {
                $payments = Payment::onlyTrashed()
                    ->whereIn('status', [SettlementStatus::Active, SettlementStatus::PendingCancel])
                    ->when($model instanceof Receivable, fn ($query) => $query->where('receivable_id', $model->id))
                    ->when($model instanceof Payable, fn ($query) => $query->where('payable_id', $model->id))
                    ->get();

                foreach ($payments as $payment) {
                    $this->restorePaymentEffects($payment);
                    $payment->restore();
                }
            }

            if ($model instanceof Payment) {
                $this->restorePaymentEffects($model);
            }

            if ($model instanceof Cashflow && $model->isManual()) {
                $realisasi = Realisasi::withTrashed()
                    ->where('sumber', Realisasi::SUMBER_MANUAL)
                    ->where('sumber_id', $model->id)
                    ->first();

                if ($realisasi?->trashed()) {
                    $realisasi->restore();
                }

                if ($realisasi !== null) {
                    $payable = Payable::withTrashed()->where('realisasi_id', $realisasi->id)->first();
                    if ($payable?->trashed()) {
                        $payable->restore();
                    }
                }
            }

            // Older deletion flows removed vouchers instead of keeping them in Trash.
            if ($model instanceof Cashflow && $model->isPosted()) {
                app(VoucherService::class)->generateFor($model);
            } elseif ($model instanceof FundTransfer && $model->isPosted()) {
                app(VoucherService::class)->generateForFundTransfer($model);
            }
        });

        session()->flash('status', 'Data berhasil dikembalikan.');
    }

    /** Restore a settlement's parent records before reviving the settlement. */
    private function restoreSettlementParent(Payment $payment): void
    {
        if ($payment->receivable_id !== null) {
            $receivable = Receivable::withTrashed()->find($payment->receivable_id);
            if ($receivable?->trashed()) {
                $receivable->restore();
            }
        }

        if ($payment->payable_id !== null) {
            $payable = Payable::withTrashed()->find($payment->payable_id);
            if ($payable?->trashed()) {
                $this->restorePayableSource($payable);
                $payable->restore();
            }
        }
    }

    /** Restore the originating actual row for a payable generated from one. */
    private function restorePayableSource(Payable $payable): void
    {
        if ($payable->realisasi_id === null) {
            return;
        }

        $realisasi = Realisasi::withTrashed()->find($payable->realisasi_id);
        if ($realisasi?->trashed()) {
            $realisasi->restore();
        }
    }

    /** Restore ledger and paid-total effects for an active settlement. */
    private function restorePaymentEffects(Payment $payment): void
    {
        Cashflow::onlyTrashed()->where('payment_id', $payment->id)->restore();

        if ($payment->receivable_id !== null) {
            Receivable::whereKey($payment->receivable_id)->increment('nominal_dibayar', $payment->nominal);
        }

        if ($payment->payable_id !== null) {
            Payable::whereKey($payment->payable_id)->increment('nominal_dibayar', $payment->nominal);
            app(ActualService::class)->recordFromPayablePayment($payment);
        }
    }

    /**
     * Permanently delete a soft-deleted row.
     */
    public function forceDelete(int $id): void
    {
        abort_unless(Gate::allows('accessTrash', CashAccount::class), 403);

        $model = $this->modelClass()::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($model): void {
            // For payments, also permanently delete the linked cashflow entries.
            if ($model instanceof Payment) {
                $this->forceDeletePaymentRelations($model);
            }

            if ($model instanceof Receivable || $model instanceof Payable) {
                $paymentForeignKey = $model instanceof Receivable ? 'receivable_id' : 'payable_id';
                $payments = Payment::withTrashed()->where($paymentForeignKey, $model->id)->get();

                foreach ($payments as $payment) {
                    $this->forceDeletePaymentRelations($payment);
                    $payment->forceDelete();
                }
            }

            if ($model instanceof Cashflow) {
                $realisasi = Realisasi::withTrashed()
                    ->where('sumber', 'manual')
                    ->where('sumber_id', $model->id)
                    ->first();
                $payable = $realisasi === null ? null : Payable::withTrashed()->where('realisasi_id', $realisasi->id)->first();

                if ($payable !== null && Payment::withTrashed()->where('payable_id', $payable->id)->exists()) {
                    throw ValidationException::withMessages(['trash' => 'Delete or permanently remove linked payment history before permanently deleting this cashflow.']);
                }

                Voucher::where('cashflow_id', $model->id)->delete();
                $realisasi?->forceDelete();
            }

            if ($model instanceof FundTransfer) {
                Voucher::where('fund_transfer_id', $model->id)->delete();
            }

            $model->forceDelete();
        });

        session()->flash('status', 'Data dihapus permanen.');
    }

    /** Permanently remove ledger/actual rows that belong to a settlement. */
    private function forceDeletePaymentRelations(Payment $payment): void
    {
        $cashflows = Cashflow::withTrashed()->where('payment_id', $payment->id)->get();
        Voucher::whereIn('cashflow_id', $cashflows->pluck('id'))->delete();
        $cashflows->each(fn (Cashflow $cashflow) => $cashflow->forceDelete());

        Realisasi::withTrashed()
            ->where('sumber', Realisasi::SUMBER_AP_PAYMENT)
            ->where('sumber_id', $payment->id)
            ->forceDelete();
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
            $row instanceof Payable => ($row->pihak ?? $row->project?->kode ?? '-'),
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
