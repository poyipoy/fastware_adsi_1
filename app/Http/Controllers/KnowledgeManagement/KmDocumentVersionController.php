<?php

namespace App\Http\Controllers\KnowledgeManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeManagement\RecoverKmDocumentRequest;
use App\Http\Requests\KnowledgeManagement\StoreKmMajorRevisionRequest;
use App\Http\Requests\KnowledgeManagement\StoreKmMinorRevisionRequest;
use App\Models\KmDocumentVersion;
use App\Models\KmPengajuan;
use App\Services\KnowledgeManagement\KmFileService;
use App\Services\KnowledgeManagement\KmVersioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KmDocumentVersionController extends Controller
{
    public function __construct(
        private readonly KmVersioningService $versions,
        private readonly KmFileService $files,
    ) {
    }

    public function storeMajor(
        StoreKmMajorRevisionRequest $request,
        KmPengajuan $kmPengajuan,
    ): RedirectResponse {
        $version = $this->versions->createMajorRevision(
            $kmPengajuan,
            $request->user(),
            $request->string('change_note')->toString(),
        );

        return back()->with('success', 'Revisi major '.$version->number().' berhasil dibuat sebagai draf.');
    }

    public function storeMinor(
        StoreKmMinorRevisionRequest $request,
        KmPengajuan $kmPengajuan,
    ): RedirectResponse {
        $version = $this->versions->createMinorRevision(
            $kmPengajuan,
            $request->user(),
            $request->string('change_note')->toString(),
            $request->safe()->only('tag_ids'),
        );

        return back()->with('success', 'Perubahan administratif versi '.$version->number().' dipublikasikan.');
    }

    public function index(Request $request, KmPengajuan $kmPengajuan): JsonResponse
    {
        $this->authorize('view', $kmPengajuan);

        return response()->json([
            'data' => $kmPengajuan->versions()->get()->map(static fn (KmDocumentVersion $version): array => [
                'id' => (int) $version->getKey(),
                'number' => $version->number(),
                'change_type' => $version->change_type->value,
                'change_note' => $version->change_note,
                'status' => $version->version_status->value,
                'processing_status' => $version->processing_status->value,
                'created_at' => $version->created_at?->toIso8601String(),
                'published_at' => $version->published_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function preview(
        Request $request,
        KmPengajuan $kmPengajuan,
        KmDocumentVersion $version,
    ): BinaryFileResponse {
        abort_unless((int) $version->km_pengajuan_id === (int) $kmPengajuan->getKey(), 404);
        $this->authorize('viewVersion', [$kmPengajuan, $version]);

        return $this->files->streamVersionPreview($version);
    }

    public function recover(
        RecoverKmDocumentRequest $request,
        KmDocumentVersion $version,
    ): BinaryFileResponse {
        return $this->files->streamAdminRecovery(
            $version,
            $request->user(),
            $request->string('reason')->toString(),
            $this->requestId($request),
        );
    }

    private function requestId(Request $request): string
    {
        $requestId = trim((string) $request->header('X-Request-ID'));

        return $requestId !== '' && strlen($requestId) <= 128
            ? $requestId
            : Str::uuid()->toString();
    }
}
