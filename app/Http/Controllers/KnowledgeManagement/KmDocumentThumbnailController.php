<?php

namespace App\Http\Controllers\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmThumbnailStatus;
use App\Http\Controllers\Controller;
use App\Models\KmPengajuan;
use App\Services\KnowledgeManagement\KmPdfThumbnailService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KmDocumentThumbnailController extends Controller
{
    public function __invoke(
        KmPengajuan $kmPengajuan,
        KmPdfThumbnailService $thumbnails,
    ): Response|StreamedResponse {
        $this->authorize('view', $kmPengajuan);

        $disk = (string) config('knowledge_management.thumbnail.disk', 'km_private');
        $previewVersion = $kmPengajuan->resolvedPreviewVersion();
        $hasVersionPointer = $kmPengajuan->published_version_id !== null
            || $kmPengajuan->current_version_id !== null;
        $isPreviewSourceReady = $hasVersionPointer
            ? ($previewVersion?->isReady() ?? false)
            : in_array($kmPengajuan->file_mime_type, ['application/pdf', 'application/x-pdf'], true);
        $previewChecksum = $previewVersion?->normalized_pdf_checksum_sha256
            ?? $kmPengajuan->file_checksum_sha256;
        $path = (string) $kmPengajuan->thumbnail_path;
        $checksumMatches = is_string($kmPengajuan->thumbnail_source_checksum)
            && is_string($previewChecksum)
            && hash_equals($previewChecksum, $kmPengajuan->thumbnail_source_checksum);

        if ($isPreviewSourceReady
            && $kmPengajuan->thumbnail_status === KmThumbnailStatus::READY
            && $thumbnails->isSafeThumbnailPath($path, (int) $kmPengajuan->getKey())
            && $checksumMatches
            && Storage::disk($disk)->exists($path)
            && $this->isPng(Storage::disk($disk)->path($path))) {
            return response()->stream(function () use ($disk, $path): void {
                $stream = Storage::disk($disk)->readStream($path);
                if (is_resource($stream)) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return $this->defaultThumbnailResponse();
    }

    private function isPng(string $path): bool
    {
        $imageInfo = @getimagesize($path);

        return $imageInfo !== false && ($imageInfo[2] ?? null) === IMAGETYPE_PNG;
    }

    private function defaultThumbnailResponse(): Response
    {
        $path = public_path('assets/img/km/default-thumbnail.svg');
        $svg = is_file($path)
            ? file_get_contents($path)
            : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 150"><rect width="200" height="150" fill="#e9ecef"/><text x="100" y="80" text-anchor="middle" fill="#6c757d" font-size="14">Dokumen</text></svg>';

        return response((string) $svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
