<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\HRMenuAccessGroup;
use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmVersionStatus;
use App\Models\KmAssignmentUser;
use App\Models\KmDocumentVersion;
use App\Models\KmPengajuan;
use App\Models\User;
use App\Models\UserJobPosition;
use App\Services\HR\HRRoleAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KmAccessService
{
    private const FULL_PUBLISHED_ROLE_IDS = [1, 15];

    private const APPROVER_POSITION_NAME = 'HRGA & Legal Staff';

    private const SITI_MARIA_ULFA_USER_ID = 91;

    public function __construct(
        private readonly HRRoleAccessService $roleAccess,
        private readonly KmRbacService $rbac,
        private readonly KmTargetingService $targeting,
    ) {
    }

    public function canCreate(User $user): bool
    {
        return true;
    }

    public function canApprove(User $user): bool
    {
        if ((int) $user->getKey() === self::SITI_MARIA_ULFA_USER_ID) {
            return true;
        }

        return UserJobPosition::query()
            ->where('user_id', $user->getKey())
            ->activeAt()
            ->whereHas('jobPosition', static function (Builder $query): void {
                $query->where('position_name', self::APPROVER_POSITION_NAME)
                    ->where('is_active', true);
            })
            ->exists();
    }

    /**
     * Hak pengawasan KM lama tetap dipakai oleh analytics dan moderasi,
     * tetapi tidak lagi memberikan hak untuk menjalankan workflow approval.
     */
    public function canAccessKnowledgeOversight(User $user): bool
    {
        return $this->rbac->allows(
            $user,
            'km.oversight',
            HRMenuAccessGroup::KNOWLEDGE_APPROVAL->hasAccessForUser($user),
        );
    }

    /**
     * Akun login-enabled pada aplikasi lama memakai users.is_active = 0.
     *
     * @return list<int>
     */
    public function eligibleApproverIds(): array
    {
        return User::query()
            ->where('users.is_active', false)
            ->where(static function (Builder $query): void {
                $query->whereKey(self::SITI_MARIA_ULFA_USER_ID)
                    ->orWhereHas('userJobPositions', static function (Builder $assignment): void {
                        $assignment->activeAt()
                            ->whereHas('jobPosition', static function (Builder $position): void {
                                $position->where('position_name', self::APPROVER_POSITION_NAME)
                                    ->where('mst_job_positions.is_active', true);
                            });
                    });
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function hasFullAccess(User $user): bool
    {
        return $this->roleAccess->hasFullAccess($user);
    }

    public function canRecoverProcessingOriginal(User $user): bool
    {
        return $this->rbac->allows(
            $user,
            'km.processing.recover_original',
            $this->hasFullAccess($user),
        );
    }

    public function canManageAccess(User $user): bool
    {
        return $this->rbac->allows($user, 'km.access.manage', $this->hasFullAccess($user));
    }

    public function canModerateInsights(User $user): bool
    {
        return $this->rbac->allows(
            $user,
            'km.insight.moderate',
            $this->canAccessKnowledgeOversight($user),
        );
    }

    public function canViewAnalytics(User $user): bool
    {
        return $this->rbac->allows(
            $user,
            'km.analytics.view',
            $this->canAccessKnowledgeOversight($user),
        );
    }

    public function canManageAssignments(User $user): bool
    {
        return $this->rbac->allows(
            $user,
            'km.assignment.manage',
            $this->hasFullAccess($user),
        );
    }

    public function canOverrideCompletion(User $user): bool
    {
        return $this->rbac->allows(
            $user,
            'km.completion.override',
            $this->hasFullAccess($user),
        );
    }

    public function canExport(User $user): bool
    {
        return $this->rbac->allows(
            $user,
            'km.export',
            $this->hasFullAccess($user),
        );
    }

    public function canView(User $user, KmPengajuan $document): bool
    {
        if ((int) $document->id_user === (int) $user->id) {
            return true;
        }

        $status = $document->documentStatus();

        if ($status === KmDocumentStatus::PENDING_APPROVAL) {
            return $this->canApprove($user);
        }

        if ($this->hasFullAccess($user)) {
            return true;
        }

        if ($status === KmDocumentStatus::PUBLISHED
            && $this->canAccessKnowledgeOversight($user)) {
            return true;
        }

        return match ($status) {
            KmDocumentStatus::PUBLISHED => $this->isPublishedDocumentEligible($user, $document),
            default => false,
        };
    }

    public function isPublishedDocumentEligible(User $user, KmPengajuan $document): bool
    {
        if ($document->documentStatus() !== KmDocumentStatus::PUBLISHED) {
            return false;
        }
        if (Schema::hasTable('km_document_versions')) {
            $version = $document->relationLoaded('publishedVersion')
                ? $document->publishedVersion
                : $document->publishedVersion()->first();
            if ($version !== null) {
                return $this->isDocumentVersionEligible($user, $document, $version);
            }
        }

        if ($this->hasPublishedVisibilityBypass($user)) {
            return true;
        }

        return in_array((string) $document->posisi, $this->positionsFor($user), true);
    }

    public function isDocumentVersionEligible(
        User $user,
        KmPengajuan $document,
        KmDocumentVersion $version,
    ): bool {
        if ($document->documentStatus() !== KmDocumentStatus::PUBLISHED
            || (int) $version->km_pengajuan_id !== (int) $document->getKey()
            || ! $version->isReady()
            || ! in_array($version->version_status, [KmVersionStatus::PUBLISHED, KmVersionStatus::WITHDRAWN], true)
            || ! $this->targeting->matches($user, $version)) {
            return false;
        }

        if ($this->hasPublishedVisibilityBypass($user)) {
            return true;
        }

        return in_array((string) $version->audience, $this->positionsFor($user), true);
    }

    public function canReadAssignedVersion(
        User $user,
        KmPengajuan $document,
        KmDocumentVersion $version,
    ): bool {
        if (! $this->isDocumentVersionEligible($user, $document, $version)
            || ! Schema::hasTable('km_assignment_users')) {
            return false;
        }

        return KmAssignmentUser::query()
            ->where('user_id', $user->getKey())
            ->whereNull('exempted_at')
            ->whereHas('assignment', static fn (Builder $query): Builder => $query
                ->where('status', 'active')
                ->where('document_version_id', $version->getKey()))
            ->exists();
    }

    public function canReadVersion(
        User $user,
        KmPengajuan $document,
        KmDocumentVersion $version,
    ): bool {
        if ((int) $document->published_version_id === (int) $version->getKey()) {
            return $this->isDocumentVersionEligible($user, $document, $version);
        }

        return $this->canReadAssignedVersion($user, $document, $version);
    }

    public function applyPublishedVisibility(Builder $query, User $user): Builder
    {
        $query->where('status', KmDocumentStatus::PUBLISHED->value);

        if (Schema::hasTable('km_document_versions')) {
            $query->where(static fn (Builder $document): Builder => $document
                ->whereNull('published_version_id')
                ->orWhereHas('publishedVersion', static fn (Builder $version): Builder => $version
                    ->where('processing_status', 'ready')));
        }

        if (! $this->hasPublishedVisibilityBypass($user)) {
            $query->whereIn('posisi', $this->positionsFor($user));
            $this->applyOrganizationTargeting($query, $user);
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    public function positionsFor(User $user): array
    {
        return match ((int) $user->role_id) {
            2, 5, 10, 11 => ['Dept. Head', 'Sec. Head', 'All Employee'],
            3, 9, 12, 14, 22, 30, 31, 32 => ['Sec. Head', 'All Employee'],
            default => ['All Employee'],
        };
    }

    private function hasPublishedVisibilityBypass(User $user): bool
    {
        return $this->hasFullAccess($user)
            || in_array((int) $user->role_id, self::FULL_PUBLISHED_ROLE_IDS, true);
    }

    private function applyOrganizationTargeting(Builder $query, User $user): void
    {
        if (! Schema::hasTable('km_document_version_departments')
            || ! Schema::hasTable('km_document_version_job_positions')) {
            return;
        }
        $positionIds = $this->rbac->activePositionIds($user);
        $departmentIds = $positionIds === []
            ? []
            : DB::table('mst_job_positions')->whereIn('id', $positionIds)
                ->pluck('department_id')->filter()->map('intval')->unique()->values()->all();

        $query->where(static function (Builder $target) use ($positionIds, $departmentIds): void {
            $target->where(static function (Builder $none): void {
                $none->whereNotExists(static fn ($sub) => $sub
                    ->selectRaw('1')->from('km_document_version_departments as target_departments')
                    ->whereColumn('target_departments.document_version_id', 'km_pengajuans.published_version_id'))
                    ->whereNotExists(static fn ($sub) => $sub
                        ->selectRaw('1')->from('km_document_version_job_positions as target_positions')
                        ->whereColumn('target_positions.document_version_id', 'km_pengajuans.published_version_id'));
            });
            if ($positionIds !== []) {
                $target->orWhereExists(static fn ($sub) => $sub
                    ->selectRaw('1')->from('km_document_version_job_positions as target_positions')
                    ->whereColumn('target_positions.document_version_id', 'km_pengajuans.published_version_id')
                    ->whereIn('target_positions.job_position_id', $positionIds));
            }
            if ($departmentIds !== []) {
                $target->orWhereExists(static fn ($sub) => $sub
                    ->selectRaw('1')->from('km_document_version_departments as target_departments')
                    ->whereColumn('target_departments.document_version_id', 'km_pengajuans.published_version_id')
                    ->whereIn('target_departments.department_id', $departmentIds));
            }
        });
    }
}
