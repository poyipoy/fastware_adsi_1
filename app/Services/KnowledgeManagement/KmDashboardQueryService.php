<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Http\Requests\KnowledgeManagement\KmDashboardFilterRequest;
use App\Models\KmPengajuan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class KmDashboardQueryService
{
    public function __construct(
        private readonly KmAccessService $access,
        private readonly KmMandatoryMaterialService $mandatoryMaterials,
    ) {
    }

    public function paginate(KmDashboardFilterRequest $request, User $user): LengthAwarePaginator
    {
        $versionSearch = Schema::hasTable('km_document_versions')
            && Schema::hasColumn('km_pengajuans', 'published_version_id');
        $query = KmPengajuan::query()->select('km_pengajuans.*');
        if ($versionSearch) {
            $query->leftJoin(
                'km_document_versions as published_version',
                'published_version.id',
                '=',
                'km_pengajuans.published_version_id',
            );
        }

        $this->access->applyPublishedVisibility($query, $user);

        if ($request->validated('document') !== null) {
            $query->whereKey((int) $request->validated('document'));
        }

        $query
            ->with([
                'kmKategori:id,nama_kategori',
                'tags:id,name,slug',
                'coAuthors:id,name',
                'currentVersion',
                'publishedVersion',
                'publishedVersion.tags:id,name,slug',
                'publishedVersion.coAuthors:id,name',
                'kmTransaksi' => fn ($relation) => $relation
                    ->where('id_user', $user->getKey()),
            ])
            ->withCount(['kmSukas', 'insights'])
            ->withSum('kmLihatBukus as total_views', 'jumlah_lihat')
            ->withExists([
                'bookmarks as is_bookmarked' => fn ($relation) => $relation
                    ->where('user_id', $user->getKey()),
                'kmSukas as is_liked' => fn ($relation) => $relation
                    ->where('id_user', $user->getKey()),
            ]);

        if ($request->hasSearchQuery()) {
            if ($versionSearch) {
                $search = (string) $request->validated('q');
                $query->where(static function ($searchQuery) use ($search): void {
                    $searchQuery->whereRaw(
                        'MATCH (`published_version`.`title`, `published_version`.`synopsis`, '
                        .'`published_version`.`extracted_text`) AGAINST (? IN NATURAL LANGUAGE MODE)',
                        [$search],
                    )->orWhere(static function ($legacy) use ($search): void {
                        $legacy->whereNull('km_pengajuans.published_version_id')
                            ->whereRaw(
                                'MATCH (`km_pengajuans`.`judul`, `km_pengajuans`.`keterangan`) '
                                .'AGAINST (? IN NATURAL LANGUAGE MODE)',
                                [$search],
                            );
                    });
                });
            } else {
                $query->whereFullText(
                    ['km_pengajuans.judul', 'km_pengajuans.keterangan'],
                    (string) $request->validated('q'),
                );
            }
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

        if ($request->validated('mandatory') === true) {
            $mandatoryDocumentIds = $this->mandatoryMaterials->activeDocumentIds($user);
            if ($mandatoryDocumentIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('km_pengajuans.id', $mandatoryDocumentIds);
            }
        }

        match ($request->sortBy()) {
            'relevance' => $query
                ->selectRaw(
                    $versionSearch
                        ? 'GREATEST(COALESCE(MATCH (`published_version`.`title`, `published_version`.`synopsis`, '
                            .'`published_version`.`extracted_text`) AGAINST (? IN NATURAL LANGUAGE MODE), 0), '
                            .'COALESCE(MATCH (`km_pengajuans`.`judul`, `km_pengajuans`.`keterangan`) '
                            .'AGAINST (? IN NATURAL LANGUAGE MODE), 0)) AS search_relevance'
                        : 'MATCH (`km_pengajuans`.`judul`, `km_pengajuans`.`keterangan`) '
                            .'AGAINST (? IN NATURAL LANGUAGE MODE) AS search_relevance',
                    $versionSearch
                        ? [(string) $request->validated('q'), (string) $request->validated('q')]
                        : [(string) $request->validated('q')],
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

        $paginator = $query->paginate($request->perPage())->withQueryString();
        $this->mandatoryMaterials->annotateCatalog($paginator->getCollection(), $user);

        return $paginator;
    }
}
