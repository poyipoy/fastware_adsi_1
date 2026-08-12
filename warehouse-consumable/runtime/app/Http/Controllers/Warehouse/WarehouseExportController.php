<?php

namespace App\Http\Controllers\Warehouse;

use App\Exports\Warehouse\WarehouseTransactionsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\WarehouseTransactionExportRequest;
use App\Models\Warehouse\WarehouseStockTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseExportController extends Controller
{
    public function transactions(WarehouseTransactionExportRequest $request)
    {
        $query = $this->query($request);
        $maxRows = max(1, (int) config('warehouse.export.max_rows', 10000));
        $rows = $query->limit($maxRows + 1)->get();

        if ($rows->count() > $maxRows) {
            return back()->withErrors(['export' => 'Export melebihi batas '.$maxRows.' baris. Persempit filter terlebih dahulu.']);
        }

        return Excel::download(new WarehouseTransactionsExport($rows), 'warehouse-transactions-'.now()->format('Ymd-His').'.xlsx');
    }

    private function query(WarehouseTransactionExportRequest $request): Builder
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $from = $request->filled('date_from') ? CarbonImmutable::parse($request->input('date_from'), $timezone)->startOfDay() : CarbonImmutable::now($timezone)->subDays((int) config('warehouse.dashboard.default_period_days', 30) - 1)->startOfDay();
        $to = $request->filled('date_to') ? CarbonImmutable::parse($request->input('date_to'), $timezone)->endOfDay() : CarbonImmutable::now($timezone)->endOfDay();
        $query = WarehouseStockTransaction::query()->with(['consumable:id,item_name,item_code,unit', 'creator:id,name'])->whereBetween('transaction_at', [$from, $to])->orderByDesc('transaction_at');

        foreach (['transaction_type', 'consumable_id', 'verified_user_id'] as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->input($column));
            }
        }
        if ($request->filled('category_id')) {
            $query->whereHas('consumable', fn (Builder $builder) => $builder->where('category_id', (int) $request->input('category_id')));
        }
        if ($request->filled('section')) {
            $query->where('verified_user_section', $request->input('section'));
        }
        if ($request->filled('reference_number')) {
            $query->where('reference_number', 'like', '%'.trim((string) $request->input('reference_number')).'%');
        }
        if ($request->filled('transaction_number')) {
            $query->where('transaction_number', 'like', '%'.trim((string) $request->input('transaction_number')).'%');
        }

        return $query;
    }
}
