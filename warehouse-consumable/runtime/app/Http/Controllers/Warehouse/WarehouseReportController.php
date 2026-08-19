<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\WarehouseReportRequest;
use App\Services\Warehouse\WarehouseReportService;

class WarehouseReportController extends Controller
{
    public function index(WarehouseReportRequest $request, WarehouseReportService $reports)
    {
        $year = (int) ($request->validated('year') ?: now()->year);

        return view('warehouse.reports.index', [
            'report' => $reports->build($year),
            'year' => $year,
        ]);
    }
}
