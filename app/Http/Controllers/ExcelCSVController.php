<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\EventsImport;

use Maatwebsite\Excel\Facades\Excel;

use App\Models\Event;


class ExcelCSVController extends Controller
{
    public function importExcelCSV(Request $request)
    {
        $validatedData = $request->validate([

            'file' => 'required',

        ]);

        Excel::import(new EventsImport, $request->file('file'));


        return redirect('excel-csv-file')->with('status', 'The file has been excel/csv imported to database in Laravel 10');
    }
}
