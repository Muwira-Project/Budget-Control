<?php

namespace App\Http\Controllers;

use App\Exports\AkunTemplateExport;
use App\Exports\ProjectExport;
use App\Exports\ProjectTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class ImportTemplateController extends Controller
{
    /**
     * Download the akun import template.
     */
    public function akun()
    {
        return Excel::download(new AkunTemplateExport, 'template-account.xlsx');
    }

    /**
     * Download the project template.
     */
    public function project()
    {
        return Excel::download(new ProjectTemplateExport, 'template-project.xlsx');
    }

    /**
     * Export projects to Excel.
     */
    public function exportProjects(?string $periode = null)
    {
        $filename = $periode ? "projects-{$periode}.xlsx" : 'projects-all.xlsx';

        return Excel::download(new ProjectExport($periode), $filename);
    }
}
