<?php

namespace App\Services;

use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\NumberSequence;
use App\Models\Voucher;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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

        return DB::transaction(function () use ($cashflow, $year): Voucher {
            $voucher = Voucher::create([
                'nomor' => $this->nextNomor($year),
                'tanggal' => $cashflow->tanggal?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'jenis' => $cashflow->jenis->value,
                'cashflow_id' => $cashflow->id,
                'keterangan' => $cashflow->keterangan,
                'created_by' => auth()->id(),
            ]);

            return $voucher;
        });
    }

    /**
     * Generate a voucher (nomor seri otomatis + tanggal) for a fund transfer.
     */
    public function generateForFundTransfer(FundTransfer $fundTransfer): ?Voucher
    {
        if (Voucher::where('fund_transfer_id', $fundTransfer->id)->exists()) {
            return null;
        }

        $year = $fundTransfer->tanggal?->format('Y') ?? (string) now()->year;

        return DB::transaction(function () use ($fundTransfer, $year): Voucher {
            $voucher = Voucher::create([
                'nomor' => $this->nextNomor($year),
                'tanggal' => $fundTransfer->tanggal?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'jenis' => 'transfer',
                'fund_transfer_id' => $fundTransfer->id,
                'keterangan' => $fundTransfer->keterangan,
                'created_by' => auth()->id(),
            ]);

            return $voucher;
        });
    }

    /**
     * List vouchers, optionally filtered by date range and jenis.
     */
    public function paginate(?string $startDate = null, ?string $endDate = null, ?string $jenis = null, int $perPage = 10): LengthAwarePaginator
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
