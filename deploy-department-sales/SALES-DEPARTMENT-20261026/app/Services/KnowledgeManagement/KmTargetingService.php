<?php

namespace App\Services\KnowledgeManagement;

use App\Models\KmDocumentVersion;
use App\Models\User;
use App\Models\UserJobPosition;
use App\Services\HR\DepartmentStructureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class KmTargetingService
{
    public function __construct(
        private readonly KmRbacService $rbac,
    ) {
    }

    public function matches(User $user, KmDocumentVersion $version): bool
    {
        if (! Schema::hasTable('km_document_version_departments')) {
            return true;
        }
        $departmentIds = $version->targetDepartments()->pluck('mst_departments.id')->map('intval');
        $positionIds = $version->targetJobPositions()->pluck('mst_job_positions.id')->map('intval');
        if ($departmentIds->isEmpty() && $positionIds->isEmpty()) {
            return true;
        }
        $activePositionIds = $this->rbac->activePositionIds($user);
        if ($activePositionIds === []) {
            return false;
        }
        if ($positionIds->intersect($activePositionIds)->isNotEmpty()) {
            return true;
        }

        return DB::table('mst_job_positions')
            ->whereIn('id', $activePositionIds)
            ->whereIn('department_id', $departmentIds)
            ->exists();
    }

    /** @param list<int> $departmentIds @param list<int> $positionIds */
    public function sync(KmDocumentVersion $version, array $departmentIds, array $positionIds): void
    {
        DB::transaction(function () use ($version, $departmentIds, $positionIds): void {
            // A request may have passed validation before an organization cutover.
            app(DepartmentStructureService::class)->lockOrganization();
            $departmentIds = array_values(array_unique(array_map('intval', $departmentIds)));
            $positionIds = array_values(array_unique(array_map('intval', $positionIds)));
            if (DB::table('mst_departments')->whereIn('id', $departmentIds)->where('is_active', true)->orderBy('id')->lockForUpdate()->pluck('id')->count() !== count($departmentIds)) {
                throw ValidationException::withMessages(['target_department_ids' => 'Target department berubah atau sudah nonaktif. Muat ulang pilihan target.']);
            }
            if (DB::table('mst_job_positions')->whereIn('id', $positionIds)->where('is_active', true)->orderBy('id')->lockForUpdate()->pluck('id')->count() !== count($positionIds)) {
                throw ValidationException::withMessages(['target_job_position_ids' => 'Target job position berubah atau sudah nonaktif. Muat ulang pilihan target.']);
            }
            $version->targetDepartments()->sync($departmentIds);
            $version->targetJobPositions()->sync($positionIds);
        }, 3);
    }

    /** @return array{department: string|null, position: string|null} */
    public function snapshot(User $user): array
    {
        $position = UserJobPosition::query()
            ->join('mst_job_positions as positions', 'positions.id', '=', 'user_job_positions.mst_job_position_id')
            ->leftJoin('mst_departments as departments', 'departments.id', '=', 'positions.department_id')
            ->where('user_job_positions.user_id', $user->getKey())
            ->activeAt()
            ->orderByDesc('user_job_positions.id')
            ->first(['positions.position_name', 'departments.name as department_name']);

        return [
            'department' => $position?->department_name,
            'position' => $position?->position_name,
        ];
    }
}
