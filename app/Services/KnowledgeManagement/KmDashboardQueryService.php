<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Http\Requests\KnowledgeManagement\KmDashboardFilterRequest;
use App\Models\KmPengajuan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class KmDashboardQueryService
{
    public function __construct(
        private readonly KmAccessService $access,
    ) {
    }

    public function paginate(KmDashboardFilterRequest $request, User $user): LengthAwarePaginator
    {
        $query = KmPengajuan::query()->select('km_pengajuans.*');

        $this->access->applyPublishedVisibility($query, $user);

        $query
            ->with([
                'kmKategori:id,nama_kategori',
                'insights.user:id,name',
                'tags:id,name,slug',
                'coAuthors:id,name',
                'kmTransaksi' => fn ($relation) => $relation
                    ->where('id_user', $user->getKey()),
            ])
            ->withCount('kmSukas')
            ->withSum('kmLihatBukus as total_views', 'jumlah_lihat')
            ->withExists([
                'bookmarks as is_bookmarked' => fn ($relation) => $relation
                    ->where('user_id', $user->getKey()),
                'kmSukas as is_liked' => fn ($relation) => $relation
                    ->where('id_user', $user->getKey()),
            ]);

        if ($request->hasSearchQuery()) {
            $query->whereFullText(
                ['km_pengajuans.judul', 'km_pengajuans.keterangan'],
                (string) $request->validated('q'),
            );
        }

        $tagIds = $request->validated('tag_ids') ?? [];
        if ($tagIds !== []) {
            $query->whereHas('tags', fn ($relation) => $relation
                ->whereIn('km_tags.id', array_map('intval', $tagIds)));
        }

        if ($request->validated('category') !== null) {
            $query->where('id_km_kategori', (int) $request->validated('category'));
        }

        if ($request->validated('date_from') !== null) {
            $query->whereDate('km_pengajuans.created_at', '>=', $request->validated('date_from'));
        }

        if ($request->validated('date_to') !== null) {
            $query->whereDate('km_pengajuans.created_at', '<=', $request->validated('date_to'));
        }

        $readStatus = $request->validated('read_status');
        if ($readStatus === 'unread') {
            $query->whereDoesntHave('kmTransaksi', fn ($relation) => $relation
                ->where('id_user', $user->getKey()));
        } elseif ($readStatus !== null) {
            $status = $readStatus === 'reading'
                ? KmReadStatus::READING
                : KmReadStatus::COMPLETED;

            $query->whereHas('kmTransaksi', fn ($relation) => $relation
                ->where('id_user', $user->getKey())
                ->where('status', $status->value));
        }

        if ($request->validated('bookmarked') === true) {
            $query->whereHas('bookmarks', fn ($relation) => $relation
                ->where('user_id', $user->getKey()));
        }

        match ($request->sortBy()) {
            'relevance' => $query
                ->selectRaw(
                    'MATCH (`km_pengajuans`.`judul`, `km_pengajuans`.`keterangan`) '
                    .'AGAINST (? IN NATURAL LANGUAGE MODE) AS search_relevance',
                    [(string) $request->validated('q')],
                )
                ->orderByDesc('search_relevance')
                ->orderBy('km_pengajuans.id'),
            'oldest' => $query
                ->orderBy('km_pengajuans.created_at')
                ->orderBy('km_pengajuans.id'),
            'title_asc' => $query
                ->orderBy('km_pengajuans.judul')
                ->orderBy('km_pengajuans.id'),
            'popular' => $query
                ->orderByDesc('total_views')
                ->orderByDesc('km_pengajuans.id'),
            default => $query
                ->orderByDesc('km_pengajuans.created_at')
                ->orderByDesc('km_pengajuans.id'),
        };

        return $query->paginate($request->perPage())->withQueryString();
    }
}
