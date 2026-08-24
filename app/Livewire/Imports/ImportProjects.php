<?php

namespace App\Livewire\Imports;

use App\Imports\ProjectImport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
class ImportProjects extends Component
{
    use WithFileUploads;

    #[Validate('required|file|mimes:xlsx,xls|max:10240', message: [
        'required' => 'Please select an Excel file first.',
        'file' => 'The selected file is invalid.',
        'mimes' => 'The file must be in .xlsx or .xls format.',
        'max' => 'The maximum file size is 10 MB.',
    ])]
    public $file;

    /**
     * Import report: success count, failed rows, and fatal error message.
     *
     * @var array<string, mixed>
     */
    public array $report = [];

    /**
     * Run the project import and build the report.
     */
    public function import(): void
    {
        $this->validate();

        $import = new ProjectImport;
        Excel::import($import, $this->file->getRealPath());

        $this->report = [
            'success' => $import->successCount,
            'failures' => $import->failures,
            'fatal' => $import->fatalError,
        ];

        $this->reset('file');
    }

    /**
     * Render the import page.
     */
    public function render()
    {
        return view('livewire.imports.import-projects');
    }
}