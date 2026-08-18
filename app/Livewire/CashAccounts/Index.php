<?php

namespace App\Livewire\CashAccounts;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\CashAccount;
use App\Services\CashAccountService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public string $statusFilter = '';

    /**
     * Delete a cash account.
     */
    public function delete(CashAccount $account, CashAccountService $service): void
    {
        try {
            $service->delete($account);
            session()->flash('status', 'Cash account deleted.');
        } catch (ValidationException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of cash accounts.
     */
    #[Computed]
    public function accounts(): LengthAwarePaginator
    {
        return app(CashAccountService::class)->paginate($this->statusFilter !== '' ? $this->statusFilter : null, $this->perPage);
    }

    protected function bulkCollectionProperty(): string
    {
        return 'accounts';
    }

    public function deleteSelected(CashAccountService $service): void
    {
        $deleted = 0;
        $skipped = 0;
        foreach ($this->selectedIds as $id) {
            if ($account = CashAccount::find($id)) {
                try {
                    $service->delete($account);
                    $deleted++;
                } catch (ValidationException $exception) {
                    $skipped++;
                }
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' cash account(s) deleted.');
        if ($skipped > 0) {
            session()->flash('error', $skipped.' account(s) with transaction history could not be deleted.');
        }
    }

    public function render()
    {
        return view('livewire.cash-accounts.index');
    }
}
