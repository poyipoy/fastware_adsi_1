<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Exports\HandlingsExport;
use App\Models\Handling;
use App\Models\Customer;
use App\Models\TypeMaterial;
use App\Models\ScheduleVisit;
use Maatwebsite\Excel\Facades\Excel;

class ExcelController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function exportExcel(Request $request)
    {
        // Ambil rentang tanggal dari request
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data2 = Handling::select(
            'handlings.no_wo', 
            'customers.customer_code', 
            'customers.name_customer', 
            'customers.area', 
            'type_materials.type_name', 
            'handlings.thickness', 
            'handlings.weight', 
            'handlings.outer_diameter', 
            'handlings.inner_diameter', 
            'handlings.lenght', 
            'handlings.qty', 
            'handlings.pcs', 
            'handlings.category', 
            'handlings.process_type', 
            'handlings.type_1', 
            'handlings.type_2', 
            'handlings.image', 
            'schedule_visits.schedule', 
            'schedule_visits.results', 
            'schedule_visits.due_date', 
            'schedule_visits.pic', 
            'handlings.status',
            'handlings.created_at'
        )
        ->join('schedule_visits', 'handlings.id', '=', 'schedule_visits.handling_id')
        ->join('customers', 'handlings.customer_id', '=', 'customers.id')
        ->join('type_materials', 'handlings.type_id', '=', 'type_materials.id')
        ->where('schedule_visits.status', 3)
        ->get();
        
        // Ekspor data ke Excel
        return Excel::download($data2, 'history.xlsx');
    }
}
