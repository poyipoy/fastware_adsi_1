<?php

namespace App\Http\Controllers\Warehouse;

use App\Data\Warehouse\WarehouseDashboardFilter;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\UpdateWarehouseStockAttentionRequest;
use App\Http\Requests\Warehouse\WarehouseDashboardFilterRequest;
use App\Models\Warehouse\WarehouseConsumable;
use App\Services\Warehouse\WarehouseAccessService;
use App\Services\Warehouse\WarehouseDashboardService;

class WarehouseDashboardController extends Controller
{
    public function index(
        WarehouseDashboardFilterRequest $request,
        WarehouseAccessService $access,
        WarehouseDashboardService $dashboard
    ) {
        $dashboardFilter = WarehouseDashboardFilter::defaultPeriod();
        $movementFilter = WarehouseDashboardFilter::fromMovementRequest($request);
        $stockOutFilter = WarehouseDashboardFilter::fromStockOutMonthRequest($request);
        $stockoutMachine = $request->string('stockout_machine')->trim()->value() ?: null;
        $currentMonth = WarehouseDashboardFilter::currentMonth();

        $conditionParam = strtoupper(trim((string) $request->input('condition', 'NEW')));
        $activeCondition = $conditionParam === 'USED'
            ? WarehouseItemCondition::USED
            : WarehouseItemCondition::NEW;

        $newStockOutMetrics = $dashboard->synchronizedStockOutMetrics(
            $stockOutFilter,
            condition: WarehouseItemCondition::NEW,
            machineCode: $stockoutMachine,
        );
        $usedStockOutMetrics = $dashboard->synchronizedStockOutMetrics(
            $stockOutFilter,
            condition: WarehouseItemCondition::USED,
            machineCode: $stockoutMachine,
        );
        $newTopUsage = $dashboard->topUsage(
            $stockOutFilter,
            condition: WarehouseItemCondition::NEW,
            machineTypeUsed: $stockoutMachine,
        );
        $usedTopUsage = $dashboard->topUsage(
            $stockOutFilter,
            condition: WarehouseItemCondition::USED,
            machineTypeUsed: $stockoutMachine,
        );

        $summary = $dashboard->summary($dashboardFilter, condition: $activeCondition);
        $trend = $dashboard->movementTrendForView($movementFilter, condition: $activeCondition);
        $movementChart = $dashboard->movementStockChart($movementFilter, condition: $activeCondition);
        $lowStock = $dashboard->lowStock($dashboardFilter);
        $usedStock = $dashboard->usedStock($dashboardFilter);

        $activeStockOutMetrics = $activeCondition === WarehouseItemCondition::USED
            ? $usedStockOutMetrics
            : $newStockOutMetrics;
        $activeTopUsage = $activeCondition === WarehouseItemCondition::USED
            ? $usedTopUsage
            : $newTopUsage;
        $topMachineUsage = $dashboard->topUsageByMachineType(
            $stockOutFilter,
            condition: $activeCondition,
        );

        return view('warehouse.dashboard.index', [
            'canManageMaster' => $access->can($request->user(), 'warehouse.master.manage'),
            'canViewTransactions' => $access->can($request->user(), 'warehouse.transaction.view'),
            'canExport' => $access->can($request->user(), 'warehouse.report.export'),
            'canStockIn' => $access->can($request->user(), 'warehouse.stock-in.create'),
            'canStockOut' => $access->can($request->user(), 'warehouse.stock-out.create'),
            'canAdjust' => $access->canAdjust($request->user()),
            'canViewReport' => $access->can($request->user(), 'warehouse.report.view'),
            'canValidateStock' => $access->can($request->user(), 'warehouse.stock-validation.view'),
            'canUpdateStockAttention' => $access->can($request->user(), 'warehouse.stock-attention.update'),
            'activeCondition' => $activeCondition->value,
            'condition' => $activeCondition->value,
            'trendFilter' => $movementFilter,
            'movementFilter' => $movementFilter,
            'stockOutFilter' => $stockOutFilter,
            'stockoutMachine' => $stockoutMachine,
            'summary' => $summary,
            'currentMonthLabel' => $currentMonth->from->copy()->locale('id')->translatedFormat('F Y'),
            'trend' => $trend,
            'movementChart' => $movementChart,
            'movementMachineOptions' => $dashboard->movementMachineOptions(),
            'movementInsertOptions' => $dashboard->movementInsertOptions($movementFilter->machineType),
            'stockOutMachineOptions' => $dashboard->stockOutMachineOptions(),
            'stockOutMetrics' => $activeStockOutMetrics,
            'newStockOutMetrics' => $newStockOutMetrics,
            'usedStockOutMetrics' => $usedStockOutMetrics,
            'newTopUsage' => $newTopUsage,
            'usedTopUsage' => $usedTopUsage,
            'topUsage' => $activeTopUsage,
            'topMachineUsage' => $topMachineUsage,
            'lowStock' => $lowStock,
            'usedStock' => $usedStock,
        ]);
    }

    public function updateStockAttentionNote(
        UpdateWarehouseStockAttentionRequest $request,
        WarehouseConsumable $consumable,
    ) {
        $consumable->forceFill([
            'stock_attention_note' => $request->validated('stock_attention_note'),
            'updated_by' => $request->user()->getKey(),
        ])->save();

        return redirect()
            ->route('warehouse.dashboard')
            ->with('status', 'Catatan perhatian stok untuk '.$consumable->item_name.' berhasil disimpan.');
    }

    public function data(
        WarehouseDashboardFilterRequest $request,
        WarehouseDashboardService $dashboard,
    ) {
        $filter = WarehouseDashboardFilter::fromRequest($request);
        $conditionParam = $request->filled('condition') ? strtoupper(trim((string) $request->input('condition'))) : null;
        $conditionEnum = $conditionParam === 'USED'
            ? WarehouseItemCondition::USED
            : ($conditionParam === 'NEW' ? WarehouseItemCondition::NEW : null);

        return response()->json([
            'summary' => $dashboard->summary($filter, $conditionEnum ?? WarehouseItemCondition::NEW),
            'trend' => $dashboard->movementTrend($filter, $conditionEnum),
            'top_usage' => $dashboard->topUsage($filter, limit: 10, condition: $conditionEnum),
            'top_usage_by_machine_type' => $dashboard->topUsageByMachineType($filter, limit: 10, condition: $conditionEnum),
        ]);
    }
}
