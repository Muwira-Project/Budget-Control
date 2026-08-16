<?php

namespace App\Livewire\NonProjectExpenses;

use App\Http\Requests\NonProjectExpense\StoreNonProjectExpenseRequest;
use App\Models\Akun;
use App\Models\Investor;
use App\Models\Mandor;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Services\NonProjectExpenseService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $tanggal = '';

    public ?int $akunId = null;

    public string $pihakJenis = 'none';

    public ?int $vendorId = null;

    public ?int $supplierId = null;

    public ?int $mandorId = null;

    public ?int $investorId = null;

    public string $nominal = '';

    public ?string $keterangan = null;

    public function mount(): void
    {
        $this->tanggal = now()->format('Y-m-d');
    }

    public function save(NonProjectExpenseService $service): void
    {
        $validator = Validator::make(
            [
                'tanggal' => $this->tanggal,
                'akun_id' => $this->akunId,
                'vendor_id' => $this->vendorId,
                'supplier_id' => $this->supplierId,
                'mandor_id' => $this->mandorId,
                'investor_id' => $this->investorId,
                'nominal' => $this->nominal,
                'keterangan' => $this->keterangan,
            ],
            (new StoreNonProjectExpenseRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            $partyCount = collect([
                $this->vendorId,
                $this->supplierId,
                $this->mandorId,
                $this->investorId,
            ])->filter(fn ($value) => $value !== null)->count();

            if ($partyCount > 1) {
                $validator->errors()->add('vendor_id', 'Only one party can be selected.');
            }
        });

        $validated = $validator->validate();

        $service->create($validated);

        session()->flash('status', 'Non-project expense saved as draft. Submit it for admin approval.');

        $this->redirectRoute('non-project-expenses.index', navigate: true);
    }

    /**
     * Reset the party selection when the party type changes.
     */
    public function updatedPihakJenis(): void
    {
        $this->vendorId = null;
        $this->supplierId = null;
        $this->mandorId = null;
        $this->investorId = null;
    }

    #[Computed]
    public function akuns()
    {
        return Akun::orderBy('kode_akun')->get();
    }

    #[Computed]
    public function vendors()
    {
        return Vendor::orderBy('nama')->get();
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::orderBy('nama')->get();
    }

    #[Computed]
    public function mandors()
    {
        return Mandor::orderBy('nama')->get();
    }

    #[Computed]
    public function investors()
    {
        return Investor::orderBy('nama')->get();
    }

    public function render()
    {
        return view('livewire.non-project-expenses.create');
    }
}
