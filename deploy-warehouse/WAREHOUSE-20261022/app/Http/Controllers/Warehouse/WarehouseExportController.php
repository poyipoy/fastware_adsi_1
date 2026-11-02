<?php

namespace App\Http\Controllers\Warehouse;

use App\Exports\WarehouseTransactionsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\WarehouseTransactionExportRequest;
use App\Services\Warehouse\WarehouseTransactionQueryService;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseExportController extends Controller
{
    public function transactions(
        WarehouseTransactionExportRequest $request,
        WarehouseTransactionQueryService $transactions,
    ) {
        $maxRows = max(1, (int) config('warehouse.export.max_rows', 10000));
        $rows = $transactions->build($request->validated())->limit($maxRows + 1)->get();

        if ($rows->count() > $maxRows) {
            return back()->withErrors(['export' => 'Export melebihi batas '.$maxRows.' baris. Persempit filter terlebih dahulu.']);
        }

        return Excel::download(new WarehouseTransactionsExport($rows), 'warehouse-transactions-'.now()->format('Ymd-His').'.xlsx');
    }
}
