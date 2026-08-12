<?php

namespace App\Policies;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\KmPengajuan;
use App\Models\KmDocumentVersion;
use App\Models\User;
use App\Services\KnowledgeManagement\KmAccessService;

class KmPengajuanPolicy
{
    public function __construct(
        private readonly KmAccessService $access,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, KmPengajuan $document): bool
    {
        return $this->access->canView($user, $document);
    }

    public function download(User $user, KmPengajuan $document): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return $this->access->canCreate($user);
    }

    public function update(User $user, KmPengajuan $document): bool
    {
        return ($document->documentStatus() === KmDocumentStatus::DRAFT
                || $document->hasEditableDraftVersion())
            && ((int) $document->id_user === (int) $user->id || $this->access->hasFullAccess($user));
    }

    public function autosave(User $user, KmPengajuan $document): bool
    {
        return ($document->documentStatus() === KmDocumentStatus::DRAFT
                || $document->hasEditableDraftVersion())
            && (int) $document->id_user === (int) $user->id;
    }

    public function submit(User $user, KmPengajuan $document): bool
    {
        return $this->update($user, $document);
    }

    public function approve(User $user, KmPengajuan $document): bool
    {
        return $document->documentStatus() === KmDocumentStatus::PENDING_APPROVAL
            && $this->access->canApprove($user);
    }

    public function bulkApprove(User $user): bool
    {
        return $this->access->canApprove($user);
    }

    public function viewPopularAnalytics(User $user): bool
    {
        return $this->access->canViewAnalytics($user);
    }

    public function reject(User $user, KmPengajuan $document): bool
    {
        return $this->approve($user, $document);
    }

    public function deactivate(User $user, KmPengajuan $document): bool
    {
        return match ($document->documentStatus()) {
            KmDocumentStatus::DRAFT => (int) $document->id_user === (int) $user->id
                || $this->access->hasFullAccess($user),
            KmDocumentStatus::PUBLISHED => $this->access->canAccessKnowledgeOversight($user)
                || $this->access->hasFullAccess($user),
            default => false,
        };
    }

    public function completeReading(User $user, KmPengajuan $document): bool
    {
        return $this->access->isPublishedDocumentEligible($user, $document);
    }

    public function comment(User $user, KmPengajuan $document): bool
    {
        return $document->documentStatus() === KmDocumentStatus::PUBLISHED
            && $this->access->canView($user, $document);
    }

    public function moderateInsights(User $user, KmPengajuan $document): bool
    {
        return $this->access->canModerateInsights($user)
            || $this->access->hasFullAccess($user);
    }

    public function featureInsight(User $user, KmPengajuan $document): bool
    {
        return (int) $document->id_user === (int) $user->getKey()
            || $this->moderateInsights($user, $document);
    }

    public function revise(User $user, KmPengajuan $document): bool
    {
        return $document->documentStatus() === KmDocumentStatus::PUBLISHED
            && ! $document->hasEditableDraftVersion()
            && ((int) $document->id_user === (int) $user->getKey()
                || $this->access->hasFullAccess($user));
    }

    public function minorRevision(User $user, KmPengajuan $document): bool
    {
        return $document->documentStatus() === KmDocumentStatus::PUBLISHED
            && ($this->access->canAccessKnowledgeOversight($user)
                || $this->access->hasFullAccess($user));
    }

    public function viewVersion(
        User $user,
        KmPengajuan $document,
        KmDocumentVersion $version,
    ): bool {
        if ((int) $version->km_pengajuan_id !== (int) $document->getKey()) {
            return false;
        }
        if ((int) $document->published_version_id === (int) $version->getKey()) {
            return $this->view($user, $document);
        }

        if ($this->access->canReadAssignedVersion($user, $document, $version)) {
            return true;
        }

        return (int) $document->id_user === (int) $user->getKey()
            || $this->access->canApprove($user)
            || $this->access->canAccessKnowledgeOversight($user)
            || $this->access->hasFullAccess($user);
    }

    public function recoverOriginal(User $user, KmPengajuan $document): bool
    {
        return $this->access->canRecoverProcessingOriginal($user);
    }
}
