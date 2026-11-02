<?php

namespace App\Services\Warehouse;

use App\Data\Warehouse\WarehouseDashboardFilter;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseMachineCode;
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
    public function summary(
        WarehouseDashboardFilter $filter,
        WarehouseItemCondition $condition = WarehouseItemCondition::NEW
    ): array {
        $active = WarehouseConsumable::query()->where('is_active', true);
        $activeItems = (clone $active)->count();

        $today = CarbonImmutable::now(config('app.timezone', 'Asia/Jakarta'));
        $todayFrom = $today->startOfDay();
        $todayTo = $today->endOfDay();
        $todayQuery = $this->movementQuery(new WarehouseDashboardFilter($todayFrom, $todayTo), $condition);
        $currentMonth = WarehouseDashboardFilter::currentMonth();
        $currentMonthQuery = $this->movementQuery($currentMonth, $condition);

        if ($condition === WarehouseItemCondition::USED) {
            $totalUsedStock = (float) ((clone $active)->selectRaw('SUM(COALESCE(stock_used_ds8, 0) + COALESCE(stock_used_deltamas, 0)) as total')->value('total') ?? 0.0);
            $activeUsedItems = (clone $active)->whereRaw('(COALESCE(stock_used_ds8, 0) + COALESCE(stock_used_deltamas, 0)) > 0')->count();

            return [
                'condition' => WarehouseItemCondition::USED->value,
                'total_used_stock' => $totalUsedStock,
                'active_used_items' => $activeUsedItems,
                'active_items' => $activeItems,
                'healthy_stock_items' => $activeUsedItems,
                'low_stock_items' => 0,
                'out_of_stock_items' => max(0, $activeItems - $activeUsedItems),
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
                    'transactions' => (clone $this->movementQuery($filter, $condition))->count(),
                ],
            ];
        }

        $healthy = (clone $active)->where(function (Builder $query): void {
            if (config('warehouse.dashboard.low_stock_inclusive', true)) {
                $query->whereColumn('current_stock', '>', 'minimum_stock');
            } else {
                $query->whereColumn('current_stock', '>=', 'minimum_stock');
            }
        })->where('current_stock', '>', 0)->count();
        $low = (clone $active)->whereColumn('current_stock', '<=', 'minimum_stock')->where('current_stock', '>', 0)->count();
        $out = (clone $active)->where('current_stock', 0)->count();

        return [
            'condition' => WarehouseItemCondition::NEW->value,
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
                'transactions' => (clone $this->movementQuery($filter, $condition))->count(),
            ],
        ];
    }

    public function movementTrend(WarehouseDashboardFilter $filter, ?WarehouseItemCondition $condition = null): Collection
    {
        return $this->movementQuery($filter, $condition)
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
    public function movementTrendForView(WarehouseDashboardFilter $filter, ?WarehouseItemCondition $condition = null): Collection
    {
        return $this->movementTrend($filter, $condition)->map(
            static fn (Collection $rows): Collection => $rows->keyBy(
                static fn (WarehouseStockTransaction $row): string => $row->transaction_type->value,
            ),
        );
    }

    public function movementStockChart(WarehouseDashboardFilter $filter, ?WarehouseItemCondition $condition = null): array
    {
        $extendedFrom = $filter->from->subDays(6)->startOfDay();
        $extendedFilter = new WarehouseDashboardFilter(
            from: $extendedFrom,
            to: $filter->to,
            transactionType: null,
            categoryId: $filter->categoryId,
            consumableId: $filter->consumableId,
            section: $filter->section,
            verifiedUserId: $filter->verifiedUserId,
            stockStatus: $filter->stockStatus,
            machineType: $filter->machineType,
        );

        $records = $this->movementQuery($extendedFilter, $condition)
            ->whereIn('transaction_type', [WarehouseTransactionType::IN->value, WarehouseTransactionType::OUT->value])
            ->selectRaw('DATE(transaction_at) as movement_date, transaction_type, SUM(quantity) as quantity')
            ->groupBy(DB::raw('DATE(transaction_at)'), 'transaction_type')
            ->get();

        $dailyIn = [];
        $dailyOut = [];
        foreach ($records as $row) {
            $dateStr = (string) $row->movement_date;
            $typeStr = $row->transaction_type instanceof WarehouseTransactionType
                ? $row->transaction_type->value
                : (string) $row->transaction_type;
            $qty = (float) $row->quantity;

            if ($typeStr === WarehouseTransactionType::IN->value) {
                $dailyIn[$dateStr] = ($dailyIn[$dateStr] ?? 0.0) + $qty;
            } elseif ($typeStr === WarehouseTransactionType::OUT->value) {
                $dailyOut[$dateStr] = ($dailyOut[$dateStr] ?? 0.0) + $qty;
            }
        }

        $labels = [];
        $dates = [];
        $stockIn = [];
        $stockOut = [];
        $average7Days = [];
        $totalIn = 0.0;
        $totalOut = 0.0;

        $cursor = $filter->from->copy();
        $end = $filter->to->copy();
        while ($cursor->lessThanOrEqualTo($end)) {
            $dateStr = $cursor->toDateString();
            $inQty = (float) ($dailyIn[$dateStr] ?? 0.0);
            $outQty = (float) ($dailyOut[$dateStr] ?? 0.0);
            $totalIn += $inQty;
            $totalOut += $outQty;

            $sumOut7 = 0.0;
            for ($i = 0; $i < 7; $i++) {
                $subDateStr = $cursor->subDays($i)->toDateString();
                $sumOut7 += (float) ($dailyOut[$subDateStr] ?? 0.0);
            }
            $avg7 = round($sumOut7 / 7, 2);

            $labels[] = $cursor->format('d/m');
            $dates[] = $dateStr;
            $stockIn[] = $inQty;
            $stockOut[] = $outQty;
            $average7Days[] = $avg7;

            $cursor = $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'dates' => $dates,
            'stock_in' => $stockIn,
            'stock_out' => $stockOut,
            'average_7_days' => $average7Days,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'has_data' => ($totalIn > 0 || $totalOut > 0),
        ];
    }

    public function movementMachineOptions(): Collection
    {
        $dbCodes = DB::table('trs_wh_stock_transaction_machine_usages')
            ->distinct()
            ->pluck('machine_code');

        $rawTrx = WarehouseStockTransaction::query()
            ->whereNotNull('machine_type_used')
            ->where('machine_type_used', '<>', '')
            ->distinct()
            ->pluck('machine_type_used');

        return collect(WarehouseMachineCode::values())
            ->concat($dbCodes)
            ->concat($rawTrx)
            ->flatMap(static fn ($val): array => explode(',', (string) $val))
            ->map(static fn ($val): string => trim((string) $val))
            ->filter(static fn ($val): bool => $val !== '')
            ->unique()
            ->sort(SORT_NATURAL)
            ->values();
    }

    public function movementInsertOptions(?string $machineType = null): Collection
    {
        $query = WarehouseConsumable::query()->where('is_active', true);

        return $query->orderBy('item_name')->get(['id', 'item_code', 'item_name', 'unit', 'current_stock', 'minimum_stock']);
    }

    public function stockOutMachineOptions(): Collection
    {
        $dbCodes = DB::table('trs_wh_stock_transaction_machine_usages')
            ->distinct()
            ->pluck('machine_code');

        $rawTrx = WarehouseStockTransaction::query()
            ->where('transaction_type', WarehouseTransactionType::OUT->value)
            ->whereNotNull('machine_type_used')
            ->where('machine_type_used', '<>', '')
            ->distinct()
            ->pluck('machine_type_used');

        return collect(WarehouseMachineCode::values())
            ->concat($dbCodes)
            ->concat($rawTrx)
            ->flatMap(static fn ($val): array => explode(',', (string) $val))
            ->map(static fn ($val): string => trim((string) $val))
            ->filter(static fn ($val): bool => $val !== '')
            ->unique()
            ->sort(SORT_NATURAL)
            ->values();
    }

    public function topUsage(
        WarehouseDashboardFilter $filter,
        int $limit = 10,
        ?WarehouseItemCondition $condition = null,
        ?string $machineTypeUsed = null,
    ): Collection {
        $query = $this->movementQuery($filter)
            ->where('transaction_type', WarehouseTransactionType::OUT->value);

        if ($condition !== null) {
            $query->where('item_condition', $condition->value);
        }

        $query->join('mst_wh_consumables as usage_items', 'usage_items.id', '=', 'trs_wh_stock_transactions.consumable_id');

        if ($machineTypeUsed !== null && trim($machineTypeUsed) !== '') {
            $trimmed = trim($machineTypeUsed);
            $query->where(function (Builder $builder) use ($trimmed): void {
                $builder->whereExists(function ($sub) use ($trimmed): void {
                    $sub->selectRaw('1')
                        ->from('trs_wh_stock_transaction_machine_usages as mu')
                        ->whereColumn('mu.transaction_id', 'trs_wh_stock_transactions.id')
                        ->where('mu.machine_code', $trimmed);
                })->orWhere('trs_wh_stock_transactions.machine_type_used', $trimmed);
            });
        }

        return $query
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
        $baseQuery = $this->movementQuery($filter)
            ->where('transaction_type', WarehouseTransactionType::OUT->value);

        if ($condition !== null) {
            $baseQuery->where('item_condition', $condition->value);
        }

        $usageResults = DB::table('trs_wh_stock_transaction_machine_usages as mu')
            ->joinSub((clone $baseQuery)->select('trs_wh_stock_transactions.id'), 'filtered_trx', function ($join): void {
                $join->on('mu.transaction_id', '=', 'filtered_trx.id');
            })
            ->select('mu.machine_code as machine_type')
            ->selectRaw('SUM(mu.quantity) as quantity')
            ->groupBy('mu.machine_code')
            ->orderByDesc('quantity')
            ->limit(max(1, min($limit, 100)))
            ->get();

        if ($usageResults->isNotEmpty()) {
            return $usageResults;
        }

        return (clone $baseQuery)
            ->whereNotNull('trs_wh_stock_transactions.machine_type_used')
            ->where('trs_wh_stock_transactions.machine_type_used', '<>', '')
            ->select('trs_wh_stock_transactions.machine_type_used as machine_type')
            ->selectRaw('SUM(trs_wh_stock_transactions.quantity) as quantity')
            ->groupBy('trs_wh_stock_transactions.machine_type_used')
            ->orderByDesc('quantity')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    /**
     * Synchronized Stock Out metrics derived strictly from machine allocations.
     * Guarantees Total Stock Out strictly equals the sum of machine breakdown,
     * hiding machines with zero usage.
     *
     * @return array{total_stock_out: float, machine_breakdown: array<string, float>}
     */
    public function synchronizedStockOutMetrics(
        WarehouseDashboardFilter $filter,
        WarehouseItemCondition $condition,
        ?string $machineCode = null,
    ): array {
        $baseQuery = $this->movementQuery($filter)
            ->where('transaction_type', WarehouseTransactionType::OUT->value)
            ->where('item_condition', $condition->value);

        if ($machineCode !== null && trim($machineCode) !== '') {
            $trimmedMachine = trim($machineCode);
            $baseQuery->where(function (Builder $builder) use ($trimmedMachine): void {
                $builder->whereExists(function ($sub) use ($trimmedMachine): void {
                    $sub->selectRaw('1')
                        ->from('trs_wh_stock_transaction_machine_usages as mu')
                        ->whereColumn('mu.transaction_id', 'trs_wh_stock_transactions.id')
                        ->where('mu.machine_code', $trimmedMachine);
                })->orWhere('trs_wh_stock_transactions.machine_type_used', $trimmedMachine);
            });
        }

        $machineBreakdownQuery = DB::table('trs_wh_stock_transaction_machine_usages as mu')
            ->joinSub($baseQuery->select('trs_wh_stock_transactions.id'), 'filtered_trx', function ($join): void {
                $join->on('mu.transaction_id', '=', 'filtered_trx.id');
            });

        if ($machineCode !== null && trim($machineCode) !== '') {
            $machineBreakdownQuery->where('mu.machine_code', trim($machineCode));
        }

        $rawMachineSums = $machineBreakdownQuery
            ->select('mu.machine_code', DB::raw('SUM(mu.quantity) as total_qty'))
            ->groupBy('mu.machine_code')
            ->pluck('total_qty', 'machine_code');

        $orderedMachineCodes = WarehouseMachineCode::values();
        $breakdown = [];
        $totalStockOut = 0.0;

        foreach ($orderedMachineCodes as $code) {
            $qty = (float) ($rawMachineSums->get($code) ?? 0);
            if ($qty > 0) {
                $breakdown[$code] = $qty;
                $totalStockOut += $qty;
            }
        }

        // If a specific machine filter was requested and had matches outside the 7 standard codes (e.g. legacy names)
        if ($machineCode !== null && trim($machineCode) !== '' && empty($breakdown)) {
            $rawQty = (float) ($rawMachineSums->get(trim($machineCode)) ?? 0);
            if ($rawQty > 0) {
                $breakdown[trim($machineCode)] = $rawQty;
                $totalStockOut = $rawQty;
            }
        }

        return [
            'total_stock_out' => $totalStockOut,
            'machine_breakdown' => $breakdown,
        ];
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

    public function usedStock(WarehouseDashboardFilter $filter): LengthAwarePaginator
    {
        $query = WarehouseConsumable::query()
            ->with('category:id,name')
            ->where('is_active', true);

        if ($filter->categoryId !== null) {
            $query->where('category_id', $filter->categoryId);
        }
        if ($filter->consumableId !== null) {
            $query->whereKey($filter->consumableId);
        }

        $query->where(function (Builder $builder): void {
            $builder->whereRaw('(COALESCE(stock_used_ds8, 0) + COALESCE(stock_used_deltamas, 0)) > 0');
        })
            ->orderByRaw('(COALESCE(stock_used_ds8, 0) + COALESCE(stock_used_deltamas, 0)) DESC')
            ->orderBy('item_name');

        $paginator = $query->paginate(10, ['*'], 'used_page')->withQueryString();

        $this->attachUsedAverageConsume($paginator->getCollection());

        return $paginator;
    }

    private function attachUsedAverageConsume(Collection $items): void
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

        $totalsByConsumable = $this->movementQuery(new WarehouseDashboardFilter($globalFrom, $globalTo), WarehouseItemCondition::USED)
            ->where('transaction_type', WarehouseTransactionType::OUT->value)
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
            $totalOutUsed = (float) ($totalsByConsumable->get($item->id) ?? 0);

            $item->average_consume_used = (int) ceil($totalOutUsed / $monthCount);
        }
    }

    private function movementQuery(WarehouseDashboardFilter $filter, ?WarehouseItemCondition $condition = null): Builder
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

        if ($condition !== null) {
            $query->where('item_condition', $condition->value);
        }

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
        if ($filter->machineType !== null && trim($filter->machineType) !== '') {
            $trimmed = trim($filter->machineType);

            $query->where(function (Builder $builder) use ($trimmed): void {
                $builder->whereExists(function ($sub) use ($trimmed): void {
                    $sub->selectRaw('1')
                        ->from('trs_wh_stock_transaction_machine_usages as mu')
                        ->whereColumn('mu.transaction_id', 'trs_wh_stock_transactions.id')
                        ->where('mu.machine_code', $trimmed);
                })->orWhere('trs_wh_stock_transactions.machine_type_used', $trimmed);
            });
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
