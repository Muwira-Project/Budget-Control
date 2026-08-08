<?php

namespace App\Livewire\Receivables;

use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Receivable;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Pay extends Component
{
    public Receivable $receivable;

    public string $tanggal = '';

    public string $nominal = '';

    public ?string $keterangan = null;

    /**
     * Load the receivable being paid.
     */
    public function mount(Receivable $receivable): void
    {
        $this->receivable = $receivable->load('project');
        $this->tanggal = now()->format('Y-m-d');
    }

    /**
     * Record the payment against the receivable.
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
            if ($this->receivable->sisa <= 0) {
                $validator->errors()->add('nominal', 'This receivable is already paid.');
            } elseif ($this->nominal !== '' && (float) $this->nominal > $this->receivable->sisa) {
                $validator->errors()->add('nominal', 'Amount cannot exceed the remaining receivable.');
            }
        });

        $validated = $validator->validate();

        $service->createForReceivable($this->receivable, $validated);

        session()->flash('status', 'Receivable payment recorded successfully.');

        $this->redirectRoute('receivables.index', navigate: true);
    }

    /**
     * Render the receivable payment page.
     */
    public function render()
    {
        return view('livewire.receivables.pay');
    }
}
