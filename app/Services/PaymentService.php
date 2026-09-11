<?php

namespace App\Services;

use App\Enums\PaymentJenis;
use App\Enums\SettlementStatus;
use App\Models\Cashflow;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Receivable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Record a payment against a receivable (AR), cash masuk.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForReceivable(Receivable $receivable, array $data): Payment
    {
        return DB::transaction(function () use ($receivable, $data) {
            $receivable = Receivable::query()->lockForUpdate()->findOrFail($receivable->id);

            if ($receivable->isHeld()) {
                throw ValidationException::withMessages(['nominal' => 'Receivable is on hold; release it before recording a payment.']);
            }

            $this->ensurePaymentDoesNotExceedBalance($data['nominal'], $receivable->sisa, 'piutang');

            $payment = Payment::create([
                'receivable_id' => $receivable->id,
                'payable_id' => null,
                'tanggal' => $data['tanggal'],
                'nominal' => $data['nominal'],
                'jenis' => PaymentJenis::Masuk,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            $receivable->increment('nominal_dibayar', $data['nominal']);

            app(CashflowService::class)->create([
                'tanggal' => $data['tanggal'],
                'jenis' => 'masuk',
                'sumber' => 'pelunasan_ar',
                'payment_id' => $payment->id,
                'nominal' => $data['nominal'],
                'keterangan' => 'Pelunasan AR '.($receivable->project?->kode ?: ($receivable->project_id ? '#'.$receivable->project_id : 'Non-Project')),
            ]);

            return $payment->refresh();
        });
    }

    /**
     * Record a payment against a payable (AP), cash keluar.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForPayable(Payable $payable, array $data): Payment
    {
        return DB::transaction(function () use ($payable, $data) {
            $payable = Payable::query()->lockForUpdate()->findOrFail($payable->id);
            $this->ensurePaymentDoesNotExceedBalance($data['nominal'], $payable->sisa, 'hutang');

            $payment = Payment::create([
                'receivable_id' => null,
                'payable_id' => $payable->id,
                'tanggal' => $data['tanggal'],
                'nominal' => $data['nominal'],
                'jenis' => PaymentJenis::Keluar,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            $payable->increment('nominal_dibayar', $data['nominal']);

            $partyName = $payable->pihak_item_id !== null
                ? $payable->pihakItem()->value('nama')
                : $payable->project?->kode;

            app(CashflowService::class)->create([
                'tanggal' => $data['tanggal'],
                'jenis' => 'keluar',
                'sumber' => 'pelunasan_ap',
                'payment_id' => $payment->id,
                'nominal' => $data['nominal'],
                'keterangan' => 'Pelunasan AP '.($partyName ?: '#'.$payable->id),
            ]);

            app(ActualService::class)->recordFromPayablePayment($payment);

            return $payment->refresh();
        });
    }

    /**
     * Delete a payment and reverse the paid amount + cashflow entry.
     */
    public function delete(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            if ($payment->receivable_id !== null) {
                Receivable::whereKey($payment->receivable_id)->decrement('nominal_dibayar', $payment->nominal);
            }

            if ($payment->payable_id !== null) {
                Payable::whereKey($payment->payable_id)->decrement('nominal_dibayar', $payment->nominal);
            }

            Cashflow::where('payment_id', $payment->id)->delete();

            app(ActualService::class)->removeForPayment($payment);

            $payment->delete();
        });
    }

    /**
     * Request cancellation of a settlement (staff). Wajib approval admin (K2).
     */
    public function requestVoid(Payment $payment, string $reason): Payment
    {
        if ($payment->status !== SettlementStatus::Active) {
            throw new \LogicException('Only active settlements can be requested for cancellation.');
        }

        $payment->update([
            'status' => SettlementStatus::PendingCancel,
            'void_reason' => $reason,
            'void_requested_by' => auth()->id(),
            'void_requested_at' => now(),
            'void_review_note' => null,
            'void_reviewed_by' => null,
            'void_reviewed_at' => null,
        ]);

        return $payment->refresh();
    }

    /**
     * Approve a pending cancellation: reverse amounts and remove the settlement (admin).
     */
    public function approveVoid(Payment $payment): void
    {
        if ($payment->status !== SettlementStatus::PendingCancel) {
            throw new \LogicException('Only pending settlements can be approved for cancellation.');
        }

        $this->delete($payment);
    }

    /**
     * Reject a pending cancellation and restore the settlement (admin).
     */
    public function rejectVoid(Payment $payment, string $note): Payment
    {
        if ($payment->status !== SettlementStatus::PendingCancel) {
            throw new \LogicException('Only pending settlements can be rejected.');
        }

        $payment->update([
            'status' => SettlementStatus::Active,
            'void_review_note' => $note,
            'void_reviewed_by' => auth()->id(),
            'void_reviewed_at' => now(),
        ]);

        return $payment->refresh();
    }

    /**
     * List payments, optionally filtered by jenis.
     */
    public function paginate(?string $jenis = null, int $perPage = 10, ?string $status = null): LengthAwarePaginator
    {
        return Payment::query()
            ->with(['receivable.project', 'payable.project', 'payable.pihakItem', 'payable.pihakType', 'voidRequestedBy'])
            ->when($jenis, fn ($query) => $query->where('jenis', $jenis))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Enforce the outstanding balance inside the transaction, not only in the UI.
     */
    private function ensurePaymentDoesNotExceedBalance(mixed $nominal, float $balance, string $label): void
    {
        $amount = (float) $nominal;

        if ($amount <= 0) {
            throw ValidationException::withMessages(['nominal' => 'Nominal pembayaran harus lebih dari nol.']);
        }

        if ($amount > $balance) {
            throw ValidationException::withMessages(['nominal' => 'Nominal pembayaran melebihi sisa '.$label.'.']);
        }
    }
}
