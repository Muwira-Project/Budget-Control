<?php

namespace App\Livewire\Exports;

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

    /**
     * Load the export type from the route parameter.
     */
    public function mount(string $type): void
    {
        if (! in_array($type, ['akuns', 'realisasi', 'vs'], true)) {
            abort(404);
        }

        $this->type = $type;
    }

    /**
     * The projects available for filtering.
     */
    #[Computed]
    public function projects()
    {
        return Project::orderBy('nama')->get();
    }

    /**
     * Build the download URL for the given format with the current filters.
     */
    public function downloadUrl(string $format): string
    {
        return route('exports.'.$this->type, [
            'format' => $format,
            'project_id' => $this->projectId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'status' => $this->status,
        ]);
    }

    /**
     * Render the export page.
     */
    public function render()
    {
        return view('livewire.exports.index');
    }
}
