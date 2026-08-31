<?php

namespace App\Livewire\Exports;

use App\Models\CashAccount;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $type = 'akuns';

    public ?int $projectId = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?string $status = null;

    public ?string $arCategory = null;

    public ?string $poNumber = null;

    public ?string $aging = null;

    public ?float $amountMin = null;

    public ?float $amountMax = null;

    // Cashflow specific filters
    public ?string $jenis = null;

    public ?string $sumber = null;

    public ?int $cashAccountId = null;

    /** Load the export type from the route parameter. */
    public function mount(string $type): void
    {
        if (! in_array($type, ['akuns', 'realisasi', 'vs', 'receivables', 'payables', 'cashflows'], true)) {
            abort(404);
        }

        $this->type = $type;
    }

    /** The projects available for filtering. */
    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    /** The statuses available for filtering (AR). */
    #[Computed]
    public function arStatuses(): array
    {
        return [
            '' => 'All Statuses',
            'belum_dibayar' => 'Unpaid',
            'sebagian' => 'Partial',
            'lunas' => 'Paid',
        ];
    }

    /** The statuses available for filtering (AP). */
    #[Computed]
    public function apStatuses(): array
    {
        return [
            '' => 'All Statuses',
            'belum_bayar' => 'Unpaid',
            'sebagian' => 'Partial',
            'lunas' => 'Paid',
        ];
    }

    /** The aging buckets available for filtering. */
    #[Computed]
    public function agingBuckets(): array
    {
        return [
            '' => 'All Ages',
            'current' => 'Current (not due)',
            '1_30' => '1-30 days overdue',
            '31_60' => '31-60 days overdue',
            '61_90' => '61-90 days overdue',
            'over_90' => 'Over 90 days',
        ];
    }

    /** The AR categories available for filtering. */
    #[Computed]
    public function arCategories(): array
    {
        return [
            '' => 'All Categories',
            'billed' => 'Billed (Done + PO)',
            'unbilled' => 'Unbilled (Done, No PO)',
            'inprogress' => 'In Progress',
        ];
    }

    /** Cashflow jenis options for filtering. */
    #[Computed]
    public function cashflowJenisOptions(): array
    {
        return [
            '' => 'All Types',
            'masuk' => 'Cash In',
            'keluar' => 'Cash Out',
        ];
    }

    /** Cashflow sumber options for filtering. */
    #[Computed]
    public function cashflowSumberOptions(): array
    {
        return [
            '' => 'All Sources',
            'pendapatan' => 'Income',
            'pelunasan_ar' => 'AR Settlement',
            'pelunasan_ap' => 'AP Settlement',
            'pengeluaran_lain' => 'Other Expense',
        ];
    }

    /** The cash accounts available for filtering. */
    #[Computed]
    public function cashAccounts()
    {
        return CashAccount::query()->where('status', 'active')->orderBy('kode')->get();
    }

    /** Build the download URL for the given format with the current filters. */
    public function downloadUrl(string $format): string
    {
        $params = [
            'format' => $format,
            'project_id' => $this->projectId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'status' => $this->status,
            'ar_category' => $this->arCategory,
            'po_number' => $this->poNumber,
            'aging' => $this->aging,
            'amount_min' => $this->amountMin,
            'amount_max' => $this->amountMax,
            'jenis' => $this->jenis,
            'sumber' => $this->sumber,
            'cash_account_id' => $this->cashAccountId,
        ];

        return route('exports.'.$this->type, $params);
    }

    /** Render the export page. */
    public function render()
    {
        return view('livewire.exports.index');
    }
}
