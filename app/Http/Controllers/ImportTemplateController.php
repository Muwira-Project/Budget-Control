<?php

namespace App\Http\Controllers;

use App\Exports\AkunTemplateExport;
use App\Exports\RealisasiTemplateExport;
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
     * Download the realisasi import template.
     */
    public function realisasi()
    {
        return Excel::download(new RealisasiTemplateExport, 'template-actual.xlsx');
    }
}
