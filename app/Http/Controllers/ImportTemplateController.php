<?php

namespace App\Http\Controllers;

use App\Exports\AkunTemplateExport;
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
}
