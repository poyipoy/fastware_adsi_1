<?php

namespace App\Services\HR;

use App\Models\TcPeopleDevelopment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TrainingExportQueryService
{
    public function __construct(
        private readonly HRRoleAccessService $roleAccess,
        private readonly TrainingHistoryQueryService $history,
    ) {
    }

    public function submissions(User $actor, array $filters): Builder
    {
        abort_unless($this->roleAccess->canAccessTrainingDevelopment($actor), 403);

        $query = $this->base();
        if (! $this->roleAccess->hasFullAccess($actor)) {
            $query->where('modified_at', $actor->name);
        }

        return $this->filters($query, $filters);
    }

    public function approvals(User $actor, array $filters): Builder
    {
        abort_unless($this->roleAccess->canApproveTrainingDevelopment($actor), 403);

        return $this->filters($this->base()->whereIn('status_1', [2, 3]), $filters);
    }

    public function followUp(User $actor, int $year, array $filters): Builder
    {
        abort_unless($this->roleAccess->canApproveTrainingDevelopment($actor), 403);

        return $this->filters($this->base()->where('tahun_aktual', $year), $filters);
    }

    public function history(User $actor, array $filters): Builder
    {
        return $this->history->query($actor, $filters);
    }

    private function base(): Builder
    {
        return TcPeopleDevelopment::query()->with([
            'user:id,npk,name',
            'participants:id,npk,name',
            'section.department:id,name',
            'jobPosition.department:id,name',
        ]);
    }

    private function filters(Builder $query, array $filters): Builder
    {
        $year = $filters['year'] ?? $filters['tahun'] ?? null;
        if ($year !== null && $year !== '') {
            $query->where('tahun_aktual', (int) $year);
        }

        if (($filters['status_1'] ?? '') !== '') {
            $query->where('status_1', (int) $filters['status_1']);
        }
        if (($filters['status_2'] ?? '') !== '') {
            $query->where('status_2', $filters['status_2']);
        }
        if (($filters['kategori'] ?? '') !== '') {
            $query->where('kategori_competency', $filters['kategori']);
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
}
