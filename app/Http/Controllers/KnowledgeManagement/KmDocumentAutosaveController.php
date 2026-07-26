<?php

namespace App\Http\Controllers\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Exceptions\KnowledgeManagement\KmAutosaveConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeManagement\KmDocumentAutosaveRequest;
use App\Models\KmPengajuan;
use App\Services\KnowledgeManagement\KmDocumentAuthoringService;
use Illuminate\Http\JsonResponse;

class KmDocumentAutosaveController extends Controller
{
    public function __construct(
        private readonly KmDocumentAuthoringService $authoring,
    ) {
    }

    public function __invoke(
        KmDocumentAutosaveRequest $request,
        KmPengajuan $kmPengajuan,
    ): JsonResponse {
        abort_unless((int) $kmPengajuan->id_user === (int) $request->user()->getKey(), 403);

        if ($kmPengajuan->documentStatus() !== KmDocumentStatus::DRAFT) {
            return response()->json([
                'message' => 'Hanya dokumen berstatus Draf yang dapat di-autosave.',
            ], 422);
        }

        $this->authorize('autosave', $kmPengajuan);

        try {
            $result = $this->authoring->autosave(
                $kmPengajuan,
                $request->user(),
                $request->validated(),
            );
        } catch (KmAutosaveConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'draft_revision' => $exception->serverRevision,
                'autosaved_at' => $exception->serverAutosavedAt,
            ], 409);
        }

        return response()->json([
            'message' => 'Draft berhasil disimpan.',
            'draft_revision' => $result['draft_revision'],
            'autosaved_at' => $result['autosaved_at'],
        ]);
    }
}
