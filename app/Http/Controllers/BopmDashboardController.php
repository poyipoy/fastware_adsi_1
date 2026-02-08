<?php

namespace App\Http\Controllers;

use App\Http\Requests\BopmDashboardFilterRequest;
use App\Services\Dashboard\BopmDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class BopmDashboardController extends Controller
{
    public function __construct(
        private BopmDashboardService $bopmDashboardService
    ) {
    }

    /**
     * Display BOPM dashboard
     */
    public function index(): View
    {
        $filterData = $this->bopmDashboardService->getFilterData();
        
        return view('bopm.dashboardBOPM', $filterData);
    }

    /**
     * Get chart data via AJAX
     */
    public function getChartData(BopmDashboardFilterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $chartData = $this->bopmDashboardService->getChartData(
                $validated['start_year'] ?? null,
                $validated['end_year'] ?? null,
                $validated['material_id'] ?? null
            );

            dd($chartData);

            return response()->json([
                'success' => true,
                'data' => $chartData,
            ]);
        } catch (\Exception $e) {
            Log::error('BOPM Chart Data Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data chart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get table data via AJAX
     */
    public function getTableData(BopmDashboardFilterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $tableData = $this->bopmDashboardService->getTableData(
                $validated['start_year'] ?? null,
                $validated['end_year'] ?? null,
                $validated['material_id'] ?? null
            );

            // Convert Collection to array for JSON response
            return response()->json([
                'success' => true,
                'data' => $tableData->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('BOPM Table Data Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data tabel: ' . $e->getMessage(),
            ], 500);
        }
    }
}

