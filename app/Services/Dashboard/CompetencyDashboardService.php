<?php

namespace App\Services\Dashboard;

use App\Models\TrsPenilaianTc;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Services\HR\JobPositionAccessService;

class CompetencyDashboardService
{
    private JobPositionAccessService $jobPositionAccess;

    public function __construct(JobPositionAccessService $jobPositionAccess)
    {
        $this->jobPositionAccess = $jobPositionAccess;
    }

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
    private function getJobPositions()
    {
        // Ambil ID job position yang sudah pernah dinilai (status 3 atau 4)
        $assessedIds = TrsPenilaianTc::whereIn('status', [3, 4])
            ->distinct()
            ->pluck('id_job_position')
            ->toArray();

        // Admin/Full Access: tampilkan semua job position yang sudah dinilai
        if ($this->jobPositionAccess->hasFullAccess(Auth::user())) {
            return \App\Models\MstJobPosition::whereIn('id', $assessedIds)
                ->pluck('position_name', 'id');
        }

        // Untuk KaSie/KaDept/DivHead: pakai scope approval (bawahan mereka).
        // Param `true` berarti mengecualikan (exclude) job position miliknya sendiri.
        // Sesuai regulasi: Atasan dan User Biasa tidak boleh melihat nilainya sendiri.
        $allowedPositions = $this->jobPositionAccess->getAccessibleJobPositions(Auth::user(), true);

        // Filter hanya yang sudah dinilai, dan langsung kembalikan hasilnya.
        // (Bagi user biasa yang bukan atasan, ini akan otomatis kosong).
        return $allowedPositions
            ->filter(fn($pos) => in_array($pos->id, $assessedIds))
            ->pluck('position_name', 'id');
    }
}

