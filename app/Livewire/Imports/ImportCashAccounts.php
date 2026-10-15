<?php

namespace App\Livewire\Imports;

use App\Imports\CashAccountImport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
class ImportCashAccounts extends Component
{
    use WithFileUploads;

    #[Validate('required|file|mimes:xlsx,xls|max:10240')]
    public $file;

    public array $report = [];

    public function import(): void
    {
        $this->validate();
        $import = new CashAccountImport;
        Excel::import($import, $this->file->getRealPath());
        $this->report = ['success' => $import->successCount, 'failures' => $import->failures, 'fatal' => $import->fatalError];
        $this->reset('file');
    }

    public function render()
    {
        return view('livewire.imports.import-cash-accounts');
    }
}
