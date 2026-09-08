<?php

namespace App\Http\Controllers;

use App\Exports\AkunTemplateExport;
use App\Exports\CashflowTemplateExport;
use App\Exports\PayableTemplateExport;
use App\Exports\ProjectExport;
use App\Exports\ProjectTemplateExport;
use App\Exports\ReceivableTemplateExport;
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

    /**
     * Download the receivable import template.
     */
    public function receivable()
    {
        return Excel::download(new ReceivableTemplateExport, 'template-receivable.xlsx');
    }

    /**
     * Download the payable import template.
     */
    public function payable(\Illuminate\Http\Request $request)
    {
        $useProjectCode = $request->boolean('use_project_code', true);
        return Excel::download(new PayableTemplateExport($useProjectCode), 'template-payable.xlsx');
    }

    /**
     * Download the cashflow import template.
     */
    public function cashflow()
    {
        return Excel::download(new CashflowTemplateExport, 'template-cashflow.xlsx');
    }
}
