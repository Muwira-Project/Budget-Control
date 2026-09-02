<?php

namespace App\Livewire\Payments;

use App\Enums\PaymentJenis;
use App\Enums\SettlementStatus;
use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public string $jenisFilter = '';

    public string $statusFilter = '';

    public ?int $voidingId = null;

    public string $voidReason = '';

    public ?int $rejectingId = null;

    public string $rejectNote = '';

    /**
     * Request cancellation of a settlement (wajib approval admin, K2).
     */
    public function requestVoid(int $paymentId): void
    {
        $this->voidingId = $paymentId;
        $this->voidReason = '';
    }

    /**
     * Confirm the cancellation request.
     */
    public function confirmVoid(PaymentService $service): void
    {
        if ($this->voidingId === null) {
            return;
        }

        if (trim($this->voidReason) === '') {
            session()->flash('error', 'Void reason is required.');

            return;
        }

        /** @var Payment|null $payment */
        $payment = Payment::find($this->voidingId);

        if ($payment === null) {
            $this->reset('voidingId', 'voidReason');

            return;
        }

        try {
            $service->requestVoid($payment, trim($this->voidReason));
            session()->flash('status', 'Cancellation requested. Waiting for admin approval.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }

        $this->reset('voidingId', 'voidReason');
    }

    /**
     * Approve a pending cancellation (admin only).
     */
    public function approveVoid(Payment $payment, PaymentService $service): void
    {
        if (! Gate::allows('manageSettlements', $payment)) {
            session()->flash('error', 'Only admins can approve cancellations.');

            return;
        }

        try {
            $service->approveVoid($payment);
            session()->flash('status', 'Settlement cancelled and balances restored.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    /**
     * Open the rejection modal for a pending cancellation.
     */
    public function rejectVoid(int $paymentId): void
    {
        $this->rejectingId = $paymentId;
        $this->rejectNote = '';
    }

    /**
     * Confirm the rejection of a cancellation request (admin only).
     */
    public function confirmReject(PaymentService $service): void
    {
        if ($this->rejectingId === null) {
            return;
        }

        /** @var Payment|null $payment */
        $payment = Payment::find($this->rejectingId);

        if ($payment === null) {
            $this->reset('rejectingId', 'rejectNote');

            return;
        }

        if (! Gate::allows('manageSettlements', $payment)) {
            session()->flash('error', 'Only admins can reject cancellations.');

            return;
        }

        if (trim($this->rejectNote) === '') {
            session()->flash('error', 'Rejection note is required.');

            return;
        }

        try {
            $service->rejectVoid($payment, trim($this->rejectNote));
            session()->flash('status', 'Cancellation rejected. Settlement restored.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }

        $this->reset('rejectingId', 'rejectNote');
    }

    /**
     * Delete a payment directly (admin bypass, balances restored).
     */
    public function delete(Payment $payment, PaymentService $service): void
    {
        if (! Gate::allows('manageSettlements', $payment)) {
            session()->flash('error', 'Only admins can delete settlements directly.');

            return;
        }

        $service->delete($payment);

        session()->flash('status', 'Payment deleted and balance restored.');
    }

    public function updatedJenisFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of settlements.
     */
    #[Computed]
    public function payments(): LengthAwarePaginator
    {
        return app(PaymentService::class)->paginate(
            jenis: $this->jenisFilter !== '' ? $this->jenisFilter : null,
            perPage: $this->perPage,
            status: $this->statusFilter !== '' ? $this->statusFilter : null,
        );
    }

    /**
     * The settlement jenis options for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function jenisOptions(): array
    {
        return [
            'masuk' => PaymentJenis::Masuk->label(),
            'keluar' => PaymentJenis::Keluar->label(),
        ];
    }

    /**
     * The settlement status options for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return [
            'active' => SettlementStatus::Active->label(),
            'pending_cancel' => SettlementStatus::PendingCancel->label(),
            'cancelled' => SettlementStatus::Cancelled->label(),
        ];
    }

    protected function bulkCollectionProperty(): string
    {
        return 'payments';
    }

    public function deleteSelected(PaymentService $service): void
    {
        if (! Gate::allows('manageSettlements', Payment::class)) {
            session()->flash('error', 'Only admins can delete settlements directly.');

            return;
        }

        $count = 0;
        foreach ($this->selectedIds as $id) {
            if ($payment = Payment::find($id)) {
                $service->delete($payment);
                $count++;
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $count.' payment(s) deleted and balance restored.');
    }

    public function render()
    {
        return view('livewire.payments.index');
    }
}
