<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/**
 * Draft Excel template for Project master data.
 *
 * NOTE: this is a DRAFT aligned to the current projects schema.
 * Kolom akan disesuaikan dengan Excel final klien (feedback #9) saat sudah tersedia.
 *
 * @implements FromArray
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
            ['Code', 'Name', 'Location', 'PIC', 'Project Category', 'Sub Work', 'Period', 'Type', 'Qty', 'Unit', 'Unit Price', 'Tax %', 'Start Date', 'Target Finish', 'Status'],
            ['PRJ-001', 'Gedung Serbaguna', 'Jakarta', 'Budi Santoso', 'Konstruksi', 'Pekerjaan pondasi', '2026', 'jasa', 1, 'paket', 500000000, 11, '2026-08-01', '2026-12-31', 'progress'],
            [],
            ['NOTE: DRAFT template — akan disesuaikan dengan Excel final klien.'],
            ['Type: "barang" (goods) atau "jasa" (services). Status: "progress" / "done" / "cancelled". Tanggal format: YYYY-MM-DD. Tax dalam persen (0-100).'],
        ];
    }
}
