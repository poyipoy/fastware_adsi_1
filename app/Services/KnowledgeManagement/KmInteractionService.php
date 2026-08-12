<?php

namespace App\Services\KnowledgeManagement;

use App\Models\Insight;
use App\Models\KmInsightReaction;
use App\Models\KmPengajuan;
use App\Models\KmSuka;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class KmInteractionService
{
    public function __construct(
        private readonly KmAccessService $access,
        private readonly KmNotificationService $notifications,
        private readonly KmPointLedgerService $ledger,
        private readonly KmVersioningService $versions,
        private readonly KmGamificationService $gamification,
    ) {
    }

    /**
     * @return array{created: bool, like_count: int}
     */
    public function like(User $user, KmPengajuan $document): array
    {
        $this->assertCanView($user, $document);
        $like = KmSuka::query()->firstOrCreate([
            'id_user' => $user->getKey(),
            'id_km_pengajuan' => $document->getKey(),
        ]);

        return [
            'created' => $like->wasRecentlyCreated,
            'like_count' => $this->likeCount($document),
        ];
    }

    /**
     * @return array{deleted: bool, like_count: int}
     */
    public function unlike(User $user, KmPengajuan $document): array
    {
        $this->assertCanView($user, $document);
        $deleted = KmSuka::query()
            ->where('id_user', $user->getKey())
            ->where('id_km_pengajuan', $document->getKey())
            ->delete() > 0;

        return [
            'deleted' => $deleted,
            'like_count' => $this->likeCount($document),
        ];
    }

    /**
     * @param  list<int>  $mentionIds
     */
    public function addInsight(
        User $user,
        KmPengajuan $document,
        string $content,
        ?int $parentId = null,
        array $mentionIds = [],
    ): Insight {
        return DB::transaction(function () use (
            $user,
            $document,
            $content,
            $parentId,
            $mentionIds,
        ): Insight {
            $lockedDocument = KmPengajuan::query()
                ->whereKey($document->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            Gate::forUser($user)->authorize('comment', $lockedDocument);

            $rootParentId = null;
            $replyRecipientId = null;
            if ($parentId !== null) {
                $parent = Insight::query()
                    ->whereKey($parentId)
                    ->where('id_km_pengajuan', $lockedDocument->getKey())
                    ->lockForUpdate()
                    ->first();
                if ($parent === null) {
                    throw new DomainException('Insight induk tidak ditemukan pada dokumen ini.');
                }
                $rootParentId = $parent->parent_id ?: (int) $parent->getKey();
                $replyRecipientId = (int) $parent->id_user;
            }

            $mentionedUsers = $this->validateMentionUsers($mentionIds, $lockedDocument);
            $insightAttributes = [
                'id_user' => $user->getKey(),
                'id_km_pengajuan' => $lockedDocument->getKey(),
                'parent_id' => $rootParentId,
                'content' => trim($content),
            ];
            if (Schema::hasColumn('km_insights', 'document_version_id')) {
                $insightAttributes['document_version_id'] = $this->versions->versionIdForReading($lockedDocument);
            }
            $insight = Insight::query()->create($insightAttributes);
            $excludedMentionRecipients = [];
            if ($replyRecipientId !== null && $replyRecipientId !== (int) $user->getKey()) {
                $this->notifications->record(
                    $replyRecipientId,
                    'insight_reply',
                    'reply:'.$insight->getKey().':u'.$replyRecipientId,
                    [
                        'document_id' => $lockedDocument->getKey(),
                        'document_version_id' => $this->versions->versionIdForReading($lockedDocument),
                        'insight_id' => $insight->getKey(),
                        'title' => $lockedDocument->judul,
                    ],
                );
                $excludedMentionRecipients[] = $replyRecipientId;
            }
            $this->attachMentions(
                $insight,
                $mentionedUsers,
                $user,
                $lockedDocument,
                $excludedMentionRecipients,
            );

            return $insight->load(['user', 'mentionedUsers']);
        }, 3);
    }

    /** @param  list<int>|null  $mentionIds */
    public function editInsight(
        User $user,
        Insight $insight,
        string $content,
        ?array $mentionIds = null,
    ): Insight {
        return DB::transaction(function () use ($user, $insight, $content, $mentionIds): Insight {
            $locked = Insight::withTrashed()->whereKey($insight->getKey())->lockForUpdate()->firstOrFail();
            if (! $locked->isEditableBy($user)) {
                throw new AuthorizationException('Insight hanya dapat diedit oleh author selama 30 menit.');
            }
            $document = KmPengajuan::query()->whereKey($locked->id_km_pengajuan)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('comment', $document);

            $locked->forceFill([
                'content' => trim($content),
                'edited_at' => now(),
            ])->save();
            if ($mentionIds !== null) {
                $existingIds = DB::table('km_insight_mentions')
                    ->where('insight_id', $locked->getKey())
                    ->pluck('mentioned_user_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();
                $requestedIds = array_values(array_unique(array_map('intval', $mentionIds)));
                $combinedIds = array_values(array_unique([...$existingIds, ...$requestedIds]));
                $mentionedUsers = $this->validateMentionUsers($combinedIds, $document);
                $newIds = array_values(array_diff($combinedIds, $existingIds));

                $this->attachMentions(
                    $locked,
                    $mentionedUsers->filter(
                        static fn (User $mentioned): bool => in_array(
                            (int) $mentioned->getKey(),
                            $newIds,
                            true,
                        ),
                    ),
                    $user,
                    $document,
                );
            }

            return $locked->load(['user', 'mentionedUsers']);
        }, 3);
    }

    public function deleteInsight(User $user, Insight $insight, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($user, $insight, $reason): bool {
            $locked = Insight::withTrashed()->whereKey($insight->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->trashed()) {
                return false;
            }
            $document = KmPengajuan::query()->whereKey($locked->id_km_pengajuan)->lockForUpdate()->firstOrFail();
            $moderator = Gate::forUser($user)->allows('moderateInsights', $document);
            $authorWindow = $locked->isEditableBy($user);
            if (! $moderator && ! $authorWindow) {
                throw new AuthorizationException('Insight tidak dapat dihapus oleh pengguna ini.');
            }
            $trimmedReason = trim((string) $reason);
            if ($moderator && ! $authorWindow && $trimmedReason === '') {
                throw new DomainException('Moderator wajib mengisi alasan penghapusan insight.');
            }

            $locked->forceFill([
                'deleted_by' => $user->getKey(),
                'delete_reason' => $trimmedReason !== '' ? $trimmedReason : null,
                'featured_at' => null,
                'featured_by' => null,
            ])->save();
            $locked->delete();

            return true;
        }, 3);
    }

    /**
     * @return array{reaction: string, counts: array<string, int>}
     */
    public function react(User $user, Insight $insight, string $reaction): array
    {
        if (! in_array($reaction, config('knowledge_management.insights.reactions', []), true)) {
            throw new DomainException('Reaction insight tidak valid.');
        }

        return DB::transaction(function () use ($user, $insight, $reaction): array {
            $locked = Insight::query()->whereKey($insight->getKey())->lockForUpdate()->firstOrFail();
            $document = KmPengajuan::query()->findOrFail($locked->id_km_pengajuan);
            Gate::forUser($user)->authorize('comment', $document);

            $model = KmInsightReaction::query()->updateOrCreate(
                ['insight_id' => $locked->getKey(), 'user_id' => $user->getKey()],
                ['reaction' => $reaction],
            );
            if ($model->wasRecentlyCreated && (int) $locked->id_user !== (int) $user->getKey()) {
                $this->notifications->record(
                    (int) $locked->id_user,
                    'insight_reaction',
                    'reaction:'.$model->getKey().':u'.(int) $locked->id_user,
                    [
                        'document_id' => $document->getKey(),
                        'document_version_id' => $this->versions->versionIdForReading($document),
                        'insight_id' => $locked->getKey(),
                        'reaction' => $reaction,
                        'actor_name' => $user->name,
                        'title' => $document->judul,
                    ],
                );
            }

            return ['reaction' => $reaction, 'counts' => $this->reactionCounts($locked)];
        }, 3);
    }

    /**
     * @return array{reaction: null, counts: array<string, int>}
     */
    public function unreact(User $user, Insight $insight): array
    {
        return DB::transaction(function () use ($user, $insight): array {
            $locked = Insight::query()->whereKey($insight->getKey())->lockForUpdate()->firstOrFail();
            $document = KmPengajuan::query()->findOrFail($locked->id_km_pengajuan);
            Gate::forUser($user)->authorize('comment', $document);

            KmInsightReaction::query()
                ->where('insight_id', $locked->getKey())
                ->where('user_id', $user->getKey())
                ->delete();

            return ['reaction' => null, 'counts' => $this->reactionCounts($locked)];
        }, 3);
    }

    public function feature(User $user, Insight $insight): Insight
    {
        return DB::transaction(function () use ($user, $insight): Insight {
            $locked = Insight::query()->whereKey($insight->getKey())->lockForUpdate()->firstOrFail();
            $document = KmPengajuan::query()
                ->whereKey($locked->id_km_pengajuan)
                ->lockForUpdate()
                ->firstOrFail();
            Gate::forUser($user)->authorize('featureInsight', $document);
            if ($locked->featured_at !== null) {
                return $locked;
            }

            $maximum = max(1, (int) config('knowledge_management.insights.maximum_featured', 3));
            $featuredCount = Insight::query()
                ->where('id_km_pengajuan', $document->getKey())
                ->whereNotNull('featured_at')
                ->lockForUpdate()
                ->count();
            if ($featuredCount >= $maximum) {
                throw new DomainException("Maksimal {$maximum} Insight Pilihan per dokumen.");
            }

            $author = User::query()->whereKey($locked->id_user)->lockForUpdate()->first();
            if ($author === null) {
                throw new DomainException('Author insight tidak tersedia untuk menerima poin.');
            }

            $locked->forceFill([
                'featured_at' => now(),
                'featured_by' => $user->getKey(),
            ])->save();
            $this->ledger->award(
                $author,
                'selected_insight',
                'selected_insight:'.$locked->getKey().':'.$author->getKey(),
                max(0, (int) config('knowledge_management.points.featured_insight', 10)),
                (int) $document->getKey(),
                (int) $locked->getKey(),
                null,
                $user,
                $this->versions->versionIdForReading($document),
            );
            $this->notifications->record(
                $author,
                'insight_featured',
                'featured:'.$locked->getKey().':u'.$author->getKey(),
                [
                    'document_id' => $document->getKey(),
                    'document_version_id' => $this->versions->versionIdForReading($document),
                    'insight_id' => $locked->getKey(),
                    'title' => $document->judul,
                ],
            );
            $this->gamification->awardEligible($author);

            return $locked->load('featuredBy');
        }, 3);
    }

    public function unfeature(User $user, Insight $insight): bool
    {
        return DB::transaction(function () use ($user, $insight): bool {
            $locked = Insight::query()->whereKey($insight->getKey())->lockForUpdate()->firstOrFail();
            $document = KmPengajuan::query()
                ->whereKey($locked->id_km_pengajuan)
                ->lockForUpdate()
                ->firstOrFail();
            Gate::forUser($user)->authorize('featureInsight', $document);
            if ($locked->featured_at === null) {
                return false;
            }

            $locked->forceFill(['featured_at' => null, 'featured_by' => null])->save();

            return true;
        }, 3);
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function listInsights(
        User $user,
        KmPengajuan $document,
        int $perPage = 10,
        ?int $focusInsightId = null,
    ): array {
        $this->assertCanView($user, $document);
        $moderator = Gate::forUser($user)->allows('moderateInsights', $document);
        $focusRootId = null;
        if ($focusInsightId !== null && $focusInsightId > 0) {
            $focus = Insight::withTrashed()
                ->where('id_km_pengajuan', $document->getKey())
                ->whereKey($focusInsightId)
                ->first();
            $focusRootId = $focus === null
                ? null
                : (int) ($focus->parent_id ?: $focus->getKey());
        }
        $with = [
            'user:id,name',
            'featuredBy:id,name',
            'mentionedUsers:id,name',
            'reactions',
            'replies' => fn ($query) => $query
                ->withTrashed()
                ->with(['user:id,name', 'featuredBy:id,name', 'mentionedUsers:id,name', 'reactions'])
                ->orderBy('id'),
        ];
        $paginator = Insight::withTrashed()
            ->where('id_km_pengajuan', $document->getKey())
            ->whereNull('parent_id')
            ->with($with)
            ->when($focusRootId !== null, static fn ($query) => $query
                ->orderByRaw('km_insights.id = ? DESC', [$focusRootId]))
            ->orderByRaw('featured_at IS NULL')
            ->orderByDesc('featured_at')
            ->orderBy('id')
            ->paginate(max(1, min($perPage, 25)));

        return [
            'data' => collect($paginator->items())
                ->map(fn (Insight $root): array => [
                    ...$this->serializeInsight($root, $user, $document, $moderator),
                    'replies' => $root->replies
                        ->map(fn (Insight $reply): array => $this->serializeInsight(
                            $reply,
                            $user,
                            $document,
                            $moderator,
                        ))
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * @return EloquentCollection<int, User>
     */
    public function mentionOptions(User $user, KmPengajuan $document, ?string $query): EloquentCollection
    {
        Gate::forUser($user)->authorize('comment', $document);
        $pattern = $query === null
            ? null
            : '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($query)).'%';

        return User::query()
            ->select(['id', 'name', 'email', 'role_id'])
            ->where('is_active', false)
            ->when($pattern, static function ($builder, string $value): void {
                $builder->where(static function ($search) use ($value): void {
                    $search->whereRaw("name LIKE ? ESCAPE '!'", [$value])
                        ->orWhereRaw("email LIKE ? ESCAPE '!'", [$value])
                        ->orWhereRaw("npk LIKE ? ESCAPE '!'", [$value]);
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->filter(fn (User $candidate): bool => $this->access->canView($candidate, $document))
            ->values();
    }

    /**
     * @param  list<int>  $mentionIds
     * @return EloquentCollection<int, User>
     */
    private function validateMentionUsers(array $mentionIds, KmPengajuan $document): EloquentCollection
    {
        $ids = collect($mentionIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
        $maximum = max(0, (int) config('knowledge_management.insights.maximum_mentions', 10));
        if ($ids->count() > $maximum) {
            throw new DomainException("Maksimal {$maximum} mention per insight.");
        }
        if ($ids->isEmpty()) {
            return new EloquentCollection();
        }

        $users = User::query()
            ->whereKey($ids)
            ->where('is_active', false)
            ->get();
        if ($users->count() !== $ids->count()
            || $users->contains(fn (User $candidate): bool => ! $this->access->canView($candidate, $document))) {
            throw new DomainException('Satu atau lebih pengguna mention tidak memiliki akses ke dokumen.');
        }

        return $users;
    }

    private function attachMentions(
        Insight $insight,
        EloquentCollection $users,
        User $actor,
        KmPengajuan $document,
        array $excludedRecipientIds = [],
    ): void {
        $now = now();
        $pivot = $users->mapWithKeys(static fn (User $user): array => [
            $user->getKey() => ['created_at' => $now],
        ])->all();
        if ($pivot !== []) {
            $insight->mentionedUsers()->syncWithoutDetaching($pivot);
        }

        foreach ($users as $mentioned) {
            if ((int) $mentioned->getKey() === (int) $actor->getKey()) {
                continue;
            }
            if (in_array((int) $mentioned->getKey(), $excludedRecipientIds, true)) {
                continue;
            }
            $this->notifications->record(
                $mentioned,
                'insight_mention',
                'mention:'.$insight->getKey().':u'.$mentioned->getKey(),
                [
                    'document_id' => $document->getKey(),
                    'document_version_id' => $this->versions->versionIdForReading($document),
                    'insight_id' => $insight->getKey(),
                    'title' => $document->judul,
                ],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeInsight(
        Insight $insight,
        User $viewer,
        KmPengajuan $document,
        bool $moderator,
    ): array {
        $deleted = $insight->trashed();
        $content = $deleted && ! $moderator ? 'Insight telah dihapus.' : $insight->content;

        return [
            'id' => (int) $insight->getKey(),
            'parent_id' => $insight->parent_id === null ? null : (int) $insight->parent_id,
            'content' => $content,
            'deleted' => $deleted,
            'delete_reason' => $moderator ? $insight->delete_reason : null,
            'author' => [
                'id' => $insight->user?->getKey(),
                'name' => $insight->user?->name ?? 'Pengguna tidak tersedia',
            ],
            'mentions' => $insight->mentionedUsers->map(static fn (User $user): array => [
                'id' => (int) $user->getKey(),
                'name' => $user->name,
            ])->values()->all(),
            'reactions' => $this->reactionCountsFromCollection($insight->reactions),
            'viewer_reaction' => $insight->reactions
                ->firstWhere('user_id', $viewer->getKey())?->reaction,
            'featured' => $insight->featured_at !== null,
            'featured_by' => $insight->featuredBy?->name,
            'featured_at' => $insight->featured_at?->toIso8601String(),
            'created_at' => $insight->created_at?->toIso8601String(),
            'edited_at' => $insight->edited_at?->toIso8601String(),
            'permissions' => [
                'reply' => ! $deleted && Gate::forUser($viewer)->allows('comment', $document),
                'edit' => $insight->isEditableBy($viewer),
                'delete' => ! $deleted && ($insight->isEditableBy($viewer) || $moderator),
                'feature' => ! $deleted && Gate::forUser($viewer)->allows('featureInsight', $document),
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function reactionCounts(Insight $insight): array
    {
        return $this->reactionCountsFromCollection(
            KmInsightReaction::query()->where('insight_id', $insight->getKey())->get(),
        );
    }

    /**
     * @return array<string, int>
     */
    private function reactionCountsFromCollection(iterable $reactions): array
    {
        $counts = collect(config('knowledge_management.insights.reactions', []))
            ->mapWithKeys(static fn (string $reaction): array => [$reaction => 0])
            ->all();
        foreach ($reactions as $reaction) {
            if (array_key_exists($reaction->reaction, $counts)) {
                $counts[$reaction->reaction]++;
            }
        }

        return $counts;
    }

    private function assertCanView(User $user, KmPengajuan $document): void
    {
        $fresh = KmPengajuan::query()->findOrFail($document->getKey());
        if (! $this->access->canView($user, $fresh)) {
            throw new AuthorizationException('Dokumen Knowledge Management tidak dapat diakses.');
        }
    }

    private function likeCount(KmPengajuan $document): int
    {
        return KmSuka::query()
            ->where('id_km_pengajuan', $document->getKey())
            ->count();
    }
}
