<?php

namespace App\Services\HR;

use App\Enums\HRMenuAccessGroup;
use App\Models\MstJobPosition;
use App\Models\MstPositionApproval;
use App\Models\MstSection;
use App\Models\UserJobPosition;
use App\Models\User;
use App\Models\UserJobAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class JobPositionAccessService
{
    public function canManageMapping(?User $user): bool
    {
        return $user !== null && HRMenuAccessGroup::JOB_POSITION->hasAccessForUser($user);
    }

    public function canViewMapping(?User $user): bool
    {
        if ($this->canManageMapping($user)) {
            return true;
        }

        if (! $user || ! Schema::hasColumns('user_job_positions', ['effective_from', 'effective_until'])) {
            return false;
        }

        return UserJobPosition::query()->where('user_id', $user->id)->activeAt()
            ->whereHas('jobPosition', fn (Builder $positions) => $positions
                ->where('is_active', true)
                ->whereIn('job_level', ['sec_head', 'dept_head', 'div_head']))
            ->exists();
    }

    public function getReadableMappingScope(User $user): Builder
    {
        $query = UserJobPosition::query();

        if ($this->canManageMapping($user)) {
            return $query;
        }

        if (! $this->canViewMapping($user)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->activeAt()
            ->where('user_id', '!=', $user->id)
            ->whereHas('user')
            ->whereIn('mst_job_position_id', $this->readableDescendantPositionIds($user));
    }

    private function readableDescendantPositionIds(User $user): array
    {
        // B6 Option A: approval routes are also the authorized reporting graph.
        // Levels identify approval roles (0 = optional Sub Section Head), not ranks.
        if (! Schema::hasColumns('mst_position_approvals', ['position_id', 'approver_position_id', 'approval_level'])) {
            return [];
        }

        $positions = MstJobPosition::active()->pluck('job_level', 'id')->all();
        $actorPositions = UserJobPosition::query()->where('user_id', $user->id)->activeAt()
            ->whereIn('mst_job_position_id', array_keys($positions))
            ->pluck('mst_job_position_id')->unique()->all();
        $roots = array_values(array_filter($actorPositions,
            fn ($id) => in_array($positions[$id], ['sec_head', 'dept_head', 'div_head'], true)));
        if ($roots === []) {
            return [];
        }

        $slots = [];
        $unsafe = [];
        foreach (MstPositionApproval::query()->get(['position_id', 'approver_position_id', 'approval_level']) as $route) {
            $child = (int) $route->position_id;
            if (! isset($positions[$child])) {
                continue;
            }
            $level = $route->approval_level;
            $parent = $route->approver_position_id === null ? null : (int) $route->approver_position_id;
            // A null parent explicitly means there is no route at this level.
            if (! in_array($level, [0, 1, 2, 3], true)
                || ($parent !== null && (! isset($positions[$parent]) || $parent === $child))) {
                $unsafe[$child] = true;
                continue;
            }
            $slot = $child.':'.$level;
            $slots[$slot][$parent === null ? 'none' : $parent] = $parent;
            if (count($slots[$slot]) > 1) {
                $unsafe[$child] = true;
            }
        }

        $children = [];
        $parents = [];
        foreach ($slots as $slot => $slotParents) {
            $child = (int) explode(':', $slot, 2)[0];
            foreach ($slotParents as $parent) {
                if ($parent !== null) {
                    $children[$parent][$child] = $child;
                    $parents[$child][$parent] = $parent;
                }
            }
        }

        // Kahn's finite pass leaves cycles AND their downstream branch closed.
        $indegree = array_fill_keys(array_keys($positions), 0);
        foreach ($children as $descendants) {
            foreach ($descendants as $child) {
                $indegree[$child]++;
            }
        }
        $queue = array_keys(array_filter($indegree, fn ($degree) => $degree === 0));
        for ($i = 0; $i < count($queue); $i++) {
            foreach ($children[$queue[$i]] ?? [] as $child) {
                if (--$indegree[$child] === 0) {
                    $queue[] = $child;
                }
            }
        }
        $unsafe += array_fill_keys(array_keys(array_filter($indegree, fn ($degree) => $degree > 0)), true);
        // A conflicting/malformed source also closes downstream alternate paths.
        $blocked = $this->mappingGraphClosure(array_keys($unsafe), $children);
        $ancestors = $this->mappingGraphClosure($actorPositions, $parents);
        $excluded = $blocked + $ancestors;
        $safeChildren = [];
        foreach ($children as $parent => $descendants) {
            if (isset($blocked[$parent])) {
                continue;
            }
            foreach ($descendants as $child) {
                if (! isset($excluded[$child])) {
                    $safeChildren[$parent][$child] = $child;
                }
            }
        }
        $safeRoots = array_values(array_filter($roots, fn ($id) => ! isset($blocked[$id])));
        $readable = array_diff_key($this->mappingGraphClosure($safeRoots, $safeChildren), $excluded);
        $ids = array_keys($readable);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    private function mappingGraphClosure(array $roots, array $edges): array
    {
        $seen = array_fill_keys($roots, true);
        $queue = array_keys($seen);
        for ($i = 0; $i < count($queue); $i++) {
            foreach ($edges[$queue[$i]] ?? [] as $next) {
                if (! isset($seen[$next])) {
                    $seen[$next] = true;
                    $queue[] = $next;
                }
            }
        }

        return $seen;
    }

    public function canViewEmployeeWorkingExperience(?User $user, int $employeeId): bool
    {
        return $user !== null && $employeeId > 0 && ($this->canManageMapping($user)
            || $this->getReadableMappingScope($user)->where('user_id', $employeeId)->exists());
    }

    public function canViewCompetencyInformation(?User $user, int $positionId): bool
    {
        if ($user === null || ! HRMenuAccessGroup::TECHNICAL_COMPETENCY->hasAccessForUser($user)) {
            return false;
        }

        // Archived positions remain readable by full HR; write access continues
        // through the active-only canAccessJobPosition/getInvalidJobPositions.
        if ($this->hasFullAccess($user)) {
            return MstJobPosition::whereKey($positionId)->exists();
        }

        return $this->getAccessibleJobPositions($user)->contains(
            fn (MstJobPosition $position) => (int) $position->id === $positionId
        );
    }

    public function canViewAssessmentInformation(?User $user, int $positionId): bool
    {
        if (! $user) {
            return false;
        }

        $roles = app(HRRoleAccessService::class);

        return ($roles->hasFullAccess($user) || $roles->isKaSie($user)
            || $roles->isKaDept($user) || $roles->isDivHead($user))
            && $this->canViewCompetencyInformation($user, $positionId);
    }

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
                    ->where('users.is_active', 0)
                    ->get(['users.id', 'users.npk', 'users.name'])
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
