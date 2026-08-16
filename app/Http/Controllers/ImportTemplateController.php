<?php

namespace App\Http\Controllers;

use App\Exports\AkunTemplateExport;
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
     * Download the draft project template (menyesuaikan Excel final klien).
     */
    public function project()
    {
        return Excel::download(new ProjectTemplateExport, 'template-project-draft.xlsx');
    }
}