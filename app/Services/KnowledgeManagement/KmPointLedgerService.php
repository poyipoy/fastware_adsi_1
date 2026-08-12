<?php

namespace App\Services\KnowledgeManagement;

use App\Models\KmPointLedgerEntry;
use App\Models\User;
use App\Models\UserJobPosition;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

class KmPointLedgerService
{
    private ?bool $organizationSchemaReady = null;

    /** @var array<int, string|null> */
    private array $departmentSnapshots = [];

    /**
     * @param  array<string, mixed>|null  $notes
     */
    public function award(
        int|User $recipient,
        string $eventType,
        string $eventKey,
        int $points,
        ?int $documentId = null,
        ?int $insightId = null,
        ?array $notes = null,
        int|User|null $createdBy = null,
        ?int $documentVersionId = null,
    ): bool {
        if (DB::transactionLevel() <= 0) {
            throw new LogicException('Award poin KM wajib dijalankan di dalam database transaction.');
        }

        $userId = $recipient instanceof User ? (int) $recipient->getKey() : $recipient;
        $creatorId = $createdBy instanceof User
            ? (int) $createdBy->getKey()
            : ($createdBy === null ? null : (int) $createdBy);
        $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

        $attributes = [
                'user_id' => $userId,
                'event_type' => mb_substr(trim($eventType), 0, 48),
                'event_key' => mb_substr(trim($eventKey), 0, 191),
                'points' => $points,
                'department_snapshot' => $this->departmentSnapshotFor($user),
                'km_pengajuan_id' => $documentId,
                'document_version_id' => $documentVersionId,
                'km_insight_id' => $insightId,
                'notes' => $notes,
                'created_by' => $creatorId,
        ];
        if (Schema::hasColumn('km_point_ledger', 'document_version_id')) {
            $attributes['document_version_id'] = $documentVersionId;
        }

        try {
            KmPointLedgerEntry::query()->create($attributes);
        } catch (QueryException $exception) {
            if ($this->isDuplicateEventKey($exception)) {
                return false;
            }

            throw $exception;
        }

        User::query()
            ->whereKey($userId)
            ->update([
                'km_total_poin' => DB::raw('COALESCE(km_total_poin, 0) + '.(int) $points),
            ]);

        return true;
    }

    public function departmentSnapshotFor(int|User $user): ?string
    {
        $model = $user instanceof User ? $user : User::query()->find($user);
        if ($model === null) {
            return null;
        }
        $userId = (int) $model->getKey();
        if (array_key_exists($userId, $this->departmentSnapshots)) {
            return $this->departmentSnapshots[$userId];
        }

        if ($this->organizationSchemaIsReady()) {
            $department = trim((string) UserJobPosition::query()
                ->join(
                    'mst_job_positions as positions',
                    'positions.id',
                    '=',
                    'user_job_positions.mst_job_position_id',
                )
                ->join(
                    'mst_departments as departments',
                    'departments.id',
                    '=',
                    'positions.department_id',
                )
                ->where('user_job_positions.user_id', $userId)
                ->activeAt()
                ->orderByDesc('user_job_positions.id')
                ->value('departments.name'));
            if ($department !== '') {
                return $this->departmentSnapshots[$userId] = $department;
            }
        }

        $fallback = trim((string) $model->section);

        return $this->departmentSnapshots[$userId] = ($fallback !== '' ? $fallback : null);
    }

    /**
     * @return Collection<int, array{user_id: int, name: string, cached_points: int, ledger_points: int, drift: int}>
     */
    public function reconcile(bool $repair = false): Collection
    {
        $drift = User::query()
            ->leftJoin('km_point_ledger as ledger', 'ledger.user_id', '=', 'users.id')
            ->select(['users.id', 'users.name', 'users.km_total_poin'])
            ->selectRaw('COALESCE(SUM(ledger.points), 0) AS ledger_points')
            ->groupBy('users.id', 'users.name', 'users.km_total_poin')
            ->havingRaw('COALESCE(users.km_total_poin, 0) <> COALESCE(SUM(ledger.points), 0)')
            ->orderBy('users.id')
            ->get()
            ->map(static function (object $row): array {
                $cached = (int) ($row->km_total_poin ?? 0);
                $ledger = (int) $row->ledger_points;

                return [
                    'user_id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'cached_points' => $cached,
                    'ledger_points' => $ledger,
                    'drift' => $cached - $ledger,
                ];
            });

        if ($repair && $drift->isNotEmpty()) {
            DB::transaction(function () use ($drift): void {
                foreach ($drift as $row) {
                    User::query()
                        ->whereKey($row['user_id'])
                        ->lockForUpdate()
                        ->update(['km_total_poin' => $row['ledger_points']]);
                }
            });
        }

        return $drift;
    }

