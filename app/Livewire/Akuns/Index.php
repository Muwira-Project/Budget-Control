<?php

namespace App\Livewire\Akuns;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\Akun;
use App\Models\Realisasi;
use App\Services\AkunService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public string $search = '';

    public string $jenisAkun = '';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $selectedAkunId = null;

    /**
     * Delete an akun.
     */
    public function delete(Akun $akun, AkunService $service): void
    {
        $service->delete($akun);

        session()->flash('status', 'Account deleted successfully.');
    }

    /**
     * Reset the pagination when the search query changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset the pagination when the jenis filter changes.
     */
    public function updatedJenisAkun(): void
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

    /**
     * The paginated list of master akuns.
     */
    #[Computed]
    public function akuns(): LengthAwarePaginator
    {
        return app(AkunService::class)->paginate($this->search, $this->jenisAkun !== '' ? $this->jenisAkun : null, $this->startDate, $this->endDate, $this->perPage);
    }

    public function showAccountDetail(int $akunId): void
    {
        $this->selectedAkunId = $akunId;
    }

    public function closeDetails(): void
    {
        $this->selectedAkunId = null;
    }

    #[Computed]
    public function selectedAkun(): ?Akun
    {
        return $this->selectedAkunId ? Akun::find($this->selectedAkunId) : null;
    }

    /**
     * Realisasi rows for the selected account inside the active date range.
     */
    #[Computed]
    public function akunRealisations(): Collection
    {
        if ($this->selectedAkunId === null) {
            return collect();
        }

        return Realisasi::query()
            ->with(['project', 'kategori', 'vendor', 'supplier', 'mandor', 'investor'])
            ->where('akun_id', $this->selectedAkunId)
            ->when($this->startDate, fn ($query) => $query->whereDate('tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn ($query) => $query->whereDate('tanggal', '<=', $this->endDate))
            ->orderByDesc('tanggal')
            ->get();
    }

    /**
     * Render the akun index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'akuns';
    }

    public function deleteSelected(AkunService $service): void
    {
        $count = 0;
        foreach ($this->selectedIds as $id) {
            if ($akun = Akun::find($id)) {
                $service->delete($akun);
                $count++;
            }
        }
        $this->selectedIds = [];
        session()->flash('status', $count.' account(s) deleted.');
    }

    public function render()
    {
        return view('livewire.akuns.index');
    }
}
