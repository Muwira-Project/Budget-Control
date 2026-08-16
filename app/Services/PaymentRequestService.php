<?php

namespace App\Services;

use App\Enums\PaymentRequestStatus;
use App\Models\NumberSequence;
use App\Models\PaymentRequest;
use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentRequestService
{
    /**
     * Create a new payment request as a draft.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PaymentRequest
    {
        return PaymentRequest::create([
            'nomor' => $this->nextNomor(),
            'project_id' => $data['project_id'],
            'akun_id' => $data['akun_id'],
            'vendor_id' => $data['vendor_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'mandor_id' => $data['mandor_id'] ?? null,
            'investor_id' => $data['investor_id'] ?? null,
            'tanggal' => $data['tanggal'],
            'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
            'nominal' => $data['nominal'],
            'prioritas' => $data['prioritas'],
            'status' => PaymentRequestStatus::Draft,
            'created_by' => auth()->id(),
            'keterangan' => $data['keterangan'] ?? null,
        ]);
    }

    /**
     * Update an existing payment request.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(PaymentRequest $paymentRequest, array $data): PaymentRequest
    {
        $paymentRequest->update([
            'project_id' => $data['project_id'],
            'akun_id' => $data['akun_id'],
            'vendor_id' => $data['vendor_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'mandor_id' => $data['mandor_id'] ?? null,
            'investor_id' => $data['investor_id'] ?? null,
            'tanggal' => $data['tanggal'],
            'jatuh_tempo' => $data['jatuh_tempo'] ?? null,
            'nominal' => $data['nominal'],
            'prioritas' => $data['prioritas'],
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        if ($paymentRequest->status === PaymentRequestStatus::Approved
            || $paymentRequest->status === PaymentRequestStatus::Paid) {
            app(PayableService::class)->syncFromPaymentRequest($paymentRequest);
        }

        return $paymentRequest->refresh();
    }

    /**
     * Delete a payment request.
     */
    public function delete(PaymentRequest $paymentRequest): void
    {
        app(PayableService::class)->removeForPaymentRequest($paymentRequest);

        $paymentRequest->delete();
    }

    /**
     * Submit a draft payment request for approval.
     */
    public function submit(PaymentRequest $paymentRequest): PaymentRequest
    {
        $paymentRequest->update([
            'status' => PaymentRequestStatus::Waiting,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        app(NotificationService::class)->notifyAdmins(
            'Payment Request Pending Approval',
            'Payment request '.$paymentRequest->nomor.' ('.format_idr($paymentRequest->nominal).') is waiting for approval.',
            route('payment-requests.index'),
        );

        return $paymentRequest->refresh();
    }

    /**
     * Approve a waiting payment request (admin only).
     */
    public function approve(PaymentRequest $paymentRequest): PaymentRequest
    {
        $paymentRequest->update([
            'status' => PaymentRequestStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        app(PayableService::class)->syncFromPaymentRequest($paymentRequest);

        return $paymentRequest->refresh();
    }

    /**
     * Reject a waiting payment request (admin only).
     */
    public function reject(PaymentRequest $paymentRequest): PaymentRequest
    {
        $paymentRequest->update([
            'status' => PaymentRequestStatus::Rejected,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        app(PayableService::class)->removeForPaymentRequest($paymentRequest);

        return $paymentRequest->refresh();
    }

    /**
     * Mark an approved payment request as paid.
     */
    public function markPaid(PaymentRequest $paymentRequest): PaymentRequest
    {
        return DB::transaction(function () use ($paymentRequest) {
            $paymentRequest = PaymentRequest::query()->lockForUpdate()->findOrFail($paymentRequest->id);

            if ($paymentRequest->status === PaymentRequestStatus::Paid) {
                return $paymentRequest;
            }

            if ($paymentRequest->status !== PaymentRequestStatus::Approved) {
                throw new \LogicException('Only approved payment requests can be marked as paid.');
            }

            if ($paymentRequest->isHeld()) {
                throw new \LogicException('Held payment requests cannot be marked as paid until released.');
            }

            $paymentRequest->update([
                'status' => PaymentRequestStatus::Paid,
                'paid_at' => now(),
            ]);

            $payable = app(PayableService::class)->syncFromPaymentRequest($paymentRequest);

            if ($payable) {
                $payable->update(['nominal_dibayar' => $paymentRequest->nominal]);
            }

            app(CashflowService::class)->registerPaymentRequestPaid($paymentRequest);

            app(ActualService::class)->recordFromPaymentRequest($paymentRequest);

            return $paymentRequest->refresh();
        });
    }

    /**
     * Put a payment request on hold (K1: tahan pengeluaran).
     */
    public function hold(PaymentRequest $paymentRequest, string $reason): PaymentRequest
    {
        if (in_array($paymentRequest->status->value, ['paid', 'closed', 'cancelled'], true)) {
            throw new \LogicException('Finalized payment requests cannot be put on hold.');
        }

        $paymentRequest->update([
            'hold_reason' => $reason,
            'held_by' => auth()->id(),
            'held_at' => now(),
        ]);

        return $paymentRequest->refresh();
    }

    /**
     * Release a held payment request.
     */
    public function release(PaymentRequest $paymentRequest): PaymentRequest
    {
        $paymentRequest->update([
            'hold_reason' => null,
            'held_by' => null,
            'held_at' => null,
        ]);

        return $paymentRequest->refresh();
    }

    /**
     * Close an approved payment request.
     */
    public function close(PaymentRequest $paymentRequest): PaymentRequest
    {
        app(PayableService::class)->removeForPaymentRequest($paymentRequest);

        $paymentRequest->update(['status' => PaymentRequestStatus::Closed]);

        return $paymentRequest->refresh();
    }

    /**
     * Cancel a non-final payment request.
     */
    public function cancel(PaymentRequest $paymentRequest): PaymentRequest
    {
        if ($paymentRequest->status !== PaymentRequestStatus::Draft
            && $paymentRequest->status !== PaymentRequestStatus::Rejected) {
            throw new LogicException('Only draft or rejected payment requests can be cancelled.');
        }

        app(PayableService::class)->removeForPaymentRequest($paymentRequest);

        $paymentRequest->update(['status' => PaymentRequestStatus::Cancelled]);

        return $paymentRequest->refresh();
    }

    /**
     * List payment requests, optionally filtered by project, status, priority, and search.
     */
    public function paginate(?Project $project = null, ?string $status = null, ?string $prioritas = null, string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return PaymentRequest::query()
            ->with(['project', 'akun', 'vendor', 'supplier', 'mandor', 'investor'])
            ->when($project, fn ($query) => $query->where('project_id', $project->id))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($prioritas, fn ($query) => $query->where('prioritas', $prioritas))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nomor', 'like', '%'.$search.'%')
                    ->orWhereHas('project', fn ($projectQuery) => $projectQuery
                        ->where('kode', 'like', '%'.$search.'%')
                        ->orWhere('nama', 'like', '%'.$search.'%'));
            }))
            ->orderByRaw("CASE prioritas WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Generate the next payment request number for the current year.
     */
    protected function nextNomor(): string
    {
        do {
            $next = NumberSequence::next('payment_request');
            $nomor = 'PR-'.now()->format('Y').'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        } while (PaymentRequest::where('nomor', $nomor)->exists());

        return $nomor;
    }
}
