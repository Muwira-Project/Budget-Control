<?php

namespace App\Livewire\Vouchers;

use App\Livewire\Concerns\PerPagePagination;
use App\Services\VoucherService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use PerPagePagination, WithPagination;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $jenisFilter = '';

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function updatedJenisFilter(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of vouchers.
     */
    #[Computed]
    public function vouchers(): LengthAwarePaginator
    {
        return app(VoucherService::class)->paginate($this->startDate, $this->endDate, $this->jenisFilter !== '' ? $this->jenisFilter : null, $this->perPage);
    }

    /**
     * The jenis options for filtering.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function jenisOptions(): array
    {
        return [
            'masuk' => 'Income',
            'keluar' => 'Expense',
        ];
    }

    public function render()
    {
        return view('livewire.vouchers.index');
    }
}
