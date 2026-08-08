<?php

namespace App\Services;

use App\Enums\CashflowJenis;
use App\Enums\CashflowSumber;
use App\Models\Cashflow;
use App\Models\PaymentRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class CashflowService
{
    /**
     * Create a manual cashflow entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Cashflow
    {
        return Cashflow::create([
            'tanggal' => $data['tanggal'],
            'jenis' => $data['jenis'],
            'sumber' => $data['sumber'],
            'payment_request_id' => $data['payment_request_id'] ?? null,
            'payment_id' => $data['payment_id'] ?? null,
            'nominal' => $data['nominal'],
            'keterangan' => $data['keterangan'] ?? null,
        ]);
    }

    /**
     * Delete a manual cashflow entry.
     */
    public function delete(Cashflow $cashflow): void
    {
        $cashflow->delete();
    }

    /**
     * Register the cash out entry for a paid payment request.
     */
    public function registerPaymentRequestPaid(PaymentRequest $paymentRequest): Cashflow
    {
        $projectName = $paymentRequest->project()->value('nama');

        return Cashflow::firstOrCreate([
            'payment_request_id' => $paymentRequest->id,
        ], [
            'tanggal' => $paymentRequest->paid_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'jenis' => CashflowJenis::Keluar,
            'sumber' => CashflowSumber::PaymentRequest,
            'nominal' => $paymentRequest->nominal,
            'keterangan' => 'Payment '.$paymentRequest->nomor.($projectName ? ' ('.$projectName.')' : ''),
        ]);
    }

    /**
     * List cashflow entries, optionally filtered by date range and jenis.
     */
    public function paginate(?string $startDate = null, ?string $endDate = null, ?string $jenis = null, int $perPage = 10): LengthAwarePaginator
    {
        return Cashflow::query()
            ->with('paymentRequest')
            ->when($jenis, fn ($query) => $query->where('jenis', $jenis))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate))
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Cashflow totals for the given date range.
     *
     * @return array{total_masuk: float, total_keluar: float, saldo: float}
     */
    public function statistics(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Cashflow::query()
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate));

        $totalMasuk = (float) (clone $query)->where('jenis', CashflowJenis::Masuk)->sum('nominal');
        $totalKeluar = (float) (clone $query)->where('jenis', CashflowJenis::Keluar)->sum('nominal');

        return [
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'saldo' => $totalMasuk - $totalKeluar,
        ];
    }
}
