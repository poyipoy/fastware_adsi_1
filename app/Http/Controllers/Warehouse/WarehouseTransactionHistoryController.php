<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\WarehouseTransactionHistoryRequest;
use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseConsumableCategory;
use App\Models\Warehouse\WarehouseStockTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class WarehouseTransactionHistoryController extends Controller
{
    public function index(WarehouseTransactionHistoryRequest $request)
    {
        return view('warehouse.transactions.index', [
            'transactions' => $this->query($request)->paginate(25)->withQueryString(),
            'consumables' => WarehouseConsumable::query()->orderBy('item_name')->get(['id', 'item_name', 'item_code']),
            'categories' => WarehouseConsumableCategory::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->where('is_active', config('warehouse.identity.active_user_value', 0))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function query(WarehouseTransactionHistoryRequest $request): Builder
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $from = $request->filled('date_from')
            ? CarbonImmutable::parse($request->input('date_from'), $timezone)->startOfDay()
            : CarbonImmutable::now($timezone)->subDays((int) config('warehouse.dashboard.default_period_days', 30) - 1)->startOfDay();
        $to = $request->filled('date_to')
            ? CarbonImmutable::parse($request->input('date_to'), $timezone)->endOfDay()
            : CarbonImmutable::now($timezone)->endOfDay();

        $query = WarehouseStockTransaction::query()
            ->with(['consumable:id,item_name,item_code,unit,category_id', 'creator:id,name'])
            ->whereBetween('transaction_at', [$from, $to])
            ->orderByDesc('transaction_at');

        foreach (['transaction_type', 'consumable_id', 'verified_user_id', 'verified_user_section'] as $column) {
            $input = $column === 'verified_user_section' ? 'section' : $column;
            if ($request->filled($input)) {
                $query->where($column, $request->input($input));
            }
        }
        if ($request->filled('category_id')) {
            $query->whereHas('consumable', fn (Builder $builder) => $builder->where('category_id', (int) $request->input('category_id')));
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
