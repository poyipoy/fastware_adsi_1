<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Http\Requests\KnowledgeManagement\KmDashboardFilterRequest;
use App\Models\KmAssignmentUser;
use App\Models\KmPengajuan;
use App\Models\KmTransaksi;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class KmMandatoryMaterialService
{
    /** @var array<int, Collection<int, array<string, mixed>>> */
    private array $materialsByUser = [];

    public function __construct(
        private readonly KmAccessService $access,
    ) {
    }

    /**
     * @return array{
     *     mandatorySummary: array{active_count: int, overdue_count: int, completed_count: int},
     *     mandatoryMaterials: Collection<int, array<string, mixed>>
     * }
     */
    public function dashboardData(User $user, int $limit = 5): array
    {
        $materials = $this->materialsForUser($user);
        $active = $materials->whereNotIn('status', ['completed', 'exempted']);

        return [
            'mandatorySummary' => [
                'active_count' => $active->count(),
                'overdue_count' => $active->where('status', 'overdue')->count(),
                'completed_count' => $materials->where('status', 'completed')->count(),
            ],
            'mandatoryMaterials' => $active
                ->sortBy(static fn (array $material): array => [
                    $material['status'] === 'overdue' ? 0 : 1,
                    $material['due_at']?->getTimestamp() ?? PHP_INT_MAX,
                    $material['status'] === 'reading' ? 0 : 1,
                    $material['assignment_id'],
                ])
                ->take(max(1, $limit))
                ->values(),
        ];
    }

    /** @return list<int> */
    public function activeDocumentIds(User $user): array
    {
        return $this->materialsForUser($user)
            ->whereNotIn('status', ['completed', 'exempted'])
            ->pluck('document_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function paginateActive(
        KmDashboardFilterRequest $request,
        User $user,
    ): LengthAwarePaginator {
        $materials = $this->materialsForUser($user)
            ->whereNotIn('status', ['completed', 'exempted']);

        $query = Str::lower(trim((string) ($request->validated('q') ?? '')));
        if ($query !== '') {
            $materials = $materials->filter(static function (array $material) use ($query): bool {
                $haystack = Str::lower(implode(' ', [
                    $material['document_title'],
                    $material['synopsis'],
                    implode(' ', $material['tag_names']),
                ]));

                return Str::contains($haystack, $query);
            });
        }

        if ($request->validated('category') !== null) {
            $categoryId = (int) $request->validated('category');
            $materials = $materials->where('category_id', $categoryId);
        }

        $tagIds = array_map('intval', $request->validated('tag_ids') ?? []);
        if ($tagIds !== []) {
            $materials = $materials->filter(static fn (array $material): bool =>
                collect($material['tag_ids'])->intersect($tagIds)->isNotEmpty());
        }

        $readStatus = $request->validated('read_status');
        if ($readStatus === 'unread') {
            $materials = $materials->where('read_status', 'unread');
        } elseif ($readStatus === 'reading') {
            $materials = $materials->where('read_status', 'reading');
        } elseif ($readStatus === 'completed') {
            $materials = $materials->where('read_status', 'completed');
        }

        if ($request->validated('date_from') !== null) {
            $from = (string) $request->validated('date_from');
            $materials = $materials->filter(static fn (array $material): bool =>
                $material['published_at'] !== null
                    && $material['published_at']->toDateString() >= $from);
        }
        if ($request->validated('date_to') !== null) {
            $to = (string) $request->validated('date_to');
            $materials = $materials->filter(static fn (array $material): bool =>
                $material['published_at'] !== null
                    && $material['published_at']->toDateString() <= $to);
        }
        if ($request->validated('bookmarked') === true) {
            $materials = $materials->where('is_bookmarked', true);
        }

        $materials = match ($request->sortBy()) {
            'oldest' => $materials->sortBy(static fn (array $material): array => [
                $material['published_at']?->getTimestamp() ?? 0,
                $material['assignment_id'],
            ]),
            'title_asc' => $materials->sortBy(static fn (array $material): array => [
                Str::lower($material['document_title']),
                $material['assignment_id'],
            ]),
            'popular' => $materials->sortByDesc(static fn (array $material): array => [
                $material['total_views'],
                $material['assignment_id'],
            ]),
            'relevance' => $materials->sortByDesc(static function (array $material) use ($query): array {
                $title = Str::lower($material['document_title']);
                $synopsis = Str::lower($material['synopsis']);
                $tagNames = Str::lower(implode(' ', $material['tag_names']));

                return [
                    ($title === $query ? 8 : 0)
                        + (Str::contains($title, $query) ? 4 : 0)
                        + (Str::contains($tagNames, $query) ? 2 : 0)
                        + (Str::contains($synopsis, $query) ? 1 : 0),
                    $material['published_at']?->getTimestamp() ?? 0,
                    $material['assignment_id'],
                ];
            }),
            default => $materials->sortByDesc(static fn (array $material): array => [
                $material['published_at']?->getTimestamp() ?? 0,
                $material['assignment_id'],
            ]),
        };

        $page = max(1, (int) ($request->validated('page') ?? 1));
        $perPage = $request->perPage();

        return new LengthAwarePaginator(
            $materials->forPage($page, $perPage)->values(),
            $materials->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => 'page',
            ],
        );
    }

    /**
     * Attach the nearest active assignment to every catalog document without
     * widening the document query or exposing another user's assignment.
     *
     * @param  Collection<int, KmPengajuan>  $documents
     */
    public function annotateCatalog(Collection $documents, User $user): void
    {
        if ($documents->isEmpty()) {
            return;
        }

        $documentIds = $documents->modelKeys();
        $assignments = $this->materialsForUser($user)
            ->whereNotIn('status', ['completed', 'exempted'])
            ->whereIn('document_id', $documentIds)
            ->sortBy(static fn (array $material): array => [
                $material['status'] === 'overdue' ? 0 : 1,
                $material['due_at']?->getTimestamp() ?? PHP_INT_MAX,
                $material['assignment_id'],
            ])
            ->groupBy('document_id');

        $documents->each(function (KmPengajuan $document) use ($assignments): void {
            $document->setAttribute(
                'mandatory_assignment',
                $assignments->get($document->getKey())?->first(),
            );
        });
    }

    /**
     * @return array{available: bool, notice: string|null, material: array<string, mixed>|null}
     */
    public function deepLinkContext(User $user, int $assignmentId, ?int $documentId): array
    {
        $recipient = KmAssignmentUser::query()
            ->with('assignment.version')
            ->where('user_id', $user->getKey())
            ->where('assignment_id', $assignmentId)
            ->first();

        abort_if($recipient === null, 404);

        $assignedDocumentId = $recipient->assignment?->version?->km_pengajuan_id;
        if ($documentId !== null
            && ($assignedDocumentId === null || (int) $assignedDocumentId !== $documentId)) {
            abort(404);
        }

        $material = $this->materialsForUser($user)
            ->firstWhere('assignment_id', $assignmentId);

        if ($material === null) {
            return [
                'available' => false,
                'notice' => 'Materi wajib ini tidak lagi tersedia untuk akun Anda. Hubungi pengelola KM jika tugas masih diperlukan.',
                'material' => null,
            ];
        }

        return [
            'available' => true,
            'notice' => null,
            'material' => $material,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function materialsForUser(User $user): Collection
    {
        $userId = (int) $user->getKey();
        if (array_key_exists($userId, $this->materialsByUser)) {
            return $this->materialsByUser[$userId];
        }

        if (! Schema::hasTable('km_assignments')
            || ! Schema::hasTable('km_assignment_users')
            || ! Schema::hasTable('km_document_versions')) {
            return $this->materialsByUser[$userId] = collect();
        }

        $recipients = KmAssignmentUser::query()
            ->with([
                'assignment.version.category:id,nama_kategori',
                'assignment.version.tags:id,name,slug',
                'assignment.version.document.bookmarks' => static fn ($query) => $query
                    ->where('user_id', $userId),
                'assignment.version.document.kmLihatBukus',
                'assignment.version.document.publishedVersion',
            ])
            ->where('user_id', $userId)
            ->whereHas('assignment', static fn ($query) => $query->where('status', 'active'))
            ->orderBy('id')
            ->get()
            ->filter(function (KmAssignmentUser $recipient) use ($user): bool {
                $version = $recipient->assignment?->version;
                $document = $version?->document;

                return $version !== null
                    && $document !== null
                    && $this->access->isDocumentVersionEligible($user, $document, $version);
            })
            ->values();

        $transactions = KmTransaksi::query()
            ->where('id_user', $userId)
            ->whereIn('document_version_id', $recipients
                ->pluck('assignment.document_version_id')
                ->filter()
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->values())
            ->orderBy('id')
            ->get()
            ->keyBy('document_version_id');

        return $this->materialsByUser[$userId] = $recipients
            ->map(function (KmAssignmentUser $recipient) use ($transactions): array {
                $assignment = $recipient->assignment;
                $version = $assignment->version;
                $document = $version->document;
                $transaction = $transactions->get((int) $version->getKey());
                $status = $this->statusFor($recipient, $transaction);

                return [
                    'assignment_id' => (int) $assignment->getKey(),
                    'assignment_user_id' => (int) $recipient->getKey(),
                    'document_id' => (int) $document->getKey(),
                    'document_version_id' => (int) $version->getKey(),
                    'assignment_title' => (string) $assignment->title,
                    'document_title' => (string) ($version->title ?: $document->judul),
                    'synopsis' => (string) ($version->synopsis ?? ''),
                    'category_id' => $version->category_id === null ? null : (int) $version->category_id,
                    'category' => $version->category?->nama_kategori,
                    'tag_ids' => $version->tags->modelKeys(),
                    'tag_names' => $version->tags->pluck('name')->all(),
                    'version_number' => $version->number(),
                    'published_at' => $version->published_at ?? $version->created_at,
                    'due_at' => $recipient->due_at,
                    'status' => $status,
                    'read_status' => match ($transaction?->readStatus()) {
                        KmReadStatus::READING => 'reading',
                        KmReadStatus::COMPLETED => 'completed',
                        default => 'unread',
                    },
                    'progress_percent' => $status === 'completed'
                        ? 100
                        : max(0, min(100, (int) ($transaction?->progress_percent ?? 0))),
                    'completed_at' => $recipient->completed_at,
                    'is_bookmarked' => $document->bookmarks->isNotEmpty(),
                    'total_views' => (int) $document->kmLihatBukus->sum('jumlah_lihat'),
                    'thumbnail_url' => route('km.document-versions.thumbnail', [
                        'kmPengajuan' => $document,
                        'version' => $version,
                    ]),
                    'preview_url' => route('km.document-versions.preview', [
                        'kmPengajuan' => $document,
                        'version' => $version,
                    ]),
                    'progress_url' => route('km.reading.progress', $document),
                    'last_page' => max(1, (int) ($transaction?->last_page ?? 1)),
                    'pages_total' => (int) ($transaction?->pages_total ?? 0),
                    'active_seconds' => (int) ($transaction?->active_seconds ?? 0),
                    'unique_pages_count' => (int) ($transaction?->unique_pages_count ?? 0),
                    'completion_eligible' => $this->completionEligible($transaction),
                    'document' => $document,
                    'transaction' => $transaction,
                ];
            })
            ->values();
    }

    private function completionEligible(?KmTransaksi $transaction): bool
    {
        if ($transaction?->readStatus() === KmReadStatus::COMPLETED) {
            return true;
        }
        $pagesTotal = (int) ($transaction?->pages_total ?? 0);
        if ($pagesTotal <= 0) {
            return false;
        }
        $requiredPages = (int) ceil(
            min(1, max(0, (float) config('knowledge_management.reading.unique_page_ratio', 0.9)))
                * $pagesTotal,
        );
        $minimumSeconds = max(0, (int) config('knowledge_management.reading.minimum_active_seconds', 60));
        $perPage = max(0, (int) config('knowledge_management.reading.seconds_per_page', 20));
        $maximum = max($minimumSeconds, (int) config('knowledge_management.reading.maximum_required_seconds', 900));
        $requiredSeconds = max($minimumSeconds, min($perPage * $pagesTotal, $maximum));

        return (int) ($transaction?->unique_pages_count ?? 0) >= $requiredPages
            && (int) ($transaction?->active_seconds ?? 0) >= $requiredSeconds;
    }

    private function statusFor(KmAssignmentUser $recipient, ?KmTransaksi $transaction): string
    {
        if ($recipient->exempted_at !== null) {
            return 'exempted';
        }
        if ($recipient->completed_at !== null) {
            return 'completed';
        }
        if ($recipient->due_at !== null && $recipient->due_at->isPast()) {
            return 'overdue';
        }
        if ($transaction !== null
            && ((int) $transaction->status === KmReadStatus::READING->value
                || (int) $transaction->progress_percent > 0)) {
            return 'reading';
        }

        return 'not_started';
    }
}
