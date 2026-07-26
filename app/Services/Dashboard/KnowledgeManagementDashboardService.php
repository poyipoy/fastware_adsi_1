<?php

namespace App\Services\Dashboard;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\KnowledgeManagement\KmAccessService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class KnowledgeManagementDashboardService
{
    public function __construct(
        private readonly KmAccessService $access,
    ) {
    }

    /**
     * Get dashboard data for knowledge management
     */
    public function getDashboardData(): array
    {
        $user = Auth::user();
        $pengajuans = $this->getPengajuans($user->id);
        $leaderboard = $this->getLeaderboard();

        return [
            'pengajuans' => $pengajuans,
            'leaderboard' => $leaderboard,
            'documentStatuses' => KmDocumentStatus::class,
            'readStatuses' => KmReadStatus::class,
        ];
    }

    /**
     * Get pengajuans based on role
     *
     * @return Collection
     */
    private function getPengajuans(int $userId): LengthAwarePaginator
    {
        $query = KmPengajuan::with(['kmKategori', 'insights.user', 'kmLihatBukus'])
            ->with(['kmTransaksi' => function ($query) use ($userId) {
                $query->where('id_user', $userId);
            }])
            ->withCount(['kmTransaksi' => function ($query) use ($userId) {
                $query->where('id_user', $userId);
            }])
            ->withCount('kmSukas');

        return $this->access
            ->applyPublishedVisibility($query, Auth::user())
            ->paginate(12, ['*'], 'km_page');
    }

    /**
     * Get leaderboard
     */
    private function getLeaderboard(): Collection
    {
        return User::select('name', 'km_total_poin')
            ->orderBy('km_total_poin', 'desc')
            ->limit(100)
            ->get();
    }
}
