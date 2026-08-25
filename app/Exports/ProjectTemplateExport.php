<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/**
 * Draft Excel template for Project master data.
 *
 * Updated with Division (kode), draft status, and proper column order.
 */
class ProjectTemplateExport implements FromArray
{
    /**
     * Template header + contoh baris + catatan untuk import project.
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        return [
            ['Code', 'Name', 'Location', 'Division', 'PIC', 'Project Category', 'Sub Work', 'Period', 'Type', 'Qty', 'Unit', 'Unit Price', 'Tax', 'Start Date', 'Target Finish', 'Status'],
            ['PRJ-001', 'Office Building', 'Jakarta', 'CONSTRUCTION', 'Budi Santoso', 'Konstruksi', 'Foundation work', '2026', 'jasa', 1, 'package', 500000000, 11, '2026-08-01', '2026-12-31', 'draft'],
            ['PRJ-002', 'Toll Road', 'Bandung', 'CIVIL', 'Siti Rahayu', 'Infrastruktur', 'Asphalt work', '2026', 'barang', 5000, 'meter', 250000, 11, '2026-09-01', '2027-03-31', 'progress'],
            [],
            ['NOTE: Updated template with Division column (use MasterItem kode) and Draft status.'],
            ['Type: "barang" (goods) or "jasa" (services). Status: "draft" / "progress" / "done" / "cancelled". Date format: YYYY-MM-DD. Tax in percent (0-100).'],
        ];
    }
}
