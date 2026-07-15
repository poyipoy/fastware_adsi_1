<?php

namespace App\Services\HR;

use App\Models\MstJobPosition;
use App\Models\MstPositionApproval;
use App\Models\MstSection;
use App\Models\UserJobPosition;
use App\Models\User;
use App\Models\UserJobAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class JobPositionAccessService
{
    public function hasFullAccess(User $user): bool
    {
        return app(HRRoleAccessService::class)->hasFullAccess($user);
    }

    public function getUserApprovalScope(User $user): array
    {
        if ($this->hasFullAccess($user)) {
            $sectionIds = \App\Models\MstSection::pluck('id')->toArray();
            $deptIds    = \App\Models\MstDepartment::pluck('id')->toArray();
            return [
                'section_ids'  => $sectionIds,
                'dept_ids'     => $deptIds,
                'div_dept_ids' => $deptIds,
            ];
        }

        $userPosIds = UserJobPosition::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('mst_job_position_id')
            ->toArray();

        if (empty($userPosIds)) {
            return ['section_ids' => [], 'dept_ids' => [], 'div_dept_ids' => []];
        }

        $kasieApproverPositionIds = MstPositionApproval::whereIn('approver_position_id', $userPosIds)
            ->whereIn('approval_level', [0, 1])
            ->pluck('position_id')
            ->toArray();

        $sectionIds = MstJobPosition::whereIn('id', $kasieApproverPositionIds)
            ->whereNotNull('section_id')
            ->pluck('section_id')
            ->unique()
            ->values()
            ->toArray();

        $deptApproverPositionIds = MstPositionApproval::whereIn('approver_position_id', $userPosIds)
            ->where('approval_level', 2)
            ->pluck('position_id')
            ->toArray();

        $deptIds = MstJobPosition::whereIn('id', $deptApproverPositionIds)
            ->whereNotNull('department_id')
            ->pluck('department_id')
            ->unique()
            ->values()
            ->toArray();

        $divApproverPositionIds = MstPositionApproval::whereIn('approver_position_id', $userPosIds)
            ->where('approval_level', 3)
            ->pluck('position_id')
            ->toArray();

        $divDeptIds = MstJobPosition::whereIn('id', $divApproverPositionIds)
            ->whereNotNull('department_id')
            ->pluck('department_id')
            ->unique()
            ->values()
            ->toArray();

        // Fallback scope based on user's own job_level
        $userPositions = MstJobPosition::whereIn('id', $userPosIds)->get();
        foreach ($userPositions as $pos) {
            if ($pos->job_level === 'sec_head' && $pos->section_id) {
                $sectionIds[] = $pos->section_id;
            }
            if ($pos->job_level === 'dept_head' && $pos->department_id) {
                $deptIds[] = $pos->department_id;
            }
            if ($pos->job_level === 'div_head' && $pos->department_id) {
                $divDeptIds[] = $pos->department_id;
            }
        }

        // If the user has department head scope, automatically allow them to access all sections under those departments
        if (!empty($deptIds)) {
            $deptSections = MstSection::whereIn('department_id', $deptIds)->pluck('id')->toArray();
            $sectionIds = array_merge($sectionIds, $deptSections);
        }

        return [
            'section_ids'  => array_unique(array_filter($sectionIds)),
            'dept_ids'     => array_unique(array_filter($deptIds)),
            'div_dept_ids' => array_unique(array_filter($divDeptIds)),
        ];
    }

    public function getAccessibleJobPositions(User $user, bool $excludeSelf = true): Collection
    {
        $activeJobPositions = $this->getActiveJobPositions();

        if ($this->hasFullAccess($user)) {
            return $activeJobPositions;
        }

        // Ambil semua posisi yang dipegang oleh user saat ini
        $userPosIds = UserJobPosition::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('mst_job_position_id')
            ->toArray();

        $approvalScope = $this->getUserApprovalScope($user);
        $overrideNames = $this->getOverrideJobPositionNames($user);

        return $activeJobPositions
            ->filter(function (MstJobPosition $jobPosition) use ($approvalScope, $overrideNames, $userPosIds, $excludeSelf) {
                // Punya akses jika job position berada di section/department yang di-approve oleh user,
                // ATAU nama posisi ada di override access
                $hasAccess = in_array($jobPosition->section_id, $approvalScope['section_ids']) ||
                             in_array($jobPosition->department_id, $approvalScope['dept_ids']) ||
                             in_array($jobPosition->department_id, $approvalScope['div_dept_ids']) ||
                             $overrideNames->contains($this->normalize($jobPosition->position_name));

                if (!$hasAccess) {
                    return false;
                }

                // Sembunyikan (exclude) job position milik diri sendiri
                if ($excludeSelf && in_array($jobPosition->id, $userPosIds)) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    public function getAccessibleJobPositionOptions(User $user, bool $excludeSelf = true): Collection
    {
        $seen = [];

        return $this->getAccessibleJobPositions($user, $excludeSelf)
            ->filter(function (MstJobPosition $jobPosition) use (&$seen) {
                $key = $this->normalize($jobPosition->position_name);

                if ($key === '' || isset($seen[$key])) {
                    return false;
                }

                $seen[$key] = true;
                return true;
            })
            ->map(function (MstJobPosition $jobPosition) {
                // Tambahkan alias job_position untuk kompatibilitas frontend legacy
                $jobPosition->job_position = $jobPosition->position_name;
                // Tambahkan daftar user aktif per posisi untuk dropdown karyawan
                $jobPosition->active_users = $jobPosition->activeUsers()
                    ->get(['users.id', 'users.name'])
                    ->toArray();
                return $jobPosition;
            })
            ->values();
    }

    public function getAccessibleJobPositionNames(User $user, bool $excludeSelf = true): Collection
    {
        return $this->getAccessibleJobPositionOptions($user, $excludeSelf)
            ->pluck('position_name')
            ->map(fn($name) => trim((string) $name))
            ->filter()
            ->values();
    }

    public function getAccessibleSections(User $user, bool $excludeSelf = true): array
    {
        return $this->getAccessibleJobPositions($user, $excludeSelf)
            ->map(function (MstJobPosition $jobPosition) {
                return trim((string) ($jobPosition->section ?? ''));
            })
            ->filter()
            ->unique(fn($section) => $this->normalize($section))
            ->sort(fn($left, $right) => strcasecmp($left, $right))
            ->values()
            ->all();
    }

    /**
     * Ambil daftar MstSection object (dengan id + name) yang dapat diakses user.
     * Digunakan untuk filter berbasis section_id (integer FK).
     */
    public function getAccessibleSectionObjects(User $user, bool $excludeSelf = true): \Illuminate\Support\Collection
    {
        $accessibleJobPositions = $this->getAccessibleJobPositions($user, $excludeSelf);

        if ($this->hasFullAccess($user)) {
            return MstSection::orderBy('name')->get();
        }

        $sectionIds = $accessibleJobPositions
            ->pluck('section_id')
            ->filter()
            ->unique()
            ->values();

        return MstSection::whereIn('id', $sectionIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Ambil array dari section_id (integer) yang dapat diakses user.
     */
    public function getAccessibleSectionIds(User $user, bool $excludeSelf = true): array
    {
        if ($this->hasFullAccess($user)) {
            return MstSection::pluck('id')->toArray();
        }

        return $this->getAccessibleJobPositions($user, $excludeSelf)
            ->pluck('section_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    public function canAccessJobPosition(User $user, $jobPosition): bool
    {
        if (is_numeric($jobPosition)) {
            $jobPosition = MstJobPosition::find($jobPosition)?->position_name;
        }
        return empty($this->getInvalidJobPositions($user, [$jobPosition]));
    }

    public function getInvalidJobPositions(User $user, array $jobPositions): array
    {
        $allowed = $this->getAccessibleJobPositionNames($user)
            ->mapWithKeys(fn($name) => [$this->normalize($name) => $name]);

        $invalid = [];

        foreach ($jobPositions as $jobPosition) {
            $name = trim((string) $jobPosition);
            if (is_numeric($name)) {
                $resolvedName = MstJobPosition::find($name)?->position_name;
                if ($resolvedName) {
                    $name = trim($resolvedName);
                }
            }
            $key = $this->normalize($name);

            if ($key === '' || !$allowed->has($key)) {
                $invalid[] = $name !== '' ? $name : '(kosong)';
            }
        }

        return collect($invalid)
            ->unique(fn($name) => $this->normalize($name))
            ->values()
            ->all();
    }

    protected function getActiveJobPositions(): Collection
    {
        return MstJobPosition::query()
            ->select('mst_job_positions.*')
            ->leftJoin('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
            ->where('mst_job_positions.is_active', true)
            ->orderBy('mst_departments.name')
            ->orderBy('mst_job_positions.position_name')
            ->get();
    }

    protected function getOverrideJobPositionNames(User $user): Collection
    {
        // DISABLED - LEGACY OVERRIDE ACCESS
        return collect();
    }

    protected function normalize(?string $value): string
    {
        return mb_strtoupper(trim((string) $value));
    }
}
