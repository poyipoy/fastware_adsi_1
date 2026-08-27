<?php

namespace App\Http\Controllers\Warehouse;

use App\Data\Warehouse\WarehouseDashboardFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\WarehouseDashboardFilterRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseStockAttentionRequest;
use App\Models\Warehouse\WarehouseConsumable;
use App\Services\Warehouse\WarehouseAccessService;
use App\Services\Warehouse\WarehouseDashboardService;

class WarehouseDashboardController extends Controller
{
    public function index(
        WarehouseDashboardFilterRequest $request,
        WarehouseAccessService $access,
        WarehouseDashboardService $dashboard,
    ) {
        $dashboardFilter = WarehouseDashboardFilter::defaultPeriod();
        $trendFilter = WarehouseDashboardFilter::fromTrendRequest($request);
        $currentMonth = WarehouseDashboardFilter::currentMonth();

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
            'trendFilter' => $trendFilter,
            'summary' => $dashboard->summary($dashboardFilter),
            'currentMonthLabel' => $currentMonth->from->copy()->locale('id')->translatedFormat('F Y'),
            'trend' => $dashboard->movementTrendForView($trendFilter),
            'topUsage' => $dashboard->topUsage($trendFilter),
            'topMachineUsage' => $dashboard->topUsageByMachineType($trendFilter),
            'lowStock' => $dashboard->lowStock($dashboardFilter),
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

        return response()->json([
            'summary' => $dashboard->summary($filter),
            'trend' => $dashboard->movementTrend($filter),
            'top_usage' => $dashboard->topUsage($filter),
            'top_usage_by_machine_type' => $dashboard->topUsageByMachineType($filter),
        ]);
    }
}
