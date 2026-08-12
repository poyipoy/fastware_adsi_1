<?php

namespace App\Services\Dashboard;

use App\Models\MstJobPosition;
use App\Models\User;
use App\Services\HR\TcpdDashboardAccessService;

class TcpdAccessResolver
{
    /**
     * Resolve daftar nama job position aktif yang berada dalam scope user.
     *
     * @return array<int, string>
     */
    public static function resolve(?User $user): array
    {
        $scope = app(TcpdDashboardAccessService::class)->scope($user);

        if (! $scope['can_view'] || $scope['job_position_ids'] === []) {
            return [];
        }

        return MstJobPosition::query()
            ->whereIn('id', $scope['job_position_ids'])
            ->where('is_active', true)
            ->orderBy('position_name')
            ->pluck('position_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
