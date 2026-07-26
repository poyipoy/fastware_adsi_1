<?php

namespace App\Http\Controllers;

use App\Enums\KnowledgeManagement\KmApprovalAction;
use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Exceptions\KnowledgeManagement\InvalidKmTransitionException;
use App\Exceptions\KnowledgeManagement\KmBulkApprovalConflictException;
use App\Http\Requests\KnowledgeManagement\AddKmInsightRequest;
use App\Http\Requests\KnowledgeManagement\ApproveKmDocumentRequest;
use App\Http\Requests\KnowledgeManagement\BulkKmApprovalRequest;
use App\Http\Requests\KnowledgeManagement\CompleteKmReadingRequest;
use App\Http\Requests\KnowledgeManagement\KmDashboardFilterRequest;
use App\Http\Requests\KnowledgeManagement\KmDocumentInteractionRequest;
use App\Http\Requests\KnowledgeManagement\MarkKmReadingRequest;
use App\Http\Requests\KnowledgeManagement\StoreKmDocumentRequest;
use App\Http\Requests\KnowledgeManagement\UpdateKmDocumentRequest;
use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\KnowledgeManagement\KmAccessService;
use App\Services\KnowledgeManagement\KmApprovalService;
use App\Services\KnowledgeManagement\KmDashboardQueryService;
use App\Services\KnowledgeManagement\KmDocumentAuthoringService;
use App\Services\KnowledgeManagement\KmDocumentQueryService;
use App\Services\KnowledgeManagement\KmFileService;
use App\Services\KnowledgeManagement\KmInteractionService;
use App\Services\KnowledgeManagement\KmReadingService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KmPengajuanController extends Controller
{
    public function __construct(
        private readonly KmAccessService $access,
        private readonly KmApprovalService $approval,
        private readonly KmReadingService $reading,
        private readonly KmFileService $files,
        private readonly KmDashboardQueryService $dashboardQuery,
        private readonly KmDocumentAuthoringService $authoring,
        private readonly KmDocumentQueryService $documents,
        private readonly KmInteractionService $interactions,
    ) {
    }

    public function pengajuanKM(Request $request): View
    {
        $this->authorize('viewAny', KmPengajuan::class);
        abort_unless($this->access->canCreate($request->user()), 403);

        return view('knowlege_management.pengajuanKM', [
            'km' => $this->documents->paginateAuthoring($request->user()),
            'documentStatuses' => KmDocumentStatus::class,
        ]);
    }

    public function persetujuanKM(Request $request): View
    {
        $this->authorize('viewAny', KmPengajuan::class);
        abort_unless($this->access->canApprove($request->user()), 403);

        return view('knowlege_management.persetujuanKM', [
            'km' => $this->documents->paginateApprovals(),
            'documentStatuses' => KmDocumentStatus::class,
            'kategoris' => $this->documents->categories(),
        ]);
    }

    public function storeKM(StoreKmDocumentRequest $request): RedirectResponse
    {
        $this->authoring->createDraft(
            $request->user(),
            $request->validated(),
            $request->file('file'),
        );

        return redirect()
            ->route('pengajuanKM')
            ->with('success', 'Knowledge Management berhasil dibuat.');
    }

    public function edit(int $id): JsonResponse
    {
        $document = $this->documents->findForPayload($id);
        $this->authorize('update', $document);

        return response()->json($this->documentPayload($document));
    }

    public function update(UpdateKmDocumentRequest $request): RedirectResponse
    {
        $this->authoring->updateDraft(
            $request->user(),
            $request->integer('id'),
            $request->validated(),
            $request->hasFile('file') ? $request->file('file') : null,
        );

        return redirect()->back()->with('success', 'Data KM berhasil diperbarui.');
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $document = $this->documents->find($id);
        $this->authorize('deactivate', $document);

        try {
            $this->approval->deactivate($document, $request->user(), $this->requestMetadata($request));
        } catch (InvalidKmTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Status data berhasil diperbarui']);
    }

    public function kirimKM(Request $request, int $id): JsonResponse
    {
        $document = $this->documents->find($id);
        $this->authorize('submit', $document);

        try {
            $this->approval->submit($document, $request->user(), $this->requestMetadata($request));
        } catch (InvalidKmTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function showPersetujuan(Request $request, int $id): JsonResponse
    {
        $document = $this->documents->findForApprovalPayload($id);
        $this->authorize('view', $document);

        $payload = $this->documentPayload($document);
        $payload['id_km_kategori'] = $document->id_km_kategori;
        $payload['posisi'] = $document->posisi;
        $payload['can_approve'] = $request->user()->can('approve', $document);
        $payload['can_reject'] = $request->user()->can('reject', $document);
        $payload['approval_events'] = $document->approvalEvents->map(static fn ($event): array => [
            'action' => $event->action->value,
            'from_status' => $event->from_status,
            'to_status' => $event->to_status,
            'reason' => $event->reason,
            'actor_name' => $event->actor_name,
            'acted_at' => $event->acted_at?->toIso8601String(),
        ])->values();

        return response()->json([
            'km' => $payload,
            'kategoris' => $this->documents->categories(),
        ]);
    }

    public function approveKM(ApproveKmDocumentRequest $request): RedirectResponse
    {
        $document = $this->documents->find($request->integer('id'));
        $attributes = $request->safe()->only([
            'posisi',
            'id_km_kategori',
            'judul',
            'keterangan',
        ]);

        try {
            if ($request->string('action')->toString() === KmApprovalAction::APPROVED->value) {
                $this->authorize('approve', $document);
                $this->approval->approve(
                    $document,
                    $request->user(),
                    $attributes,
                    $this->requestMetadata($request),
                );
            } else {
                $this->authorize('reject', $document);
                $this->approval->reject(
                    $document,
                    $request->user(),
                    $request->string('reason')->toString(),
                    $attributes,
                    $this->requestMetadata($request),
                );
            }
        } catch (InvalidKmTransitionException $exception) {
            abort(422, $exception->getMessage());
        }

        return redirect()->back()->with('success', 'Data KM berhasil diperbarui.');
    }

    public function bulkApprove(BulkKmApprovalRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('bulkApprove', KmPengajuan::class);

        try {
            $documents = $this->approval->bulkAct(
                $request->user(),
                $request->validated('items'),
                $request->action(),
                $request->validated('reason'),
                $this->requestMetadata($request),
            );
        } catch (InvalidKmTransitionException|KmBulkApprovalConflictException $exception) {
            throw ValidationException::withMessages([
                'items' => $exception->getMessage(),
            ]);
        }

        $message = sprintf('%d dokumen berhasil diproses secara atomik.', $documents->count());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'processed_count' => $documents->count(),
                'action' => $request->action()->value,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function dsKnowlege(KmDashboardFilterRequest $request): View
    {
        $this->authorize('viewAny', KmPengajuan::class);

        $pengajuans = $this->dashboardQuery->paginate($request, $request->user());
        $references = $this->documents->dashboardReferences();

        return view('dashboard.dsKnowlege', [
            'pengajuans' => $pengajuans,
            ...$references,
            'filters' => $request->safe()->except('page'),
        ]);
    }

    public function preview(KmPengajuan $kmPengajuan): BinaryFileResponse
    {
        $this->authorize('view', $kmPengajuan);

        return $this->files->streamPreview($kmPengajuan);
    }

    public function download(KmPengajuan $kmPengajuan): BinaryFileResponse
    {
        $this->authorize('view', $kmPengajuan);

        return $this->files->streamDownload($kmPengajuan);
    }

    public function markAsRead(MarkKmReadingRequest $request): JsonResponse
    {
        $document = $this->documents->find($request->integer('id_km_pengajuan'));
        $this->authorize('view', $document);
        $result = $this->reading->markStarted($request->user(), $document);

        return response()->json(['success' => true, ...$result]);
    }

    public function saveTransaction(CompleteKmReadingRequest $request): JsonResponse
    {
        $document = $this->documents->find($request->integer('id_km_pengajuan'));
        $this->authorize('completeReading', $document);

        try {
            $result = $this->reading->complete($request->user(), $document);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'already_completed' => false,
                'points_awarded' => 0,
            ], 422);
        }

        return response()->json(['success' => true, ...$result]);
    }

    public function like(KmDocumentInteractionRequest $request): JsonResponse
    {
        $result = $this->interactions->like($request->user(), $request->document());

        return response()->json([
            'message' => $result['created'] ? 'Liked successfully' : 'Already liked',
            'like_count' => $result['like_count'],
        ], $result['created'] ? 201 : 200);
    }

    public function unlike(KmDocumentInteractionRequest $request): JsonResponse
    {
        $result = $this->interactions->unlike($request->user(), $request->document());

        return response()->json([
            'message' => $result['deleted'] ? 'Unliked successfully' : 'Not liked yet',
            'like_count' => $result['like_count'],
        ], $result['deleted'] ? 200 : 400);
    }

    public function addInsight(AddKmInsightRequest $request): RedirectResponse
    {
        $this->interactions->addInsight(
            $request->user(),
            $request->document(),
            $request->string('content')->toString(),
        );

        return back()->with('success', 'Insight berhasil ditambahkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function documentPayload(KmPengajuan $document): array
    {
        $hasFile = $document->hasCompletePrivateFileMetadata();

        return [
            'id' => $document->getKey(),
            'judul' => $document->judul,
            'keterangan' => $document->keterangan,
            'reading_minutes' => $document->reading_minutes,
            'tags_csv' => $document->tags->pluck('name')->join(','),
            'co_authors' => $document->coAuthors
                ->map(fn (User $user): array => [
                    'id' => (int) $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                ])
                ->values(),
            'draft_revision' => (int) $document->draft_revision,
            'file' => $hasFile ? basename((string) $document->file) : null,
            'file_name' => $document->file_original_name ?: $document->file_name,
            'status' => $document->status,
            'has_file' => $hasFile,
            'previewable' => $hasFile && $document->isPreviewableFile(),
            'preview_url' => $hasFile ? route('km.documents.preview', $document) : null,
            'download_url' => $hasFile ? route('km.documents.download', $document) : null,
        ];
    }

    /**
     * @return array{request_id: string}
     */
    private function requestMetadata(Request $request): array
    {
        $requestId = trim((string) $request->header('X-Request-ID'));
        if ($requestId === '' || strlen($requestId) > 128) {
            $requestId = Str::uuid()->toString();
        }

        return ['request_id' => $requestId];
    }
}
