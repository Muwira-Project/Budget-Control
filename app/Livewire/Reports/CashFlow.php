<?php

namespace App\Livewire\Reports;

use App\Models\CashAccount;
use App\Services\ReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CashFlow extends Component
{
    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $cashAccountId = null;

    #[Computed]
    public function cashAccounts()
    {
        return CashAccount::query()->where('status', 'active')->orderBy('kode')->get();
    }

    #[Computed]
    public function report(): array
    {
        return app(ReportService::class)->cashFlow($this->startDate, $this->endDate);
    }

    #[Computed]
    public function detailReport(): ?array
    {
        if (! $this->cashAccountId) {
            return null;
        }

        return app(ReportService::class)->cashFlowDetail($this->startDate, $this->endDate, $this->cashAccountId);
    }

    /**
     * Build the download URL for the given format with the current date and account filters.
     */
    public function downloadUrl(string $format): string
    {
        return route('exports.cash-flow-report', array_filter([
            'format'          => $format,
            'start_date'      => $this->startDate,
            'end_date'        => $this->endDate,
            'cash_account_id' => $this->cashAccountId,
        ]));
    }

    public function render()
    {
        return view('livewire.reports.cash-flow');
    }
}
