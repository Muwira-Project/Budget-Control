<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Export existing projects to Excel.
 */
class ProjectExport implements FromCollection, WithHeadings, WithMapping
{
    protected ?string $periode;

    public function __construct(?string $periode = null)
    {
        $this->periode = $periode;
    }

    /**
     * Get the collection of projects to export.
     */
    public function collection()
    {
        return Project::with(['projectCategory', 'budgetPlans', 'projectAkuns'])
            ->when($this->periode, fn ($query) => $query->where('periode', $this->periode))
            ->orderBy('kode')
            ->get();
    }

    /**
     * Map each project to an array for export.
     *
     * @param  Project  $project
     * @return array<int, string|int|float|null>
     */
    public function map($project): array
    {
        return [
            $project->kode,
            $project->nama,
            $project->lokasi,
            $project->devisi,
            $project->pic,
            $project->projectCategory?->nama,
            $project->sub_work,
            $project->periode,
            $project->jenis->value,
            $project->qty,
            $project->satuan,
            $project->harga_satuan,
            $project->pajak,
            $project->tanggal_mulai?->format('Y-m-d'),
            $project->target_selesai?->format('Y-m-d'),
            $project->status->value,
        ];
    }

    /**
     * Return the column headings.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Location',
            'Division',
            'PIC',
            'Project Category',
            'Sub Work',
            'Period',
            'Type',
            'Qty',
            'Unit',
            'Unit Price',
            'Tax %',
            'Start Date',
            'Target Finish',
            'Status',
        ];
    }
}
