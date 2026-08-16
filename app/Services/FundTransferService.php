<?php

namespace App\Services;

use App\Enums\KasStatus;
use App\Models\CashAccount;
use App\Models\FundTransfer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class FundTransferService
{
    /**
     * Create a fund transfer as a draft.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FundTransfer
    {
        if ((int) $data['dari_cash_account_id'] === (int) $data['ke_cash_account_id']) {
            throw ValidationException::withMessages(['ke_cash_account_id' => 'Source and destination accounts must be different.']);
        }

        return FundTransfer::create([
            'tanggal' => $data['tanggal'],
            'dari_cash_account_id' => $data['dari_cash_account_id'],
            'ke_cash_account_id' => $data['ke_cash_account_id'],
            'nominal' => $data['nominal'],
            'keterangan' => $data['keterangan'] ?? null,
            'created_by' => auth()->id(),
            'status' => KasStatus::Draft,
        ]);
    }

    /**
     * Submit a draft fund transfer for admin approval.
     */
    public function submit(FundTransfer $transfer): FundTransfer
    {
        if ($transfer->status !== KasStatus::Draft) {
            throw new \LogicException('Only draft fund transfers can be submitted.');
        }

        $transfer->update([
            'status' => KasStatus::Waiting,
            'submitted_by' => auth()->id(),
        ]);

        app(NotificationService::class)->notifyAdmins(
            'New Approval Request',
            'Fund Transfer ('.format_idr($transfer->nominal).') is waiting for approval.',
            route('approvals.index'),
        );

        return $transfer->refresh();
    }

    /**
     * Approve a waiting fund transfer (admin).
     */
    public function approve(FundTransfer $transfer): FundTransfer
    {
        if ($transfer->status !== KasStatus::Waiting) {
            throw new \LogicException('Only pending fund transfers can be approved.');
        }

        $transfer->update([
            'status' => KasStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $transfer->refresh();
    }

    /**
     * Post an approved fund transfer (admin). Affects account balances.
     */
    public function post(FundTransfer $transfer): FundTransfer
    {
        if ($transfer->status !== KasStatus::Approved) {
            throw new \LogicException('Only approved fund transfers can be posted.');
        }

        $transfer->update([
            'status' => KasStatus::Posted,
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        app(VoucherService::class)->generateForFundTransfer($transfer);

        return $transfer->refresh();
    }

    /**
     * Reject a pending/approved fund transfer (admin).
     */
    public function reject(FundTransfer $transfer, string $reason): FundTransfer
    {
        if ($transfer->status !== KasStatus::Waiting && $transfer->status !== KasStatus::Approved) {
            throw new \LogicException('Only pending or approved fund transfers can be rejected.');
        }

        $transfer->update([
            'status' => KasStatus::Rejected,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $transfer->refresh();
    }

    /**
     * Delete a non-posted fund transfer.
     */
    public function delete(FundTransfer $transfer): void
    {
        if ($transfer->isPosted()) {
            throw new \LogicException('Posted fund transfers cannot be deleted.');
        }

        $transfer->delete();
    }

    /**
     * List fund transfers.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return FundTransfer::query()
            ->with(['dariCashAccount', 'keCashAccount', 'submittedBy'])
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * The accounts usable as transfer destinations / sources.
     */
    public function accounts()
    {
        return CashAccount::query()->where('status', 'active')->orderBy('kode')->get();
    }
}
