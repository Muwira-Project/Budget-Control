<?php

namespace App\Livewire\Approvals;

use App\Enums\KasStatus;
use App\Models\Cashflow;
use App\Models\FundTransfer;
use App\Models\NonProjectExpense;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Services\CashflowService;
use App\Services\FundTransferService;
use App\Services\NonProjectExpenseService;
use App\Services\PaymentRequestService;
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
     * Approve a waiting non-project expense.
     */
    public function approveNpe(int $id, NonProjectExpenseService $service): void
    {
        if (! $this->isAdmin()) {
            return;
        }

        try {
            $service->approve(NonProjectExpense::findOrFail($id));
            session()->flash('status', 'Non-project expense approved.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    /**
     * Post an approved non-project expense.
     */
    public function postNpe(int $id, NonProjectExpenseService $service): void
    {
        if (! $this->isAdmin()) {
            return;
        }

        try {
            $service->post(NonProjectExpense::findOrFail($id));
            session()->flash('status', 'Non-project expense posted to Cash Activity.');
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
     * Approve a waiting payment request.
     */
    public function approvePr(int $id, PaymentRequestService $service): void
    {
        if (! $this->isAdmin()) {
            return;
        }

        $pr = PaymentRequest::findOrFail($id);

        if ($pr->status->value !== 'waiting') {
            session()->flash('error', 'Only payment requests awaiting approval can be approved.');

            return;
        }

        $service->approve($pr);
        session()->flash('status', 'Payment request approved.');
    }

    /**
     * Reject a waiting payment request.
     */
    public function rejectPr(int $id, PaymentRequestService $service): void
    {
        if (! $this->isAdmin()) {
            return;
        }

        $pr = PaymentRequest::findOrFail($id);

        if ($pr->status->value !== 'waiting') {
            session()->flash('error', 'Only payment requests awaiting approval can be rejected.');

            return;
        }

        $service->reject($pr);
        session()->flash('status', 'Payment request rejected.');
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
        NonProjectExpenseService $npeService,
        FundTransferService $transferService,
        PaymentRequestService $prService,
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
                'npe' => $npeService->reject(NonProjectExpense::findOrFail($this->rejectingId), trim($this->rejectNote)),
                'transfer' => $transferService->reject(FundTransfer::findOrFail($this->rejectingId), trim($this->rejectNote)),
                'pr' => $prService->reject(PaymentRequest::findOrFail($this->rejectingId)),
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
            ->where('payment_request_id', null)
            ->where('payment_id', null)
            ->where('non_project_expense_id', null)
            ->orderByDesc('tanggal')
            ->get();
    }

    /**
     * Waiting non-project expenses.
     */
    #[Computed]
    public function pendingNpes(): Collection
    {
        return NonProjectExpense::query()
            ->with(['akun', 'vendor', 'supplier', 'mandor', 'investor', 'submittedBy'])
            ->where('status', KasStatus::Waiting)
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
     * Waiting payment requests.
     */
    #[Computed]
    public function pendingPrs(): Collection
    {
        return PaymentRequest::query()
            ->with(['project', 'akun', 'vendor', 'supplier', 'mandor', 'investor'])
            ->where('status', 'waiting')
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
                ->where('payment_request_id', null)
                ->where('payment_id', null)
                ->where('non_project_expense_id', null)
                ->orderByDesc('tanggal')
                ->get()
                ->map(fn (Cashflow $item) => ['type' => 'cashflow', 'item' => $item]))
            ->merge(NonProjectExpense::query()
                ->with(['akun', 'vendor', 'supplier', 'approvedBy'])
                ->where('status', KasStatus::Approved)
                ->orderByDesc('tanggal')
                ->get()
                ->map(fn (NonProjectExpense $item) => ['type' => 'npe', 'item' => $item]))
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
            || $this->pendingNpes->isNotEmpty()
            || $this->pendingTransfers->isNotEmpty()
            || $this->pendingPrs->isNotEmpty()
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
