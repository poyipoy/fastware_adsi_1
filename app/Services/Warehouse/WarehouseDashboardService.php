<?php

namespace App\Services\Warehouse;

use App\Data\Warehouse\WarehouseDashboardFilter;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class WarehouseDashboardService
{
    public function summary(WarehouseDashboardFilter $filter): array
    {
        $active = WarehouseConsumable::query()->where('is_active', true);
        $activeItems = (clone $active)->count();
        $healthy = (clone $active)->where(function (Builder $query): void {
            if (config('warehouse.dashboard.low_stock_inclusive', true)) {
                $query->whereColumn('current_stock', '>', 'minimum_stock');
            } else {
                $query->whereColumn('current_stock', '>=', 'minimum_stock');
            }
        })->where('current_stock', '>', 0)->count();
        $low = (clone $active)->whereColumn('current_stock', '<=', 'minimum_stock')->where('current_stock', '>', 0)->count();
        $out = (clone $active)->where('current_stock', 0)->count();

        $today = CarbonImmutable::now(config('app.timezone', 'Asia/Jakarta'));
        $todayFrom = $today->startOfDay();
        $todayTo = $today->endOfDay();
        $todayQuery = $this->movementQuery(new WarehouseDashboardFilter($todayFrom, $todayTo));
        $currentMonth = WarehouseDashboardFilter::currentMonth();
        $currentMonthQuery = $this->movementQuery($currentMonth);

        return [
            'active_items' => $activeItems,
            'healthy_stock_items' => $healthy,
            'low_stock_items' => $low,
            'out_of_stock_items' => $out,
            'stock_in_today' => $this->movementTotals((clone $todayQuery)->where('transaction_type', WarehouseTransactionType::IN->value)),
            'stock_out_today' => $this->movementTotals((clone $todayQuery)->where('transaction_type', WarehouseTransactionType::OUT->value)),
            'stock_in_month' => $this->movementTotals((clone $currentMonthQuery)->where('transaction_type', WarehouseTransactionType::IN->value)),
            'stock_out_month' => $this->movementTotals((clone $currentMonthQuery)->where('transaction_type', WarehouseTransactionType::OUT->value)),
            'current_month' => [
                'from' => $currentMonth->from->toDateString(),
                'to' => $currentMonth->to->toDateString(),
                'label' => $currentMonth->from->format('F Y'),
            ],
            'period' => [
                'from' => $filter->from->toDateString(),
                'to' => $filter->to->toDateString(),
                'transactions' => (clone $this->movementQuery($filter))->count(),
            ],
        ];
    }

    public function movementTrend(WarehouseDashboardFilter $filter): Collection
    {
        return $this->movementQuery($filter)
            ->whereIn('transaction_type', [WarehouseTransactionType::IN->value, WarehouseTransactionType::OUT->value])
            ->selectRaw('DATE(transaction_at) as movement_date, transaction_type, SUM(quantity) as quantity, COUNT(*) as transaction_count')
            ->groupBy(DB::raw('DATE(transaction_at)'), 'transaction_type')
            ->orderBy('movement_date')
            ->get()
            ->groupBy('movement_date');
    }

    /**
     * Return trend rows in the shape expected by the server-rendered dashboard.
     *
     * The raw trend is intentionally kept unchanged for the JSON endpoint. The
     * Eloquent model casts transaction_type to WarehouseTransactionType, so the
     * view-facing collection is keyed by the enum's persisted value instead of
     * asking Blade to compare an enum object to a plain string.
     */
    public function movementTrendForView(WarehouseDashboardFilter $filter): Collection
    {
        return $this->movementTrend($filter)->map(
            static fn (Collection $rows): Collection => $rows->keyBy(
                static fn (WarehouseStockTransaction $row): string => $row->transaction_type->value,
            ),
        );
    }

    public function topUsage(
        WarehouseDashboardFilter $filter,
        int $limit = 10,
        ?WarehouseItemCondition $condition = null,
    ): Collection {
        $query = $this->movementQuery($filter)
            ->where('transaction_type', WarehouseTransactionType::OUT->value);

        if ($condition !== null) {
            $query->where('item_condition', $condition->value);
        }

        return $query
            ->join('mst_wh_consumables as usage_items', 'usage_items.id', '=', 'trs_wh_stock_transactions.consumable_id')
            ->select('usage_items.id', 'usage_items.item_name', 'usage_items.unit')
            ->selectRaw('SUM(trs_wh_stock_transactions.quantity) as quantity')
            ->groupBy('usage_items.id', 'usage_items.item_name', 'usage_items.unit')
            ->orderByDesc('quantity')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    public function topUsageByMachineType(
        WarehouseDashboardFilter $filter,
        int $limit = 10,
        ?WarehouseItemCondition $condition = null,
    ): Collection {
        $query = $this->movementQuery($filter)
            ->where('transaction_type', WarehouseTransactionType::OUT->value);

        if ($condition !== null) {
            $query->where('item_condition', $condition->value);
        }

        return $query
            ->whereNotNull('trs_wh_stock_transactions.machine_type_used')
            ->where('trs_wh_stock_transactions.machine_type_used', '<>', '')
            ->select('trs_wh_stock_transactions.machine_type_used as machine_type')
            ->selectRaw('SUM(trs_wh_stock_transactions.quantity) as quantity')
            ->groupBy('trs_wh_stock_transactions.machine_type_used')
            ->orderByDesc('quantity')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    public function lowStock(WarehouseDashboardFilter $filter): LengthAwarePaginator
    {
        $query = WarehouseConsumable::query()->with('category:id,name')->where('is_active', true);
        if ($filter->categoryId !== null) {
            $query->where('category_id', $filter->categoryId);
        }
        if ($filter->consumableId !== null) {
            $query->whereKey($filter->consumableId);
        }
        $query->where(function (Builder $builder): void {
            $builder->where('current_stock', 0)->orWhereColumn('current_stock', '<=', 'minimum_stock');
        })->orderByRaw('current_stock = 0 DESC')->orderBy('item_name');

        $paginator = $query->paginate(10, ['*'], 'low_page')->withQueryString();

        $this->attachAverageConsume($paginator->getCollection());

        return $paginator;
    }

    private function attachAverageConsume(Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $tz = config('app.timezone', 'Asia/Jakarta');
        $now = CarbonImmutable::now($tz);
        $consumableIds = $items->pluck('id')->all();

        $earliestCreatedAt = $items->min(
            fn (WarehouseConsumable $item): CarbonImmutable => $item->created_at !== null
                ? CarbonImmutable::parse($item->created_at, $tz)
                : $now
        );
        $earliestCreatedAt ??= $now;
        $globalFrom = $earliestCreatedAt->startOfMonth()->startOfDay();
        $globalTo = $now;

        $totalsByConsumable = $this->movementQuery(new WarehouseDashboardFilter($globalFrom, $globalTo))
            ->where('transaction_type', WarehouseTransactionType::OUT->value)
            ->where('item_condition', WarehouseItemCondition::NEW->value)
            ->whereIn('trs_wh_stock_transactions.consumable_id', $consumableIds)
            ->join('mst_wh_consumables as usage_items', 'usage_items.id', '=', 'trs_wh_stock_transactions.consumable_id')
            ->whereRaw("trs_wh_stock_transactions.transaction_at >= DATE_FORMAT(COALESCE(usage_items.created_at, trs_wh_stock_transactions.transaction_at), '%Y-%m-01 00:00:00')")
            ->where('trs_wh_stock_transactions.transaction_at', '<=', $now)
            ->groupBy('trs_wh_stock_transactions.consumable_id')
            ->select('trs_wh_stock_transactions.consumable_id')
            ->selectRaw('SUM(trs_wh_stock_transactions.quantity) as total_quantity')
            ->pluck('total_quantity', 'consumable_id');

        foreach ($items as $item) {
            $createdDate = $item->created_at !== null
                ? CarbonImmutable::parse($item->created_at, $tz)
                : $now;

            $monthCount = max(1, (($now->year - $createdDate->year) * 12) + ($now->month - $createdDate->month) + 1);
            $totalOutNew = (float) ($totalsByConsumable->get($item->id) ?? 0);

            $item->average_consume = (int) ceil($totalOutNew / $monthCount);
        }
    }

    private function movementQuery(WarehouseDashboardFilter $filter): Builder
    {
        $query = WarehouseStockTransaction::query()
            ->whereBetween('transaction_at', [$filter->from, $filter->to])
            ->whereIn('transaction_type', [
                WarehouseTransactionType::IN->value,
                WarehouseTransactionType::OUT->value,
                WarehouseTransactionType::ADJUSTMENT->value,
            ])
            // A reversed original and its opposite movement cancel as a pair;
            // neither is included in operational dashboard totals.
            ->whereNull('reversal_of_id')
            ->whereNotExists(function ($subquery): void {
                $subquery->selectRaw('1')
                    ->from('trs_wh_stock_transactions as reversal_rows')
                    ->whereColumn('reversal_rows.reversal_of_id', 'trs_wh_stock_transactions.id');
            });

        if ($filter->transactionType !== null) {
            $query->where('transaction_type', $filter->transactionType);
        }
        $this->applyItemFilters($query, $filter);

        return $query;
    }

    private function applyItemFilters(Builder $query, WarehouseDashboardFilter $filter): void
    {
        if ($filter->categoryId !== null) {
            $query->whereHas('consumable', fn (Builder $builder) => $builder->where('category_id', $filter->categoryId));
        }
        if ($filter->consumableId !== null) {
            $query->where('consumable_id', $filter->consumableId);
        }
        if ($filter->section !== null) {
            $query->where('verified_user_section', $filter->section);
        }
        if ($filter->verifiedUserId !== null) {
            $query->where('verified_user_id', $filter->verifiedUserId);
        }
        if ($filter->stockStatus !== null) {
            $query->whereHas('consumable', function (Builder $builder) use ($filter): void {
                match ($filter->stockStatus) {
                    'OUT' => $builder->where('current_stock', 0),
                    'LOW' => $builder->whereColumn('current_stock', '<=', 'minimum_stock')->where('current_stock', '>', 0),
                    'HEALTHY' => $builder->whereColumn('current_stock', '>', 'minimum_stock'),
                    default => null,
                };
            });
        }
    }

    private function movementTotals(Builder $query): array
    {
        $totals = $query->selectRaw('COALESCE(SUM(quantity), 0) as quantity, COUNT(*) as transaction_count')->first();

        return ['quantity' => number_format((float) ($totals?->quantity ?? 0), 3, '.', ''), 'transaction_count' => (int) ($totals?->transaction_count ?? 0)];
    }
}
