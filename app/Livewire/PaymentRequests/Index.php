<?php

namespace App\Livewire\PaymentRequests;

use App\Enums\PaymentPriority;
use App\Enums\PaymentRequestStatus;
use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\PaymentRequest;
use App\Models\Project;
use App\Services\PaymentRequestService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public ?int $projectId = null;

    public string $statusFilter = '';

    public string $prioritasFilter = '';

    public string $search = '';

    /**
     * Delete a non-final payment request.
     */
    public function delete(PaymentRequest $paymentRequest, PaymentRequestService $service): void
    {
        if (! $this->canManageDraft($paymentRequest)) {
            session()->flash('error', 'Staff can only manage their own draft payment requests.');

            return;
        }

        if (in_array($paymentRequest->status->value, ['waiting', 'approved', 'paid', 'closed'], true)) {
            session()->flash('error', 'Payment request with status '.$paymentRequest->status->label().' cannot be deleted.');

            return;
        }

        $service->delete($paymentRequest);

        session()->flash('status', 'Payment request deleted successfully.');
    }

    /**
     * Submit a draft payment request for approval.
     */
    public function submit(PaymentRequest $paymentRequest, PaymentRequestService $service): void
    {
        if (! $this->canManageDraft($paymentRequest)) {
            session()->flash('error', 'Staff can only submit their own draft payment requests.');

            return;
        }

        if ($paymentRequest->status !== PaymentRequestStatus::Draft) {
            session()->flash('error', 'Only payment requests with draft status can be submitted.');

            return;
        }

        $service->submit($paymentRequest);

        session()->flash('status', 'Payment request submitted for approval.');
    }

    /**
     * Approve a waiting payment request (admin only).
     */
    public function approve(PaymentRequest $paymentRequest, PaymentRequestService $service): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'Only admins can approve payment requests.');

            return;
        }

        if ($paymentRequest->status !== PaymentRequestStatus::Waiting) {
            session()->flash('error', 'Only payment requests awaiting approval can be approved.');

            return;
        }

        $service->approve($paymentRequest);

        session()->flash('status', 'Payment request approved.');
    }

    /**
     * Reject a waiting payment request (admin only).
     */
    public function reject(PaymentRequest $paymentRequest, PaymentRequestService $service): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'Only admins can reject payment requests.');

            return;
        }

        if ($paymentRequest->status !== PaymentRequestStatus::Waiting) {
            session()->flash('error', 'Only payment requests awaiting approval can be rejected.');

            return;
        }

        $service->reject($paymentRequest);

        session()->flash('status', 'Payment request rejected.');
    }

    /**
     * Mark an approved payment request as paid.
     */
    public function markPaid(PaymentRequest $paymentRequest, PaymentRequestService $service): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'Only admins can mark payment requests as paid.');

            return;
        }

        if ($paymentRequest->status !== PaymentRequestStatus::Approved) {
            session()->flash('error', 'Only approved payment requests can be marked as paid.');

            return;
        }

        $service->markPaid($paymentRequest);

        session()->flash('status', 'Payment request marked as paid.');
    }

    /**
     * Close an approved payment request.
     */
    public function close(PaymentRequest $paymentRequest, PaymentRequestService $service): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'Only admins can close payment requests.');

            return;
        }

        if ($paymentRequest->status !== PaymentRequestStatus::Approved) {
            session()->flash('error', 'Only approved payment requests can be closed.');

            return;
        }

        $service->close($paymentRequest);

        session()->flash('status', 'Payment request closed.');
    }

    /**
     * Cancel a non-final payment request.
     */
    public function cancel(PaymentRequest $paymentRequest, PaymentRequestService $service): void
    {
        if (in_array($paymentRequest->status->value, ['paid', 'closed', 'cancelled'], true)) {
            session()->flash('error', 'Payment request with status '.$paymentRequest->status->label().' cannot be cancelled.');

            return;
        }

        $service->cancel($paymentRequest);

        session()->flash('status', 'Payment request cancelled.');
    }

    /**
     * Reset the pagination when a filter changes.
     */
    public function updatedProjectId(): void
    {
        $this->resetPage();
    }

    /**
     * Reset the pagination when the status filter changes.
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Reset the pagination when the priority filter changes.
     */
    public function updatedPrioritasFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Reset the pagination when the search query changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of payment requests.
     */
    #[Computed]
    public function paymentRequests(): LengthAwarePaginator
    {
        $project = $this->projectId ? Project::find($this->projectId) : null;

        if (! auth()->user()->isAdmin()) {
            return PaymentRequest::query()
                ->with(['project', 'akun', 'vendor', 'supplier'])
                ->where('created_by', auth()->id())
                ->where('status', PaymentRequestStatus::Draft)
                ->orderByDesc('tanggal')
                ->paginate($this->perPage)
                ->withQueryString();
        }

        return app(PaymentRequestService::class)->paginate(
            $project,
            $this->statusFilter !== '' ? $this->statusFilter : null,
            $this->prioritasFilter !== '' ? $this->prioritasFilter : null,
            $this->search,
            $this->perPage,
        );
    }

    /**
     * The projects available for filtering.
     */
    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    /**
     * The payment request statuses available for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function statuses(): array
    {
        return [
            'draft' => PaymentRequestStatus::Draft->label(),
            'waiting' => PaymentRequestStatus::Waiting->label(),
            'approved' => PaymentRequestStatus::Approved->label(),
            'paid' => PaymentRequestStatus::Paid->label(),
            'closed' => PaymentRequestStatus::Closed->label(),
            'cancelled' => PaymentRequestStatus::Cancelled->label(),
            'rejected' => PaymentRequestStatus::Rejected->label(),
        ];
    }

    /**
     * The payment priorities available for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function prioritas(): array
    {
        return [
            'high' => PaymentPriority::High->label(),
            'medium' => PaymentPriority::Medium->label(),
            'low' => PaymentPriority::Low->label(),
        ];
    }

    /**
     * Render the payment request index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'paymentRequests';
    }

    public function deleteSelected(PaymentRequestService $service): void
    {
        $deleted = 0;
        $skipped = 0;
        foreach ($this->selectedIds as $id) {
            if (! $paymentRequest = PaymentRequest::find($id)) {
                continue;
            }
            if (! $this->canManageDraft($paymentRequest)) {
                $skipped++;

                continue;
            }
            $service->delete($paymentRequest);
            $deleted++;
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' payment request(s) deleted.');
        if ($skipped > 0) {
            session()->flash('error', $skipped.' payment request(s) cannot be deleted in their current status.');
        }
    }

    public function render()
    {
        return view('livewire.payment-requests.index');
    }

    private function canManageDraft(PaymentRequest $paymentRequest): bool
    {
        return auth()->user()->isAdmin()
            || ($paymentRequest->status === PaymentRequestStatus::Draft && $paymentRequest->created_by === auth()->id());
    }
}