    /**
     * @return array{department: string|null, cohort_size: int, insufficient_cohort: bool, leaders: Collection<int, object>, viewer_rank: int|null, viewer_points: int}
     */
    public function departmentLeaderboard(
        User $viewer,
        int $topN = 10,
        int $minCohort = 5,
    ): array {
        $department = $this->departmentSnapshotFor($viewer);
        if ($department === null) {
            return [
                'department' => null,
                'cohort_size' => 0,
                'insufficient_cohort' => true,
                'leaders' => collect(),
                'viewer_rank' => null,
                'viewer_points' => 0,
            ];
        }

        $base = DB::table('km_point_ledger')
            ->where('department_snapshot', $department);
        $cohortSize = (clone $base)->distinct()->count('user_id');
        if ($cohortSize < $minCohort) {
            return [
                'department' => $department,
                'cohort_size' => $cohortSize,
                'insufficient_cohort' => true,
                'leaders' => collect(),
                'viewer_rank' => null,
                'viewer_points' => 0,
            ];
        }

        $totals = (clone $base)
            ->join('users', 'users.id', '=', 'km_point_ledger.user_id')
            ->select(['users.id as user_id', 'users.name'])
            ->selectRaw('SUM(km_point_ledger.points) AS points')
            ->groupBy('users.id', 'users.name');
        $leaders = (clone $totals)
            ->orderByDesc('points')
            ->orderBy('users.name')
            ->orderBy('users.id')
            ->limit(max(1, min($topN, 100)))
            ->get();

        foreach ($leaders as $index => $leader) {
            $leader->leaderboard_rank = $index + 1;
        }

        $viewerPoints = (int) (clone $base)
            ->where('user_id', $viewer->getKey())
            ->sum('points');
        $viewerHasLedger = (clone $base)
            ->where('user_id', $viewer->getKey())
            ->exists();
        $viewerRank = null;
        if ($viewerHasLedger) {
            $departmentTotals = (clone $base)
                ->join('users', 'users.id', '=', 'km_point_ledger.user_id')
                ->select(['km_point_ledger.user_id', 'users.name'])
                ->selectRaw('SUM(points) AS points')
                ->groupBy('km_point_ledger.user_id', 'users.name');
            $viewerRank = DB::query()
                ->fromSub($departmentTotals, 'department_totals')
                ->where(function ($query) use ($viewer, $viewerPoints): void {
                    $query->where('points', '>', $viewerPoints)
                        ->orWhere(function ($tie) use ($viewer, $viewerPoints): void {
                            $tie->where('points', '=', $viewerPoints)
                                ->where(static function ($deterministic) use ($viewer): void {
                                    $deterministic->where('name', '<', $viewer->name)
                                        ->orWhere(static fn ($sameName) => $sameName
                                            ->where('name', $viewer->name)
                                            ->where('user_id', '<', $viewer->getKey()));
                                });
                        });
                })
                ->count() + 1;
        }

        return [
            'department' => $department,
            'cohort_size' => $cohortSize,
            'insufficient_cohort' => false,
            'leaders' => $leaders,
            'viewer_rank' => $viewerRank,
            'viewer_points' => $viewerPoints,
        ];
    }

    private function isDuplicateEventKey(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            && str_contains(
                strtolower($exception->getMessage()),
                'km_point_ledger_event_key_unique',
            );
    }

    /** @param  list<string>  $columns */
    private function hasColumns(string $table, array $columns): bool
    {
        return collect($columns)->every(
            static fn (string $column): bool => Schema::hasColumn($table, $column),
        );
    }

    private function organizationSchemaIsReady(): bool
    {
        return $this->organizationSchemaReady ??= Schema::hasTable('user_job_positions')
            && Schema::hasTable('mst_job_positions')
            && Schema::hasTable('mst_departments')
            && $this->hasColumns('user_job_positions', ['id', 'user_id', 'mst_job_position_id', 'is_active'])
            && $this->hasColumns('mst_job_positions', ['id', 'department_id'])
            && $this->hasColumns('mst_departments', ['id', 'name']);
    }
}
