<?php

namespace App\Livewire\Imports;

use App\Imports\ReceivableImport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
class ImportReceivables extends Component
{
    use WithFileUploads;

    #[Validate('required|file|mimes:xlsx,xls,csv|max:10240', message: [
        'required' => 'Please select an Excel or CSV file first.',
        'file' => 'The selected file is invalid.',
        'mimes' => 'The file must be in .xlsx, .xls, or .csv format.',
        'max' => 'The maximum file size is 10 MB.',
    ])]
    public $file;

    /** Import report: success count, failed rows, and fatal error message. */
    public array $report = [];

    /** Run the receivable import and build the report. */
    public function import(): void
    {
        $this->validate();

        $import = new ReceivableImport;
        Excel::import($import, $this->file->getRealPath());

        $this->report = [
            'success' => $import->successCount,
            'failures' => $import->failures,
            'fatal' => $import->fatalError,
        ];

        $this->reset('file');
    }

    /** Download the import template. */
    public function downloadTemplate(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('imports.receivables.template');
    }

    /** Render the import page. */
    public function render()
    {
        return view('livewire.imports.import-receivables');
    }

    public function __invoke(): \Illuminate\Contracts\View\View
    {
        return $this->render();
    }
}