<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\HRMenuAccessGroup;
use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\HR\HRRoleAccessService;
use Illuminate\Database\Eloquent\Builder;

class KmAccessService
{
    private const FULL_PUBLISHED_ROLE_IDS = [1, 15];

    public function __construct(
        private readonly HRRoleAccessService $roleAccess,
    ) {
    }

    public function canCreate(User $user): bool
    {
        return HRMenuAccessGroup::KNOWLEDGE_MANAGEMENT->hasAccessForUser($user);
    }

    public function canApprove(User $user): bool
    {
        return HRMenuAccessGroup::KNOWLEDGE_APPROVAL->hasAccessForUser($user);
    }

    public function hasFullAccess(User $user): bool
    {
        return $this->roleAccess->hasFullAccess($user);
    }

    public function canView(User $user, KmPengajuan $document): bool
    {
        if ($this->hasFullAccess($user) || (int) $document->id_user === (int) $user->id) {
            return true;
        }

        if ($this->canApprove($user)
            && in_array(
                $document->documentStatus(),
                [KmDocumentStatus::PENDING_APPROVAL, KmDocumentStatus::PUBLISHED],
                true,
            )) {
            return true;
        }

        return match ($document->documentStatus()) {
            KmDocumentStatus::PUBLISHED => $this->isPublishedDocumentEligible($user, $document),
            default => false,
        };
    }

    public function isPublishedDocumentEligible(User $user, KmPengajuan $document): bool
    {
        if ($document->documentStatus() !== KmDocumentStatus::PUBLISHED) {
            return false;
        }

        if ($this->hasPublishedVisibilityBypass($user)) {
            return true;
        }

        return in_array((string) $document->posisi, $this->positionsFor($user), true);
    }

    public function applyPublishedVisibility(Builder $query, User $user): Builder
    {
        $query->where('status', KmDocumentStatus::PUBLISHED->value);

        if (! $this->hasPublishedVisibilityBypass($user)) {
            $query->whereIn('posisi', $this->positionsFor($user));
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
}
