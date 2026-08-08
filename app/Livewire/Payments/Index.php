<?php

namespace App\Livewire\Payments;

use App\Enums\PaymentJenis;
use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public string $jenisFilter = '';

    /**
     * Delete a payment and reverse the paid amount.
     */
    public function delete(Payment $payment, PaymentService $service): void
    {
        $service->delete($payment);

        session()->flash('status', 'Payment deleted and balance restored.');
    }

    /**
     * Reset the pagination when the jenis filter changes.
     */
    public function updatedJenisFilter(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of payments.
     */
    #[Computed]
    public function payments(): LengthAwarePaginator
    {
        return app(PaymentService::class)->paginate($this->jenisFilter !== '' ? $this->jenisFilter : null, $this->perPage);
    }

    /**
     * The payment jenis options for filtering.
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
     * Render the payment index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'payments';
    }

    public function deleteSelected(PaymentService $service): void
    {
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
