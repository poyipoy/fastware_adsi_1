<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Models\KmBookmark;
use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\KmTag;
use App\Models\KmTransaksi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class KmDocumentQueryService
{
    public function __construct(
        private readonly KmAccessService $access,
        private readonly KmApprovalService $approval,
        private readonly KmPointLedgerService $ledger,
        private readonly KmVersioningService $versions,
        private readonly KmRecommendationService $recommendations,
        private readonly KmGamificationService $gamification,
        private readonly KmMandatoryMaterialService $mandatoryMaterials,
    ) {
    }

    public function paginateAuthoring(User $user): LengthAwarePaginator
    {
        $relations = ['user'];
        if ($this->versions->schemaReady()) {
            $relations[] = 'currentVersion';
        }
        $query = KmPengajuan::query()->with($relations)->latest('id');

        if (! $this->access->hasFullAccess($user)) {
            $query->where('id_user', $user->getKey());
        }

        return $query->paginate(25)->withQueryString();
    }

    public function paginateApprovals(string $sort = 'oldest'): LengthAwarePaginator
    {
        $query = KmPengajuan::query()
            ->select('km_pengajuans.*')
            ->addSelect([
                'pending_since' => \App\Models\KmApprovalEvent::query()
                    ->select('acted_at')
                    ->whereColumn('km_pengajuan_id', 'km_pengajuans.id')
                    ->where('action', \App\Enums\KnowledgeManagement\KmApprovalAction::SUBMITTED->value)
                    ->orderByDesc('acted_at')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->with([
                'user',
                'kmKategori',
                'approvalEvents' => fn ($query) => $query->latest('acted_at'),
            ])
            ->whereIn('status', [
                KmDocumentStatus::PENDING_APPROVAL->value,
                KmDocumentStatus::PUBLISHED->value,
            ]);

        $query->orderByRaw(
            'CASE WHEN status = ? THEN 0 ELSE 1 END',
            [KmDocumentStatus::PENDING_APPROVAL->value],
        );
        if ($sort === 'newest') {
            $query->orderByDesc('pending_since')->orderByDesc('id');
        } else {
            $query->orderByRaw('pending_since IS NULL')->orderBy('pending_since')->orderBy('id');
        }

        $paginator = $query
            ->paginate(25)
            ->withQueryString();

        $dueDays = max(1, (int) config('knowledge_management.approval_sla.due_working_days', 3));
        $paginator->getCollection()->each(function (KmPengajuan $document) use ($dueDays): void {
            $waitingDays = $document->pending_since === null
                ? 0
                : $this->approval->workingDaysSince(CarbonImmutable::parse($document->pending_since));
            $document->setAttribute('waiting_working_days', $waitingDays);
            $document->setAttribute(
                'approval_overdue',
                $document->documentStatus() === KmDocumentStatus::PENDING_APPROVAL
                    && $waitingDays >= $dueDays,
            );
        });

        return $paginator;
    }

    public function find(int $documentId): KmPengajuan
    {
        return KmPengajuan::query()->findOrFail($documentId);
    }

    public function findForPayload(int $documentId): KmPengajuan
    {
        return KmPengajuan::query()
            ->with([
                'tags:id,name,slug',
                'coAuthors' => fn ($query) => $query
                    ->orderBy('name')
                    ->select(['users.id', 'users.name', 'users.email']),
                'currentVersion.tags:id,name,slug',
                'currentVersion.coAuthors' => fn ($query) => $query
                    ->orderBy('name')
                    ->select(['users.id', 'users.name']),
            ])
            ->findOrFail($documentId);
    }

    public function findForApprovalPayload(int $documentId): KmPengajuan
    {
        return KmPengajuan::query()
            ->with([
                'tags:id,name,slug',
                'coAuthors' => fn ($query) => $query
                    ->orderBy('name')
                    ->select(['users.id', 'users.name', 'users.email']),
                'approvalEvents.actor',
                'currentVersion.targetDepartments:id,name',
                'currentVersion.targetJobPositions:id,position_name',
            ])
            ->findOrFail($documentId);
    }

    /**
     * @return Collection<int, KmKategori>
     */
    public function categories(): Collection
    {
        return KmKategori::query()
            ->orderBy('nama_kategori')
            ->get(['id', 'nama_kategori']);
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardReferences(User $user): array
    {
        $globalLeaderboard = $this->globalLeaderboard($user);
        $departmentLeaderboard = $this->ledger->departmentLeaderboard(
            $user,
            10,
            (int) config('knowledge_management.points.department_minimum_cohort', 5),
        );

        return [
            ...$this->mandatoryMaterials->dashboardData($user),
            'workspaceSummary' => $this->workspaceSummary($user),
            'leaderboard' => $globalLeaderboard['leaders'],
            'leaderboardPosition' => [
                'global' => [
                    'rank' => $globalLeaderboard['viewer_rank'],
                    'points' => $globalLeaderboard['viewer_points'],
                ],
                'department' => [
                    'available' => ! $departmentLeaderboard['insufficient_cohort']
                        && $departmentLeaderboard['viewer_rank'] !== null,
                    'rank' => $departmentLeaderboard['viewer_rank'],
                    'points' => $departmentLeaderboard['viewer_points'],
                    'reason' => $this->departmentPositionReason($departmentLeaderboard),
                ],
            ],
            'departmentLeaderboard' => $departmentLeaderboard,
            'continueReading' => KmTransaksi::query()
                ->with([
                    'kmPengajuan.kmKategori',
                    'kmPengajuan.currentVersion',
                    'kmPengajuan.publishedVersion',
                    'documentVersion.category',
                ])
                ->where('id_user', $user->getKey())
                ->where('status', KmReadStatus::READING->value)
                ->orderByRaw('COALESCE(last_progress_at, updated_at) DESC')
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->filter(fn (KmTransaksi $transaction): bool => $this->transactionIsReadable($user, $transaction))
                ->take(3)
                ->values(),
            'recommendedMaterials' => $this->recommendations->forUser($user, 6),
            'gamificationProfile' => $this->gamification->profile($user),
            'kategoris' => $this->categories(),
            'tags' => KmTag::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * @return array{reading_count: int, completed_count: int, bookmarked_count: int, points: int}
     */
    private function workspaceSummary(User $user): array
    {
        $readingCounts = KmTransaksi::query()
            ->with(['kmPengajuan.publishedVersion', 'documentVersion'])
            ->where('id_user', $user->getKey())
            ->whereIn('status', [
                KmReadStatus::READING->value,
                KmReadStatus::COMPLETED->value,
            ])
            ->get()
            ->filter(fn (KmTransaksi $transaction): bool => $this->transactionIsReadable($user, $transaction))
            ->countBy('status');

        $bookmarkedCount = KmBookmark::query()
            ->where('user_id', $user->getKey())
            ->whereHas('document', function ($query) use ($user): void {
                $this->access->applyPublishedVisibility($query, $user);
            })
            ->count();

        return [
            'reading_count' => (int) ($readingCounts[KmReadStatus::READING->value] ?? 0),
            'completed_count' => (int) ($readingCounts[KmReadStatus::COMPLETED->value] ?? 0),
            'bookmarked_count' => $bookmarkedCount,
            'points' => (int) ($user->km_total_poin ?? 0),
        ];
    }

    private function transactionIsReadable(User $user, KmTransaksi $transaction): bool
    {
        $document = $transaction->kmPengajuan;
        if ($document === null) {
            return false;
        }
        $version = $transaction->documentVersion;

        return $version === null
            ? $this->access->isPublishedDocumentEligible($user, $document)
            : $this->access->canReadVersion($user, $document, $version);
    }

    /**
     * @return array{leaders: Collection<int, User>, viewer_rank: int, viewer_points: int}
     */
    private function globalLeaderboard(User $user): array
    {
        $viewerPoints = (int) ($user->km_total_poin ?? 0);
        $leaders = User::query()
            ->select(['id', 'name', 'km_total_poin'])
            ->orderByRaw('COALESCE(km_total_poin, 0) DESC')
            ->orderBy('name')
            ->orderBy('id')
            ->limit(10)
            ->get();

        foreach ($leaders as $index => $leader) {
            $leader->setAttribute('leaderboard_rank', $index + 1);
        }

        return [
            'leaders' => $leaders,
            'viewer_rank' => User::query()
                ->where(function ($query) use ($user, $viewerPoints): void {
                    $query->whereRaw('COALESCE(km_total_poin, 0) > ?', [$viewerPoints])
                        ->orWhere(function ($tie) use ($user, $viewerPoints): void {
                            $tie->whereRaw('COALESCE(km_total_poin, 0) = ?', [$viewerPoints])
                                ->where(static function ($deterministic) use ($user): void {
                                    $deterministic->where('name', '<', $user->name)
                                        ->orWhere(static fn ($sameName) => $sameName
                                            ->where('name', $user->name)
                                            ->where('id', '<', $user->getKey()));
                                });
                        });
                })
                ->count() + 1,
            'viewer_points' => $viewerPoints,
        ];
    }

    /**
     * @param  array{department: string|null, cohort_size: int, insufficient_cohort: bool, leaders: mixed, viewer_rank: int|null, viewer_points: int}  $departmentLeaderboard
     */
    private function departmentPositionReason(array $departmentLeaderboard): ?string
    {
        if ($departmentLeaderboard['insufficient_cohort']) {
            return 'Peringkat departemen belum tersedia untuk menjaga privasi kelompok kecil.';
        }

        if ($departmentLeaderboard['viewer_rank'] === null) {
            return 'Anda belum memiliki aktivitas poin pada departemen ini.';
        }

        return null;
    }
}
