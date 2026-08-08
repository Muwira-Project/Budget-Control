<?php

namespace App\Livewire\PaymentRequests;

use App\Enums\PaymentRequestStatus;
use App\Http\Requests\PaymentRequest\UpdatePaymentRequestRequest;
use App\Models\Akun;
use App\Models\Investor;
use App\Models\Mandor;
use App\Models\PaymentRequest;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Services\PaymentRequestService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Name;
use Livewire\Component;

#[Layout('layouts.app')]
#[Name('payment-requests.edit')]
class Edit extends Component
{
    public PaymentRequest $paymentRequest;

    public ?int $projectId = null;

    public ?int $akunId = null;

    public ?int $vendorId = null;

    public ?int $supplierId = null;

    public ?int $mandorId = null;

    public ?int $investorId = null;

    public string $pihakJenis = 'vendor';

    public string $tanggal = '';

    public string $jatuhTempo = '';

    public string $nominal = '';

    public string $prioritas = 'medium';

    public ?string $keterangan = null;

    /**
     * Load the payment request being edited.
     */
    public function mount(PaymentRequest $paymentRequest): void
    {
        if (! auth()->user()->isAdmin()
            && ($paymentRequest->status !== PaymentRequestStatus::Draft || $paymentRequest->created_by !== auth()->id())) {
            abort(403, 'Staff can only edit their own draft payment requests.');
        }

        if (in_array($paymentRequest->status->value, ['waiting', 'approved', 'paid', 'closed', 'cancelled'], true)) {
            session()->flash('error', 'Payment request with status '.$paymentRequest->status->label().' cannot be edited.');

            $this->redirectRoute('payment-requests.index', navigate: true);

            return;
        }

        $this->paymentRequest = $paymentRequest;
        $this->projectId = $paymentRequest->project_id;
        $this->akunId = $paymentRequest->akun_id;
        $this->vendorId = $paymentRequest->vendor_id;
        $this->supplierId = $paymentRequest->supplier_id;
        $this->mandorId = $paymentRequest->mandor_id;
        $this->investorId = $paymentRequest->investor_id;
        $this->pihakJenis = match (true) {
            $paymentRequest->vendor_id !== null => 'vendor',
            $paymentRequest->supplier_id !== null => 'supplier',
            $paymentRequest->mandor_id !== null => 'mandor',
            $paymentRequest->investor_id !== null => 'investor',
            default => 'vendor',
        };
        $this->tanggal = $paymentRequest->tanggal->format('Y-m-d');
        $this->jatuhTempo = $paymentRequest->jatuh_tempo?->format('Y-m-d') ?? '';
        $this->nominal = $paymentRequest->nominal;
        $this->prioritas = $paymentRequest->prioritas->value;
        $this->keterangan = $paymentRequest->keterangan;
    }

    /**
     * Update the payment request and move it back to draft.
     */
    public function save(PaymentRequestService $service): void
    {
        $validator = Validator::make(
            [
                'project_id' => $this->projectId,
                'akun_id' => $this->akunId,
                'vendor_id' => $this->vendorId,
                'supplier_id' => $this->supplierId,
                'mandor_id' => $this->mandorId,
                'investor_id' => $this->investorId,
                'tanggal' => $this->tanggal,
                'jatuh_tempo' => $this->jatuhTempo !== '' ? $this->jatuhTempo : null,
                'nominal' => $this->nominal,
                'prioritas' => $this->prioritas,
                'keterangan' => $this->keterangan,
            ],
            (new UpdatePaymentRequestRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            $partyCount = collect([
                $this->vendorId,
                $this->supplierId,
                $this->mandorId,
                $this->investorId,
            ])->filter(fn ($value) => $value !== null)->count();

            if ($partyCount !== 1) {
                $validator->errors()->add('vendor_id', 'Select exactly one party.');
            }

            if (count(array_filter([$this->vendorId, $this->supplierId])) > 1) {
                $validator->errors()->add('vendor_id', 'Only one can be selected: Vendor or Supplier.');
            }

            if ($this->projectId !== null
                && $this->akunId !== null
                && ProjectAkun::where('project_id', $this->projectId)
                    ->where('akun_id', $this->akunId)
                    ->where('status', 'approved')
                    ->doesntExist()) {
                $validator->errors()->add('akun_id', 'Account must be allocated (approved) to the selected project.');
            }
        });

        $validated = $validator->validate();

        $service->update($this->paymentRequest, $validated);

        $this->paymentRequest->update(['status' => PaymentRequestStatus::Draft]);

        session()->flash('status', 'Payment request updated successfully.');

        $this->redirectRoute('payment-requests.index', navigate: true);
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

    /**
     * The projects available for selection.
     */
    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    /**
     * The approved allocations for the selected project.
     */
    #[Computed]
    public function akuns()
    {
        return Akun::query()
            ->when($this->projectId, fn ($query) => $query->whereIn('id', ProjectAkun::where('project_id', $this->projectId)->where('status', 'approved')->pluck('akun_id')))
            ->orderBy('kode_akun')
            ->get();
    }

    /**
     * The vendors available for selection.
     */
    #[Computed]
    public function vendors()
    {
        return Vendor::orderBy('nama')->get();
    }

    /**
     * The suppliers available for selection.
     */
    #[Computed]
    public function suppliers()
    {
        return Supplier::orderBy('nama')->get();
    }

    /**
     * The mandors available for selection.
     */
    #[Computed]
    public function mandors()
    {
        return Mandor::orderBy('nama')->get();
    }

    /**
     * The investors available for selection.
     */
    #[Computed]
    public function investors()
    {
        return Investor::orderBy('nama')->get();
    }

    /**
     * Render the payment request edit page.
     */
    public function render()
    {
        return view('livewire.payment-requests.edit');
    }
}
