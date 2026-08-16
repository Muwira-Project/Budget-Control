<?php

namespace App\Services;

use App\Enums\KasStatus;
use App\Models\Akun;
use App\Models\NonProjectExpense;
use Illuminate\Pagination\LengthAwarePaginator;

class NonProjectExpenseService
{
    /**
     * Create a new non-project expense as a draft.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NonProjectExpense
    {
        return NonProjectExpense::create($data + [
            'created_by' => auth()->id(),
            'status' => KasStatus::Draft,
        ]);
    }

    /**
     * Update an existing non-project expense (draft only).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(NonProjectExpense $expense, array $data): NonProjectExpense
    {
        if (! $expense->status->isDraft() && ! $expense->status->isWaiting()) {
            throw new \LogicException('Only draft or pending non-project expenses can be edited.');
        }

        $expense->update($data);

        return $expense->refresh();
    }

    /**
     * Submit a draft non-project expense for admin approval.
     */
    public function submit(NonProjectExpense $expense): NonProjectExpense
    {
        if ($expense->status !== KasStatus::Draft) {
            throw new \LogicException('Only draft non-project expenses can be submitted.');
        }

        $expense->update([
            'status' => KasStatus::Waiting,
            'submitted_by' => auth()->id(),
        ]);

        app(NotificationService::class)->notifyAdmins(
            'New Approval Request',
            'Non-Project Expense ('.format_idr($expense->nominal).') is waiting for approval.',
            route('approvals.index'),
        );

        return $expense->refresh();
    }

    /**
     * Approve a waiting non-project expense (admin).
     */
    public function approve(NonProjectExpense $expense): NonProjectExpense
    {
        if ($expense->status !== KasStatus::Waiting) {
            throw new \LogicException('Only pending non-project expenses can be approved.');
        }

        $expense->update([
            'status' => KasStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $expense->refresh();
    }

    /**
     * Post an approved non-project expense (admin). Mirrors into Cash Activity.
     */
    public function post(NonProjectExpense $expense): NonProjectExpense
    {
        if ($expense->status !== KasStatus::Approved) {
            throw new \LogicException('Only approved non-project expenses can be posted.');
        }

        $expense->update([
            'status' => KasStatus::Posted,
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        return $expense->refresh();
    }

    /**
     * Reject a pending/approved non-project expense (admin).
     */
    public function reject(NonProjectExpense $expense, string $reason): NonProjectExpense
    {
        if ($expense->status !== KasStatus::Waiting && $expense->status !== KasStatus::Approved) {
            throw new \LogicException('Only pending or approved non-project expenses can be rejected.');
        }

        $expense->update([
            'status' => KasStatus::Rejected,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $expense->refresh();
    }

    /**
     * Delete a non-posted non-project expense.
     */
    public function delete(NonProjectExpense $expense): void
    {
        if ($expense->isPosted()) {
            throw new \LogicException('Posted non-project expenses cannot be deleted.');
        }

        $expense->delete();
    }

    /**
     * List non-project expenses, optionally filtered by akun and date range.
     */
    public function paginate(?Akun $akun = null, ?string $startDate = null, ?string $endDate = null, string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return NonProjectExpense::query()
            ->with(['akun', 'vendor', 'supplier', 'mandor', 'investor', 'submittedBy'])
            ->when($akun, fn ($query) => $query->where('akun_id', $akun->id))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate))
            ->when($search !== '', fn ($query) => $query->where('keterangan', 'like', '%'.$search.'%'))
            ->latest('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }
}
