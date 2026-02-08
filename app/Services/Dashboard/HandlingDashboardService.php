<?php

namespace App\Services\Dashboard;

use App\Enums\FormFppStatus;
use App\Enums\HandlingType;
use App\Enums\ProcessType;
use App\Models\Handling;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HandlingDashboardService
{
    /**
     * Get dashboard data for handling
     * 
     * @param Request $request
     * @return array
     */
    public function getDashboardData(Request $request): array
    {
        $claimData = $this->getClaimData();
        $complainData = $this->getComplainData();
        $countPeriode = $this->getCountPeriode();
        $formattedData = $this->getTypeMaterialData();
        $pieProses = $this->getProcessTypeData();

        return [
            'complainData' => $complainData,
            'countPeriode' => $countPeriode,
            'data2' => array_fill(0, 12, 0),
            'formattedData' => $formattedData,
            'pieProses' => $pieProses,
        ];
    }

    /**
     * Get claim data grouped by month
     * 
     * @return array
     */
    private function getClaimData(): array
    {
        return Handling::whereYear('created_at', date('Y'))
            ->where('type_1', HandlingType::CLAIM->value)
            ->where('status', FormFppStatus::CLOSED->value)
            ->get(['created_at'])
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            })
            ->map(function ($item) {
                return count($item);
            })
            ->toArray();
    }

    /**
     * Get complain data grouped by month
     * 
     * @return array
     */
    private function getComplainData(): array
    {
        return Handling::whereYear('created_at', date('Y'))
            ->where('type_1', HandlingType::COMPLAIN->value)
            ->where('status', FormFppStatus::CLOSED->value)
            ->get(['created_at'])
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            })
            ->map(function ($item) {
                return count($item);
            })
            ->toArray();
    }

    /**
     * Get count per period
     * 
     * @return Collection
     */
    private function getCountPeriode(): Collection
    {
        return Handling::select(
            DB::raw('COUNT(CASE WHEN status_2 = 0 THEN 1 END) as total_status_2_0'),
            DB::raw('COUNT(CASE WHEN status = 3 THEN 1 END) as total_status_3'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('YEAR(created_at) as years')
        )
            ->groupBy('years', 'month')
            ->get();
    }

    /**
     * Get type material data
     * 
     * @return array
     */
    private function getTypeMaterialData(): array
    {
        $tipematerialDS = Handling::join('type_materials', 'handlings.type_id', '=', 'type_materials.id')
            ->select('type_materials.id', 'type_materials.type_name', DB::raw('COUNT(*) as total_type_materials'))
            ->groupBy('type_materials.id', 'type_materials.type_name')
            ->get();

        $formattedData = [];
        foreach ($tipematerialDS as $item) {
            $formattedData[] = [
                'name' => $item->type_name,
                'y' => $item->total_type_materials,
            ];
        }

        return $formattedData;
    }

    /**
     * Get process type data
     * 
     * @return array
     */
    private function getProcessTypeData(): array
    {
        $processes = DB::table('handlings')
            ->select(
                DB::raw('SUM(CASE WHEN handlings.process_type = "' . ProcessType::HEAT_TREATMENT->value . '" THEN 1 ELSE 0 END) AS total_heat_treatment'),
                DB::raw('SUM(CASE WHEN handlings.process_type = "' . ProcessType::CUTTING->value . '" THEN 1 ELSE 0 END) AS total_cutting'),
                DB::raw('SUM(CASE WHEN handlings.process_type = "' . ProcessType::MACHINING->value . '" THEN 1 ELSE 0 END) AS total_machining')
            )
            ->get();

        return [
            [
                'name' => ProcessType::HEAT_TREATMENT->getLabel(),
                'y' => intval($processes[0]->total_heat_treatment ?? 0),
            ],
            [
                'name' => ProcessType::CUTTING->getLabel(),
                'y' => intval($processes[0]->total_cutting ?? 0),
            ],
            [
                'name' => ProcessType::MACHINING->getLabel(),
                'y' => intval($processes[0]->total_machining ?? 0),
            ],
        ];
    }
}

