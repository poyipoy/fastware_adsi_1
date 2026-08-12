<?php

namespace App\Services\HR;

use App\Models\MstSection;
use App\Models\TcPeopleDevelopment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

class TrainingHistoryQueryService
{
    public function __construct(
        private readonly HRRoleAccessService $roleAccess,
        private readonly JobPositionAccessService $jobPositionAccess,
        private readonly TrainingParticipantService $participants,
    ) {
    }

    public function query(User $actor, array $filters = []): Builder
    {
        abort_unless($this->roleAccess->canAccessTrainingHistory($actor), 403);

        $requestedDepartmentId = ! empty($filters['department_id'])
            ? (int) $filters['department_id']
            : null;

        $query = TcPeopleDevelopment::query()
            ->with([
                'user:id,npk,name,is_active',
                'participants:id,npk,name,is_active',
                'section.department:id,name',
                'jobPosition.department:id,name',
            ])
            ->where('status_2', 'Done');

        if (! $this->roleAccess->hasFullAccess($actor)) {
            $scope = $this->jobPositionAccess->getUserApprovalScope($actor);
            $sectionIds = array_values(array_unique($scope['section_ids']));
            $departmentIds = array_values(array_unique(array_merge($scope['dept_ids'], $scope['div_dept_ids'])));
            $departmentIds = array_values(array_unique(array_merge(
                $departmentIds,
                MstSection::query()->whereIn('id', $sectionIds)->pluck('department_id')->filter()->map(fn ($id) => (int) $id)->all(),
            )));

            if ($requestedDepartmentId !== null && ! in_array($requestedDepartmentId, $departmentIds, true)) {
                abort(403, 'Department berada di luar scope history Training Development Anda.');
            }

            $query->where(function (Builder $visibility) use ($sectionIds, $departmentIds) {
                $visibility->whereIn('section_id', $sectionIds)
                    ->orWhere(function (Builder $sharing) use ($sectionIds, $departmentIds) {
                        $sharing->where('is_sharing_knowledge', true)
                            ->whereNull('section_id')
                            ->where(function (Builder $participantScope) use ($sectionIds, $departmentIds) {
                                $mappingScope = function (Builder $mapping) use ($sectionIds, $departmentIds) {
                                    $mapping->where('is_active', true)
                                        ->where(fn (Builder $dates) => $dates->whereNull('effective_from')->orWhereDate('effective_from', '<=', today()))
                                        ->where(fn (Builder $dates) => $dates->whereNull('effective_until')->orWhereDate('effective_until', '>=', today()))
                                        ->whereHas('jobPosition', fn (Builder $position) => $position
                                            ->where('is_active', true)
                                            ->where(fn (Builder $organization) => $organization
                                                ->whereIn('section_id', $sectionIds)
                                                ->orWhereIn('department_id', $departmentIds)));
                                };

                                $participantScope->whereHas('participants.userJobPositions', $mappingScope)
                                    ->orWhere(function (Builder $legacy) use ($mappingScope) {
                                        $legacy->whereDoesntHave('participants')
                                            ->whereHas('user.userJobPositions', $mappingScope);
                                    });
                            });
                    });
            });
        }

        if ($requestedDepartmentId !== null) {
            $query->where(function (Builder $organization) use ($requestedDepartmentId) {
                $mappingScope = fn (Builder $mapping) => $mapping
                    ->where('is_active', true)
                    ->where(fn (Builder $dates) => $dates->whereNull('effective_from')->orWhereDate('effective_from', '<=', today()))
                    ->where(fn (Builder $dates) => $dates->whereNull('effective_until')->orWhereDate('effective_until', '>=', today()))
                    ->whereHas('jobPosition', fn (Builder $position) => $position
                        ->where('is_active', true)
                        ->where('department_id', $requestedDepartmentId));

                $organization
                    ->whereHas('section', fn (Builder $section) => $section->where('department_id', $requestedDepartmentId))
                    ->orWhereHas('jobPosition', fn (Builder $position) => $position->where('department_id', $requestedDepartmentId))
                    ->orWhere(function (Builder $sharing) use ($mappingScope) {
                        $sharing->where('is_sharing_knowledge', true)
                            ->where(function (Builder $participantScope) use ($mappingScope) {
                                $participantScope->whereHas('participants.userJobPositions', $mappingScope)
                                    ->orWhere(function (Builder $legacy) use ($mappingScope) {
                                        $legacy->whereDoesntHave('participants')
                                            ->whereHas('user.userJobPositions', $mappingScope);
                                    });
                            });
                    });
            });
        }

        $year = $filters['year'] ?? $filters['tahun'] ?? null;
        if (! empty($year)) {
            $query->where('tahun_aktual', (int) $year);
        }

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery->where('program_training', 'like', "%{$search}%")
                    ->orWhere('program_training_plan', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('npk', 'like', "%{$search}%"))
                    ->orWhereHas('participants', fn (Builder $user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('npk', 'like', "%{$search}%"));
            });
        }

        return $query->orderByDesc('tahun_aktual')->orderBy('id');
    }

    public function flattened(User $actor, array $filters = []): LazyCollection
    {
        return LazyCollection::make(function () use ($actor, $filters) {
            $seen = [];

            foreach ($this->query($actor, $filters)->lazy(200) as $training) {
                $users = $training->is_sharing_knowledge
                    ? $this->participants->readableParticipants($training)
                    : collect([$training->user])->filter();

                foreach ($users as $user) {
                    $key = $training->id.':'.$user->id;
                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    yield ['training' => $training, 'participant' => $user];
                }
            }
        });
    }
}
