<?php

namespace App\Http\Controllers\Warehouse;

use App\Data\Warehouse\WarehouseDashboardFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\WarehouseDashboardFilterRequest;
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
            'trendFilter' => $trendFilter,
            'summary' => $dashboard->summary($dashboardFilter),
            'currentMonthLabel' => $currentMonth->from->copy()->locale('id')->translatedFormat('F Y'),
            'trend' => $dashboard->movementTrendForView($trendFilter),
            'topUsage' => $dashboard->topUsage($dashboardFilter),
            'lowStock' => $dashboard->lowStock($dashboardFilter),
            'recentTransactions' => $dashboard->recentTransactions($currentMonth),
        ]);
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
        ]);
    }
}
