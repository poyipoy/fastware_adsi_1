<?php

namespace App\Services\KnowledgeManagement;

use App\Models\KmPengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class KmRecommendationService
{
    public function __construct(
        private readonly KmAccessService $access,
    ) {
    }

    /** @return Collection<int, KmPengajuan> */
    public function forUser(User $user, int $limit = 6): Collection
    {
        $limit = max(1, min($limit, 12));
        $engagedIds = $this->recentEngagementIds($user, 10);
        $excluded = $this->excludedDocumentIds($user);
        $history = $engagedIds === []
            ? collect()
            : KmPengajuan::query()->with('tags:id,name')->whereKey($engagedIds)->get();
        $affinityCategoryIds = $history->pluck('id_km_kategori')->filter()->map('intval')->unique();
        $affinityTagIds = $history
            ->flatMap(fn (KmPengajuan $document) => $document->tags->pluck('id'))
            ->map('intval')
            ->unique();

        $query = KmPengajuan::query()
            ->with([
                'kmKategori:id,nama_kategori',
                'tags:id,name,slug',
                'user:id,name',
                'publishedVersion:id,km_pengajuan_id,processing_status,normalized_pdf_path,'
                    .'normalized_pdf_checksum_sha256,published_at',
            ])
            ->withCount([
                'kmSukas as recent_likes' => fn ($relation) => $relation
                    ->where('created_at', '>=', now()->subDays(30)),
                'insights as recent_insights' => fn ($relation) => $relation
                    ->where('created_at', '>=', now()->subDays(30)),
            ])
            ->withSum([
                'kmLihatBukus as recent_views' => fn ($relation) => $relation
                    ->where('updated_at', '>=', now()->subDays(30)),
            ], 'jumlah_lihat')
            ->whereNotIn('km_pengajuans.id', $excluded)
            ->where('id_user', '<>', $user->getKey());
        $this->access->applyPublishedVisibility($query, $user);

        $candidates = $query->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->filter(fn (KmPengajuan $document): bool => $document->isPreviewableFile())
            ->values();
        $scored = $candidates->map(function (KmPengajuan $document) use ($affinityCategoryIds, $affinityTagIds): KmPengajuan {
            $score = 0;
            if ($document->id_km_kategori !== null
                && $affinityCategoryIds->contains((int) $document->id_km_kategori)) {
                $score += 3;
            }
            $tagScore = $document->tags
                ->filter(fn ($tag): bool => $affinityTagIds->contains((int) $tag->getKey()))
                ->count();
            $score += min(3, $tagScore);
            $engagement = (int) $document->recent_likes
                + (int) $document->recent_insights
                + (int) ($document->recent_views ?? 0);
            $document->setAttribute('recommendation_score', $score);
            $document->setAttribute('recommendation_fallback_score', $score === 0 ? $engagement : 0);
            $document->setAttribute(
                'recommendation_reason',
                $score > 0 ? 'Sesuai riwayat belajar Anda' : 'Populer dan terbaru',
            );

            return $document;
        })->sort(function (KmPengajuan $left, KmPengajuan $right): int {
            return [
                -(int) $left->recommendation_score,
                -(int) $left->recommendation_fallback_score,
                -strtotime((string) ($left->publishedVersion?->published_at ?? $left->updated_at ?? $left->created_at)),
                -(int) $left->getKey(),
            ] <=> [
                -(int) $right->recommendation_score,
                -(int) $right->recommendation_fallback_score,
                -strtotime((string) ($right->publishedVersion?->published_at ?? $right->updated_at ?? $right->created_at)),
                -(int) $right->getKey(),
            ];
        })->take($limit)->values();

        return new Collection($scored->all());
    }

    /** @return list<int> */
    private function recentEngagementIds(User $user, int $limit): array
    {
        $activity = collect();
        $sources = [
            ['km_transaksis', 'id_km_pengajuan', 'COALESCE(last_progress_at, updated_at)', 'id_user'],
            ['km_bookmarks', 'km_pengajuan_id', 'updated_at', 'user_id'],
            ['km_sukas', 'id_km_pengajuan', 'updated_at', 'id_user'],
            ['km_insights', 'id_km_pengajuan', 'updated_at', 'id_user'],
        ];
        foreach ($sources as [$table, $documentColumn, $timestampColumn, $userColumn]) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }
            $rows = DB::table($table)
                ->where($userColumn, $user->getKey())
                ->whereNotNull($documentColumn)
                ->when($table === 'km_transaksis', static fn ($query) => $query
                    ->where(static fn ($progress) => $progress
                        ->where('progress_percent', '>=', 10)
                        ->orWhere('status', 3)))
                ->selectRaw("{$documentColumn} AS document_id, {$timestampColumn} AS engaged_at")
                ->orderByDesc('engaged_at')
                ->limit(25)
                ->get();
            $activity = $activity->concat($rows);
        }

        return $activity
            ->sortByDesc('engaged_at')
            ->unique('document_id')
            ->take($limit)
            ->pluck('document_id')
            ->map('intval')
            ->values()
            ->all();
    }

    /** @return list<int> */
    private function excludedDocumentIds(User $user): array
    {
        $ids = DB::table('km_pengajuans')->where('id_user', $user->getKey())->pluck('id');
        $ids = $ids->merge(DB::table('km_document_authors')->where('user_id', $user->getKey())->pluck('km_pengajuan_id'));
        $ids = $ids->merge(DB::table('km_transaksis')->where('id_user', $user->getKey())->pluck('id_km_pengajuan'));
        $ids = $ids->merge(DB::table('km_bookmarks')->where('user_id', $user->getKey())->pluck('km_pengajuan_id'));

        return $ids->filter()->map('intval')->unique()->values()->all();
    }
}
