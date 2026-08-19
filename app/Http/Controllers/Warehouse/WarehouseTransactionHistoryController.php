<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\WarehouseTransactionHistoryRequest;
use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseConsumableCategory;
use App\Services\Warehouse\WarehouseTransactionQueryService;

class WarehouseTransactionHistoryController extends Controller
{
    public function index(
        WarehouseTransactionHistoryRequest $request,
        WarehouseTransactionQueryService $transactions,
    ) {
        $query = $transactions->build($request->validated());
        $totals = (clone $query)->withoutEagerLoads()->reorder()->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE 0 END), 0) as stock_in_quantity")
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'OUT' THEN quantity ELSE 0 END), 0) as stock_out_quantity")
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'ADJUSTMENT' THEN quantity ELSE 0 END), 0) as adjustment_quantity")
            ->first();

        return view('warehouse.transactions.index', [
            'transactions' => $query->paginate(25)->withQueryString(),
            'totals' => $totals,
            'consumables' => WarehouseConsumable::query()->orderBy('item_name')->get(['id', 'item_name', 'item_code']),
            'categories' => WarehouseConsumableCategory::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->where('is_active', config('warehouse.identity.active_user_value', 0))->orderBy('name')->get(['id', 'name']),
            'workspaces' => config('warehouse.history_workspaces', []),
        ]);
    }
}
