<?php

namespace App\Livewire\Approvals;

use App\Enums\KasStatus;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\Payment;
use App\Services\CashflowService;
use App\Services\FundTransferService;
use App\Services\PaymentService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public ?string $rejectingType = null;

    public ?int $rejectingId = null;

    public string $rejectNote = '';

    /**
     * Approve a waiting manual cashflow entry.
     */
    public function approveCashflow(int $id, CashflowService $service): void
    {
        if (! $this->isAdmin()) {
            return;
        }

        try {
            $service->approve(Cashflow::findOrFail($id));
            session()->flash('status', 'Cash record approved.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    /**
     * Post an approved manual cashflow entry.
     */
    public function postCashflow(int $id, CashflowService $service): void
    {
        if (! $this->isAdmin()) {
            return;
        }

        try {
            $service->post(Cashflow::findOrFail($id));
            session()->flash('status', 'Cash record posted to the ledger.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    /**
     * Approve a waiting fund transfer.
     */
    public function approveTransfer(int $id, FundTransferService $service): void
    {
        if (! $this->isAdmin()) {
            return;
        }

        try {
            $service->approve(FundTransfer::findOrFail($id));
            session()->flash('status', 'Fund transfer approved.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    /**
     * Post an approved fund transfer.
     */
    public function postTransfer(int $id, FundTransferService $service): void
    {
        if (! $this->isAdmin()) {
            return;
        }

        try {
            $service->post(FundTransfer::findOrFail($id));
            session()->flash('status', 'Fund transfer posted.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    /**
     * Approve a pending settlement cancellation request.
     */
    public function approveVoid(int $id, PaymentService $service): void
    {
        if (! $this->isAdmin()) {
            return;
        }

        try {
            $service->approveVoid(Payment::findOrFail($id));
            session()->flash('status', 'Cancellation approved and settlement removed.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    /**
     * Open the rejection modal.
     */
    public function openReject(string $type, int $id): void
    {
        $this->rejectingType = $type;
        $this->rejectingId = $id;
        $this->rejectNote = '';
    }

    /**
     * Confirm the rejection with a note.
     */
    public function confirmReject(
        CashflowService $cashflowService,
        FundTransferService $transferService,
        PaymentService $paymentService,
    ): void {
        if (! $this->isAdmin() || $this->rejectingType === null || $this->rejectingId === null) {
            return;
        }

        if (trim($this->rejectNote) === '') {
            session()->flash('error', 'Rejection reason is required.');

            return;
        }

        try {
            match ($this->rejectingType) {
                'cashflow' => $cashflowService->reject(Cashflow::findOrFail($this->rejectingId), trim($this->rejectNote)),
                'transfer' => $transferService->reject(FundTransfer::findOrFail($this->rejectingId), trim($this->rejectNote)),
                'void' => $paymentService->rejectVoid(Payment::findOrFail($this->rejectingId), trim($this->rejectNote)),
                default => null,
            };

            session()->flash('status', 'Request rejected.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }

        $this->reset('rejectingType', 'rejectingId', 'rejectNote');
    }

    /**
     * Waiting manual cashflow entries.
     */
    #[Computed]
    public function pendingCashflows(): Collection
    {
        return Cashflow::query()
            ->with(['cashAccount', 'submittedBy'])
            ->where('status', KasStatus::Waiting)
            ->where('payment_id', null)
            ->orderByDesc('tanggal')
            ->get();
    }

    /**
     * Waiting fund transfers.
     */
    #[Computed]
    public function pendingTransfers(): Collection
    {
        return FundTransfer::query()
            ->with(['dariCashAccount', 'keCashAccount', 'submittedBy'])
            ->where('status', KasStatus::Waiting)
            ->orderByDesc('tanggal')
            ->get();
    }

    /**
     * Pending settlement cancellation requests.
     */
    #[Computed]
    public function pendingVoids(): Collection
    {
        return Payment::query()
            ->with(['receivable.project', 'payable.project', 'payable.vendor', 'payable.supplier', 'voidRequestedBy'])
            ->where('status', 'pending_cancel')
            ->orderByDesc('tanggal')
            ->get();
    }

    /**
     * Approved kas items ready to be posted.
     */
    #[Computed]
    public function approvedItems(): Collection
    {
        return collect()
            ->merge(Cashflow::query()
                ->with(['cashAccount', 'approvedBy'])
                ->where('status', KasStatus::Approved)
                ->where('payment_id', null)
                ->orderByDesc('tanggal')
                ->get()
                ->map(fn (Cashflow $item) => ['type' => 'cashflow', 'item' => $item]))
            ->merge(FundTransfer::query()
                ->with(['dariCashAccount', 'keCashAccount', 'approvedBy'])
                ->where('status', KasStatus::Approved)
                ->orderByDesc('tanggal')
                ->get()
                ->map(fn (FundTransfer $item) => ['type' => 'transfer', 'item' => $item]))
            ->sortByDesc(fn (array $row) => $row['item']->tanggal?->format('Y-m-d'));
    }

    /**
     * Whether the pending queues are empty.
     */
    #[Computed]
    public function hasPending(): bool
    {
        return $this->pendingCashflows->isNotEmpty()
            || $this->pendingTransfers->isNotEmpty()
            || $this->pendingVoids->isNotEmpty();
    }

    /**
     * Render the page only for admins.
     */
    private function isAdmin(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function render()
    {
        if (! $this->isAdmin()) {
            abort(403);
        }

        return view('livewire.approvals.index');
    }
}
