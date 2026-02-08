<?php

namespace App\Services\Dashboard;

use App\Models\KmPengajuan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class KnowledgeManagementDashboardService
{
    /**
     * Get dashboard data for knowledge management
     * 
     * @return array
     */
    public function getDashboardData(): array
    {
        $user = Auth::user();
        $roleId = $user->role_id;

        $pengajuans = $this->getPengajuans($roleId, $user->id);
        $leaderboard = $this->getLeaderboard();

        return [
            'pengajuans' => $pengajuans,
            'leaderboard' => $leaderboard,
        ];
    }

    /**
     * Get pengajuans based on role
     * 
     * @param int|null $roleId
     * @param int $userId
     * @return Collection
     */
    private function getPengajuans(?int $roleId, int $userId): Collection
    {
        $query = KmPengajuan::with(['kmKategori', 'insights.user', 'kmLihatBukus'])
            ->with(['kmTransaksi' => function ($query) use ($userId) {
                $query->where('id_user', $userId);
            }])
            ->withCount(['kmTransaksi' => function ($query) use ($userId) {
                $query->where('id_user', $userId);
            }])
            ->withCount('kmSukas');

        $posisiMap = $this->getPosisiMap();
        $posisi = $posisiMap[$roleId] ?? ['All Employee'];

        return $query->whereIn('posisi', $posisi)
            ->where('status', 3)
            ->get();
    }

    /**
     * Get posisi mapping based on role
     * 
     * @return array
     */
    private function getPosisiMap(): array
    {
        return [
            2 => ['Dept. Head', 'Sec. Head', 'All Employee'],
            5 => ['Dept. Head', 'Sec. Head', 'All Employee'],
            10 => ['Dept. Head', 'Sec. Head', 'All Employee'],
            11 => ['Dept. Head', 'Sec. Head', 'All Employee'],
            3 => ['Sec. Head', 'All Employee'],
            9 => ['Sec. Head', 'All Employee'],
            12 => ['Sec. Head', 'All Employee'],
            14 => ['Sec. Head', 'All Employee'],
            22 => ['Sec. Head', 'All Employee'],
            30 => ['Sec. Head', 'All Employee'],
            31 => ['Sec. Head', 'All Employee'],
            32 => ['Sec. Head', 'All Employee'],
            1 => [], // All positions (no filter)
            15 => [], // All positions (no filter)
        ];
    }

    /**
     * Get leaderboard
     * 
     * @return Collection
     */
    private function getLeaderboard(): Collection
    {
        return User::select('name', 'km_total_poin')
            ->orderBy('km_total_poin', 'desc')
            ->get();
    }
}

