<?php

namespace App\Services;

use App\Models\Cashflow;
use App\Models\NumberSequence;
use App\Models\Voucher;

class VoucherService
{
    /**
     * Generate a voucher (nomor seri otomatis + tanggal) for a cash entry.
     */
    public function generateFor(Cashflow $cashflow): ?Voucher
    {
        if (Voucher::where('cashflow_id', $cashflow->id)->exists()) {
            return null;
        }

        $year = $cashflow->tanggal?->format('Y') ?? (string) now()->year;

        return Voucher::create([
            'nomor' => $this->nextNomor($year),
            'tanggal' => $cashflow->tanggal?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'jenis' => $cashflow->jenis->value,
            'cashflow_id' => $cashflow->id,
            'keterangan' => $cashflow->keterangan,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * List vouchers, optionally filtered by date range and jenis.
     */
    public function paginate(?string $startDate = null, ?string $endDate = null, ?string $jenis = null, int $perPage = 10): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Voucher::query()
            ->with('cashflow')
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate))
            ->when($jenis, fn ($query) => $query->where('jenis', $jenis))
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Generate the next voucher number for the given year.
     */
    protected function nextNomor(string $year): string
    {
        do {
            $next = NumberSequence::next('voucher', $year);
            $nomor = 'VC-'.$year.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        } while (Voucher::where('nomor', $nomor)->exists());

        return $nomor;
    }
}