<?php

namespace App\Services;

use App\Enums\PaymentJenis;
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
                'keterangan' => 'Pelunasan AR '.($receivable->project()->value('kode') ?: '#'.$receivable->project_id),
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

            $partyName = $payable->vendor_id !== null
                ? $payable->vendor()->value('nama')
                : ($payable->supplier_id !== null ? $payable->supplier()->value('nama') : null);

            app(CashflowService::class)->create([
                'tanggal' => $data['tanggal'],
                'jenis' => 'keluar',
                'sumber' => 'pelunasan_ap',
                'payment_id' => $payment->id,
                'nominal' => $data['nominal'],
                'keterangan' => 'Pelunasan AP '.($partyName ?: '#'.$payable->id),
            ]);

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

            $payment->delete();
        });
    }

    /**
     * List payments, optionally filtered by jenis.
     */
    public function paginate(?string $jenis = null, int $perPage = 10): LengthAwarePaginator
    {
        return Payment::query()
            ->with(['receivable.project', 'payable.project', 'payable.vendor', 'payable.supplier', 'payable.mandor', 'payable.investor'])
            ->when($jenis, fn ($query) => $query->where('jenis', $jenis))
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
