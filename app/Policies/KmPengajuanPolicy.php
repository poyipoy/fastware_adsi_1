<?php

namespace App\Policies;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\KmPengajuan;
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

    public function create(User $user): bool
    {
        return $this->access->canCreate($user);
    }

    public function update(User $user, KmPengajuan $document): bool
    {
        return $document->documentStatus() === KmDocumentStatus::DRAFT
            && ((int) $document->id_user === (int) $user->id || $this->access->hasFullAccess($user));
    }

    public function autosave(User $user, KmPengajuan $document): bool
    {
        return $document->documentStatus() === KmDocumentStatus::DRAFT
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
        return $this->access->canApprove($user);
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
            KmDocumentStatus::PUBLISHED => $this->access->canApprove($user)
                || $this->access->hasFullAccess($user),
            default => false,
        };
    }

    public function completeReading(User $user, KmPengajuan $document): bool
    {
        return $this->access->isPublishedDocumentEligible($user, $document);
    }
}
