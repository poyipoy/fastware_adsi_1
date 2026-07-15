<?php

namespace App\Services\Dashboard;

use App\Models\TrsPenilaianTc;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CompetencyDashboardService
{
    /**
     * Get dashboard data for competency
     * 
     * @return array
     */
    public function getDashboardData(): array
    {
        $jobPositions = $this->getJobPositions();

        return [
            'jobPositions' => $jobPositions,
        ];
    }

    /**
     * Get job positions based on user role
     * 
     * @return Collection
     */
    private function getJobPositions(): Collection
    {
        $currentUserRoleId = Auth::user()->role_id;

        if (in_array($currentUserRoleId, [1, 15])) {
            $ids = TrsPenilaianTc::whereIn('status', [3, 4])
                ->distinct()
                ->pluck('id_job_position');
                
            return \App\Models\MstJobPosition::whereIn('id', $ids)
                ->pluck('position_name', 'id');
        }

        // Filter based on role
        $modifiedAtMap = $this->getModifiedAtMap();
        $modifiedAtValues = $modifiedAtMap[$currentUserRoleId] ?? [];

        if (empty($modifiedAtValues)) {
            return collect();
        }

        $ids = TrsPenilaianTc::whereIn('status', [3, 4])
            ->whereIn('modified_at', $modifiedAtValues)
            ->distinct()
            ->pluck('id_job_position');
            
        return \App\Models\MstJobPosition::whereIn('id', $ids)
            ->pluck('position_name', 'id');
    }

    /**
     * Get modified_at mapping based on role
     * 
     * @return array
     */
    private function getModifiedAtMap(): array
    {
        return [
            2 => [65, 45, 99, 59, 72],
            4 => [65, 45, 99, 59, 72],
            44 => [65, 45, 99, 59, 72],
            5 => [25, 84, 102, 46],
            6 => [25, 84, 102, 46],
            8 => [25, 84, 102, 46],
            9 => [25, 84, 102, 46],
            18 => [25, 84, 102, 46],
            21 => [25, 84, 102, 46],
            22 => [25, 84, 102, 46],
            26 => [25, 84, 102, 46],
            27 => [25, 84, 102, 46],
            31 => [25, 84, 102, 46],
            42 => [25, 84, 102, 46, 39, 31, 20, 91, 70],
            43 => [25, 84, 102, 46],
            45 => [25, 84, 102, 46],
            46 => [25, 84, 102, 46],
            48 => [25, 84, 102, 46],
            52 => [25, 84, 102, 46],
            7 => [70, 91, 77, 86, 46, 39],
            29 => [70, 91, 77, 86, 46],
            30 => [70, 91, 77, 86, 46],
            47 => [70, 91, 77, 86, 46],
            49 => [70, 91, 77, 86, 46],
            50 => [70, 91, 77, 86, 46],
            51 => [70, 91, 77, 86, 46],
            11 => [39, 31, 20, 91, 70, 77],
            12 => [39, 31, 20, 91, 70],
            13 => [39, 31, 20, 91, 70],
            14 => [14, 40, 41, 39, 31, 20, 91, 70],
            37 => [39, 31, 20, 91, 70],
        ];
    }
}

