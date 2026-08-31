<?php

namespace App\Services;

use App\Enums\CashflowJenis;
use App\Enums\KasStatus;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\Kategori;
use App\Models\Realisasi;
use Illuminate\Pagination\LengthAwarePaginator;

class CashflowService
{
    /**
     * Create a manual (draft) or automatic (posted) cashflow entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Cashflow
    {
        return Cashflow::create([
            'tanggal' => $data['tanggal'],
            'jenis' => $data['jenis'],
            'sumber' => $data['sumber'],
            'payment_id' => $data['payment_id'] ?? null,
            'cash_account_id' => $data['cash_account_id'] ?? CashAccount::defaultId(),
            'akun_id' => $data['akun_id'] ?? null,
            'nominal' => $data['nominal'],
            'keterangan' => $data['keterangan'] ?? null,
            'status' => $data['status'] ?? KasStatus::Posted,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Delete a non-posted manual cashflow entry.
     */
    public function delete(Cashflow $cashflow): void
    {
        if ($cashflow->isPosted()) {
            throw new \LogicException('Posted cash records cannot be deleted. Use the settlement void workflow if needed.');
        }

        $cashflow->delete();
    }

    /**
     * Submit a draft cashflow entry for admin approval.
     */
    public function submit(Cashflow $cashflow): Cashflow
    {
        if ($cashflow->status !== KasStatus::Draft) {
            throw new \LogicException('Only draft cash records can be submitted.');
        }

        $cashflow->update([
            'status' => KasStatus::Waiting,
            'submitted_by' => auth()->id(),
        ]);

        app(NotificationService::class)->notifyAdmins(
            'New Approval Request',
            'Manual cash entry ('.format_idr($cashflow->nominal).') is waiting for approval ('.$cashflow->sumber->label().').',
            route('approvals.index'),
        );

        return $cashflow->refresh();
    }

    /**
     * Approve a waiting cashflow entry (admin).
     */
    public function approve(Cashflow $cashflow): Cashflow
    {
        if ($cashflow->status !== KasStatus::Waiting) {
            throw new \LogicException('Only pending cash records can be approved.');
        }

        $cashflow->update([
            'status' => KasStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $cashflow->refresh();
    }

    /**
     * Post an approved cashflow entry (admin). Affects ledgers and issues the voucher.
     */
    public function post(Cashflow $cashflow): Cashflow
    {
        if ($cashflow->status !== KasStatus::Approved) {
            throw new \LogicException('Only approved cash records can be posted.');
        }

        $cashflow->update([
            'status' => KasStatus::Posted,
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        app(VoucherService::class)->generateFor($cashflow);

        // For manual entries (no payment_id), create/sync Realisasi if tagged with project/party
        if ($cashflow->isManual() && ($cashflow->project_id || $cashflow->pihak_item_id)) {
            $this->syncRealisasiFromCashflow($cashflow);
        }

        return $cashflow->refresh();
    }

    /**
     * Create or update Realisasi from a posted manual cashflow entry.
     */
    protected function syncRealisasiFromCashflow(Cashflow $cashflow): ?Realisasi
    {
        // Determine kategori: find by akun's kategori or default
        $kategoriId = $cashflow->akun?->kategori_id ?? Kategori::first()?->id;

        // For cash in (pendapatan), use a default income category if available
        if ($cashflow->jenis === CashflowJenis::Masuk) {
            $kategoriId = Kategori::where('nama', 'Pendapatan Lain')->first()?->id ?? $kategoriId;
        }

        $data = [
            'project_id' => $cashflow->project_id,
            'akun_id' => $cashflow->akun_id,
            'pihak_type_id' => $cashflow->pihak_type_id,
            'pihak_item_id' => $cashflow->pihak_item_id,
            'kategori_id' => $kategoriId,
            'tanggal' => $cashflow->tanggal,
            'nominal' => $cashflow->nominal,
            'keterangan' => $cashflow->keterangan ?? 'Dari cashflow #'.$cashflow->id,
            'sumber' => Realisasi::SUMBER_MANUAL,
            'sumber_id' => $cashflow->id,
        ];

        return Realisasi::updateOrCreate(
            ['sumber' => Realisasi::SUMBER_MANUAL, 'sumber_id' => $cashflow->id],
            $data
        );
    }

    /**
     * Reject a pending/approved cashflow entry (admin).
     */
    public function reject(Cashflow $cashflow, string $reason): Cashflow
    {
        if ($cashflow->status !== KasStatus::Waiting && $cashflow->status !== KasStatus::Approved) {
            throw new \LogicException('Only pending or approved cash records can be rejected.');
        }

        $cashflow->update([
            'status' => KasStatus::Rejected,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $cashflow->refresh();
    }

    /**
     * List cashflow entries, optionally filtered by date range, jenis, sumber, lokasi dana, and budget number.
     */
    public function paginate(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $jenis = null,
        ?string $sumber = null,
        ?int $cashAccountId = null,
        ?string $budgetNumber = null,
        int $perPage = 10,
    ): LengthAwarePaginator {
        return Cashflow::query()
            ->with(['cashAccount', 'voucher', 'submittedBy', 'akun', 'project.budgetPlans' => fn ($q) => $q->select('id', 'project_id', 'nomor')->orderBy('periode')])
            ->when($jenis, fn ($query) => $query->where('jenis', $jenis))
            ->when($sumber, fn ($query) => $query->where('sumber', $sumber))
            ->when($cashAccountId, fn ($query) => $query->where('cash_account_id', $cashAccountId))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate))
            ->when($budgetNumber, fn ($query) => $query->whereHas('project.budgetPlans', fn ($q) => $q->where('nomor', 'like', '%'.$budgetNumber.'%')))
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Posted cashflow totals for the given filters (drafts are excluded).
     *
     * @return array{total_masuk: float, total_keluar: float, saldo: float, saldo_rekening: float|null}
     */
    public function statistics(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $jenis = null,
        ?string $sumber = null,
        ?int $cashAccountId = null,
    ): array {
        $query = Cashflow::query()
            ->where('status', KasStatus::Posted)
            ->when($jenis, fn ($query) => $query->where('jenis', $jenis))
            ->when($sumber, fn ($query) => $query->where('sumber', $sumber))
            ->when($cashAccountId, fn ($query) => $query->where('cash_account_id', $cashAccountId))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate));

        $totalMasuk = (float) (clone $query)->where('jenis', CashflowJenis::Masuk)->sum('nominal');
        $totalKeluar = (float) (clone $query)->where('jenis', CashflowJenis::Keluar)->sum('nominal');

        $saldoRekening = null;

        if ($cashAccountId !== null && $account = CashAccount::find($cashAccountId)) {
            $saldoRekening = $account->saldo;
        }

        return [
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'saldo' => $totalMasuk - $totalKeluar,
            'saldo_rekening' => $saldoRekening,
        ];
    }
}
