<?php

namespace App\Livewire\NonProjectExpenses;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Akun;
use App\Models\NonProjectExpense;
use App\Services\NonProjectExpenseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public ?int $akunId = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $search = '';

    public function delete(NonProjectExpense $expense, NonProjectExpenseService $service): void
    {
        try {
            $service->delete($expense);
            session()->flash('status', 'Non-project expense deleted successfully.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    /**
     * Submit a draft non-project expense for admin approval.
     */
    public function submit(NonProjectExpense $expense, NonProjectExpenseService $service): void
    {
        try {
            $service->submit($expense);
            session()->flash('status', 'Non-project expense submitted for approval.');
        } catch (\LogicException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAkunId(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function expenses(): LengthAwarePaginator
    {
        $akun = $this->akunId ? Akun::find($this->akunId) : null;

        return app(NonProjectExpenseService::class)->paginate($akun, $this->startDate, $this->endDate, $this->search, $this->perPage);
    }

    #[Computed]
    public function akuns()
    {
        return Akun::orderBy('kode_akun')->get();
    }

    #[Computed]
    public function totalNominal(): float
    {
        return (float) $this->expenses->sum('nominal');
    }

    protected function bulkCollectionProperty(): string
    {
        return 'expenses';
    }

    public function deleteSelected(NonProjectExpenseService $service): void
    {
        $count = 0;

        foreach ($this->selectedIds as $id) {
            if (! $expense = NonProjectExpense::find($id)) {
                continue;
            }
            if ($expense->isPosted()) {
                continue;
            }
            try {
                $service->delete($expense);
                $count++;
            } catch (\LogicException) {
            }
        }

        $this->selectedIds = [];

        session()->flash('status', $count.' non-project expense(s) deleted.');
    }

    public function render()
    {
        return view('livewire.non-project-expenses.index');
    }
}
