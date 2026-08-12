<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\KmTag;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class KmPopularMaterialReportService
{
    public const EXPORT_LIMIT = 10_000;

    public function __construct(
        private readonly KmAccessService $access,
    ) {
    }

    /**
     * @param  array{category: int|null, tag_ids: list<int>}  $filters
     */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorize($actor);

        return $this->query($filters)->paginate(25)->withQueryString();
    }

    /**
     * @param  array{category: int|null, tag_ids: list<int>}  $filters
     * @return array{
     *     rows: Collection<int, array<string, int|string>>,
     *     generated_at: Carbon,
     *     filters: array{category: int|null, tag_ids: list<int>},
     *     limit_reached: bool,
     *     truncated: bool
     * }
     */
    public function exportReport(User $actor, array $filters): array
    {
        $this->authorize($actor);

        $limit = $this->exportLimit();
        $documents = $this->query($filters)
            ->limit($limit + 1)
            ->get();
        $limitReached = $documents->count() >= $limit;
        $truncated = $documents->count() > $limit;

        return [
            'rows' => $documents
                ->take($limit)
                ->map(fn (KmPengajuan $document): array => $this->row($document))
                ->values(),
            'generated_at' => $this->generatedAt(),
            'filters' => $filters,
            'limit_reached' => $limitReached,
            'truncated' => $truncated,
        ];
    }

    /**
     * @return array{categories: Collection<int, KmKategori>, tags: Collection<int, KmTag>}
     */
    public function filterOptions(): array
    {
        return [
            'categories' => KmKategori::query()->orderBy('nama_kategori')->get(['id', 'nama_kategori']),
            'tags' => KmTag::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function generatedAt(): Carbon
    {
        return now()->timezone('Asia/Jakarta');
    }

    public function exportLimit(): int
    {
        return self::EXPORT_LIMIT;
    }

    /**
     * @return array<string, int|string>
     */
    public function row(KmPengajuan $document): array
    {
        return [
            'id' => (int) $document->getKey(),
            'judul' => (string) $document->judul,
            'kategori' => (string) ($document->kmKategori?->nama_kategori ?? '-'),
            'tags' => $document->tags->pluck('name')->sort()->implode(', '),
            'total_views' => (int) ($document->total_views ?? 0),
            'completed_readers' => (int) ($document->completed_readers ?? 0),
            'likes_count' => (int) ($document->likes_count ?? 0),
        ];
    }

    /**
     * @param  array{category: int|null, tag_ids: list<int>}  $filters
     */
    private function query(array $filters): Builder
    {
        $query = KmPengajuan::query()
            ->select('km_pengajuans.*')
            ->where('km_pengajuans.status', KmDocumentStatus::PUBLISHED->value)
            ->with([
                'kmKategori:id,nama_kategori',
                'tags' => fn ($relation) => $relation->select('km_tags.id', 'name')->orderBy('name'),
            ])
            ->selectSub(function ($subquery): void {
                $subquery->from('km_lihat_bukus')
                    ->selectRaw('COALESCE(SUM(`jumlah_lihat`), 0)')
                    ->whereColumn('km_lihat_bukus.id_km_pengajuan', 'km_pengajuans.id');
            }, 'total_views')
            ->selectSub(function ($subquery): void {
                $subquery->from('km_transaksis')
                    ->selectRaw('COUNT(DISTINCT `id_user`)')
                    ->whereColumn('km_transaksis.id_km_pengajuan', 'km_pengajuans.id')
                    ->where('km_transaksis.status', KmReadStatus::COMPLETED->value);
            }, 'completed_readers')
            ->selectSub(function ($subquery): void {
                $subquery->from('km_sukas')
                    ->selectRaw('COUNT(`km_sukas`.`id`)')
                    ->whereColumn('km_sukas.id_km_pengajuan', 'km_pengajuans.id');
            }, 'likes_count');

        if ($filters['category'] !== null) {
            $query->where('km_pengajuans.id_km_kategori', $filters['category']);
        }

        if ($filters['tag_ids'] !== []) {
            $query->whereHas('tags', fn ($relation) => $relation
                ->whereIn('km_tags.id', $filters['tag_ids']));
        }

        return $query
            ->orderByDesc('total_views')
            ->orderByDesc('completed_readers')
            ->orderByDesc('likes_count')
            ->orderBy('km_pengajuans.id');
    }

    private function authorize(User $actor): void
    {
        if (! $this->access->canAccessKnowledgeOversight($actor)) {
            throw new AuthorizationException('Anda tidak berhak melihat laporan materi populer.');
        }
    }
}
