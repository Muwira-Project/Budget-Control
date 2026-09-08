<?php

namespace App\Livewire\Imports;

use App\Imports\PayableImport;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
class ImportPayables extends Component
{
    use WithFileUploads;

    #[Validate('required|file|mimes:xlsx,xls,csv|max:10240', message: [
        'required' => 'Please select an Excel or CSV file first.',
        'file' => 'The selected file is invalid.',
        'mimes' => 'The file must be in .xlsx, .xls, or .csv format.',
        'max' => 'The maximum file size is 10 MB.',
    ])]
    public $file;

    #[Validate('in:with_project,without_project')]
    public string $importMode = 'without_project';

    /** Import report: success count, failed rows, and fatal error message. */
    public array $report = [];

    /** Run the payable import and build the report. */
    public function import(): void
    {
        $this->validate();

        $useProjectCode = $this->importMode === 'with_project';
        $import = new PayableImport($useProjectCode);
        Excel::import($import, $this->file->getRealPath());

        $this->report = [
            'success' => $import->successCount,
            'failures' => $import->failures,
            'fatal' => $import->fatalError,
        ];

        $this->reset('file');
    }

    /** Download the import template. */
    public function downloadTemplate(): RedirectResponse
    {
        $useProjectCode = $this->importMode === 'with_project';
        return redirect()->route('imports.payables.template', ['use_project_code' => $useProjectCode]);
    }

    /** Render the import page. */
    public function render()
    {
        return view('livewire.imports.import-payables');
    }
}