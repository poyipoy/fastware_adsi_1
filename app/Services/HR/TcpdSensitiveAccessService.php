<?php

namespace App\Services\HR;

use App\Models\MstDepartment;
use App\Models\User;

class TcpdSensitiveAccessService
{
    public function __construct(
        private readonly HRRoleAccessService $roleAccess,
        private readonly TcpdDashboardAccessService $dashboardAccess,
    ) {
    }

    public function scope(?User $user, mixed $requestedDepartmentId = null, bool $abort = false): array
    {
        $denied = [
            'can_view' => false,
            'access_class' => 'denied',
            'user_id' => $user?->id,
            'department_ids' => [],
        ];

        if (! $user) {
            if ($abort) {
                abort(403, 'Anda tidak berhak melihat data Key Position dan ROI.');
            }

            return $denied;
        }

        if ($this->roleAccess->hasFullAccess($user)) {
            $departmentIds = MstDepartment::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $accessClass = 'full_hr';
        } else {
            $dashboardScope = $this->dashboardAccess->scope($user);
            $departmentIds = in_array($dashboardScope['access_class'], ['division_head', 'department_head'], true)
                ? $dashboardScope['department_ids']
                : [];
            $accessClass = $dashboardScope['access_class'];
        }

        if ($departmentIds === []) {
            if ($abort) {
                abort(403, 'Anda tidak berhak melihat data Key Position dan ROI.');
            }

            return $denied;
        }

        if ($requestedDepartmentId !== null && $requestedDepartmentId !== '') {
            $requested = filter_var($requestedDepartmentId, FILTER_VALIDATE_INT);
            if ($requested === false || ! in_array($requested, $departmentIds, true)) {
                abort(403, 'Department berada di luar scope akses Anda.');
            }
            $departmentIds = [$requested];
        }

        sort($departmentIds);

        return [
            'can_view' => true,
            'access_class' => $accessClass,
            'user_id' => (int) $user->id,
            'department_ids' => $departmentIds,
        ];
    }
}
