<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\KmTag;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class KmDocumentQueryService
{
    public function __construct(
        private readonly KmAccessService $access,
    ) {
    }

    public function paginateAuthoring(User $user): LengthAwarePaginator
    {
        $query = KmPengajuan::query()->with('user')->latest('id');

        if (! $this->access->hasFullAccess($user)) {
            $query->where('id_user', $user->getKey());
        }

        return $query->paginate(25)->withQueryString();
    }

    public function paginateApprovals(): LengthAwarePaginator
    {
        return KmPengajuan::query()
            ->with([
                'user',
                'kmKategori',
                'approvalEvents' => fn ($query) => $query->latest('acted_at'),
            ])
            ->whereIn('status', [
                KmDocumentStatus::PENDING_APPROVAL->value,
                KmDocumentStatus::PUBLISHED->value,
            ])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
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
     * @return array{leaderboard: Collection<int, User>, kategoris: Collection<int, KmKategori>, tags: Collection<int, KmTag>}
     */
    public function dashboardReferences(): array
    {
        return [
            'leaderboard' => User::query()
                ->select(['id', 'name', 'km_total_poin'])
                ->orderByDesc('km_total_poin')
                ->limit(20)
                ->get(),
            'kategoris' => $this->categories(),
            'tags' => KmTag::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
