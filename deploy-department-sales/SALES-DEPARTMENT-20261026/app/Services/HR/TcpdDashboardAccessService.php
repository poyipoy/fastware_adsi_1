<?php

namespace App\Services\HR;

use App\Models\MstJobPosition;
use App\Models\MstPositionApproval;
use App\Models\User;
use App\Models\UserJobPosition;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TcpdDashboardAccessService
{
    public function __construct(private readonly HRRoleAccessService $roleAccess)
    {
    }

    /**
     * @return array{
     *     can_view: bool,
     *     access_class: string,
     *     user_id: int|null,
     *     section_ids: array<int, int>,
     *     department_ids: array<int, int>,
     *     job_position_ids: array<int, int>
     * }
     */
    public function scope(?User $user, bool $abort = false): array
    {
        $denied = [
            'can_view' => false,
            'access_class' => 'denied',
            'user_id' => $user?->getKey() !== null ? (int) $user->getKey() : null,
            'section_ids' => [],
            'department_ids' => [],
            'job_position_ids' => [],
        ];

        if (! $user) {
            return $this->deny($denied, $abort);
        }

        if ($this->roleAccess->hasFullAccess($user)) {
            $jobs = $this->dashboardJobRows();

            return [
                'can_view' => true,
                'access_class' => 'full_hr',
                'user_id' => (int) $user->getKey(),
                'section_ids' => $this->sortedIds($jobs->pluck('section_id')->all()),
                'department_ids' => $this->sortedIds($jobs->pluck('department_id')->all()),
                'job_position_ids' => $this->sortedIds($jobs->pluck('id')->all()),
            ];
        }

        $assignments = $this->activeHeadAssignments($user)
            ->filter(fn ($assignment) => $this->isEligibleHeadAssignment($assignment))
            ->values();

        $departmentHeadIds = $this->sortedIds(
            $assignments
                ->filter(fn ($assignment) => $assignment->jobPosition?->job_level === 'dept_head')
                ->pluck('jobPosition.department_id')
                ->all(),
        );
        $divisionDepartmentIds = $this->divisionDepartmentIds($assignments);
        $departmentIds = $this->sortedIds([
            ...$departmentHeadIds,
            ...$divisionDepartmentIds,
        ]);

        if ($departmentIds !== []) {
            $jobs = $this->dashboardJobRows(departmentIds: $departmentIds);

            return [
                'can_view' => true,
                'access_class' => $divisionDepartmentIds !== [] ? 'division_head' : 'department_head',
                'user_id' => (int) $user->getKey(),
                'section_ids' => $this->sortedIds($jobs->pluck('section_id')->all()),
                'department_ids' => $departmentIds,
                'job_position_ids' => $this->sortedIds($jobs->pluck('id')->all()),
            ];
        }

        $sectionIds = $this->sortedIds(
            $assignments
                ->filter(fn ($assignment) => $assignment->jobPosition?->job_level === 'sec_head')
                ->pluck('jobPosition.section_id')
                ->all(),
        );

        if ($sectionIds === []) {
            return $this->deny($denied, $abort);
        }

        $jobs = $this->dashboardJobRows(sectionIds: $sectionIds);

        return [
            'can_view' => true,
            'access_class' => 'section_head',
            'user_id' => (int) $user->getKey(),
            'section_ids' => $sectionIds,
            'department_ids' => $this->sortedIds($jobs->pluck('department_id')->all()),
            'job_position_ids' => $this->sortedIds($jobs->pluck('id')->all()),
        ];
    }

    public function canView(?User $user): bool
    {
        return $this->scope($user)['can_view'];
    }

    public function canClearCache(?User $user): bool
    {
        return $this->roleAccess->hasFullAccess($user);
    }

    protected function activeHeadAssignments(User $user): Collection
    {
        $query = UserJobPosition::query()
            ->where('user_id', $user->getKey())
            ->where('is_active', true);

        if (Schema::hasColumn('user_job_positions', 'effective_from')) {
            $query->where(fn ($dates) => $dates
                ->whereNull('effective_from')
                ->orWhereDate('effective_from', '<=', today()));
        }

        if (Schema::hasColumn('user_job_positions', 'effective_until')) {
            $query->where(fn ($dates) => $dates
                ->whereNull('effective_until')
                ->orWhereDate('effective_until', '>=', today()));
        }

        $jobColumns = ['id', 'job_level', 'department_id', 'is_active'];
        if (Schema::hasColumn('mst_job_positions', 'section_id')) {
            $jobColumns[] = 'section_id';
        }

        return $query
            ->whereHas('jobPosition', fn ($position) => $position
                ->where('is_active', true)
                ->whereIn('job_level', ['div_head', 'dept_head', 'sec_head']))
            ->with(['jobPosition' => fn ($position) => $position->select($jobColumns)])
            ->get();
    }

    protected function isEligibleHeadAssignment(UserJobPosition $assignment): bool
    {
        if (! $assignment->is_active || ! $assignment->jobPosition?->is_active) {
            return false;
        }

        $effectiveFrom = $assignment->effective_from
            ? Carbon::parse($assignment->effective_from)->startOfDay()
            : null;
        $effectiveUntil = $assignment->effective_until
            ? Carbon::parse($assignment->effective_until)->endOfDay()
            : null;

        if (($effectiveFrom && $effectiveFrom->isAfter(today()))
            || ($effectiveUntil && $effectiveUntil->isBefore(today()))) {
            return false;
        }

        return match ($assignment->jobPosition->job_level) {
            'div_head' => true,
            'dept_head' => $assignment->jobPosition->department_id !== null,
            'sec_head' => $assignment->jobPosition->section_id !== null,
            default => false,
        };
    }

    protected function divisionDepartmentIds(Collection $assignments): array
    {
        $divisionAssignments = $assignments
            ->filter(fn ($assignment) => $assignment->jobPosition?->job_level === 'div_head');
        $divisionPositionIds = $this->sortedIds(
            $divisionAssignments->pluck('jobPosition.id')->all(),
        );

        if ($divisionPositionIds === []) {
            return [];
        }

        $directDepartmentIds = $divisionAssignments
            ->pluck('jobPosition.department_id')
            ->all();

        if (! Schema::hasTable('mst_position_approvals')) {
            return $this->sortedIds($directDepartmentIds);
        }

        $approvalDepartmentIds = MstPositionApproval::query()
            ->join(
                'mst_job_positions as scoped_positions',
                'scoped_positions.id',
                '=',
                'mst_position_approvals.position_id',
            )
            ->whereIn('mst_position_approvals.approver_position_id', $divisionPositionIds)
            ->where('mst_position_approvals.approval_level', 3)
            ->where('scoped_positions.is_active', true)
            ->whereNotNull('scoped_positions.department_id')
            ->pluck('scoped_positions.department_id')
            ->all();

        return $this->sortedIds([
            ...$directDepartmentIds,
            ...$approvalDepartmentIds,
        ]);
    }

    protected function dashboardJobRows(
        ?array $departmentIds = null,
        ?array $sectionIds = null,
    ): Collection {
        if ($sectionIds !== null && ! Schema::hasColumn('mst_job_positions', 'section_id')) {
            return collect();
        }

        $columns = ['id', 'department_id'];
        $columns[] = Schema::hasColumn('mst_job_positions', 'section_id')
            ? 'section_id'
            : DB::raw('NULL as section_id');

        return MstJobPosition::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(position_name) NOT LIKE ?', ['%head%'])
            ->when($departmentIds !== null, fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->when($sectionIds !== null, fn ($query) => $query->whereIn('section_id', $sectionIds))
            ->get($columns);
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, int>
     */
    private function sortedIds(array $ids): array
    {
        $ids = collect($ids)
            ->filter(fn ($id) => $id !== null && filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        sort($ids);

        return $ids;
    }

    private function deny(array $scope, bool $abort): array
    {
        if ($abort) {
            abort(403, 'Anda tidak berhak mengakses Dashboard TCPD.');
        }

        return $scope;
    }
}
