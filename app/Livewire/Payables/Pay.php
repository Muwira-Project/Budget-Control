<?php

namespace App\Livewire\Payables;

use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Payable;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Pay extends Component
{
    public Payable $payable;

    public string $tanggal = '';

    public string $nominal = '';

    public ?string $keterangan = null;

    /**
     * Load the payable being paid.
     */
    public function mount(Payable $payable): void
    {
        $this->payable = $payable->load(['project', 'pihakType', 'pihakItem']);
        $this->tanggal = now()->format('Y-m-d');
    }

    /**
     * Record the payment against the payable.
     */
    public function save(PaymentService $service): void
    {
        $validator = Validator::make(
            [
                'tanggal' => $this->tanggal,
                'nominal' => $this->nominal,
                'keterangan' => $this->keterangan,
            ],
            (new StorePaymentRequest)->rules(),
        );

        $validator->after(function ($validator): void {
            if ($this->payable->sisa <= 0) {
                $validator->errors()->add('nominal', 'This payable is already paid.');
            } elseif ($this->nominal !== '' && (float) $this->nominal > $this->payable->sisa) {
                $validator->errors()->add('nominal', 'Amount cannot exceed the remaining payable.');
            }
        });

        $validated = $validator->validate();

        $service->createForPayable($this->payable, $validated);

        session()->flash('status', 'Payable payment recorded successfully.');

        $this->redirectRoute('payables.index', navigate: true);
    }

    /**
     * Render the payable payment page.
     */
    public function render()
    {
        return view('livewire.payables.pay');
    }
}
