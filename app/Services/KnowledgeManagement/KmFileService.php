<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmThumbnailStatus;
use App\Models\KmPengajuan;
use App\Models\KmDocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class KmFileService
{
    public const DISK = 'km_private';

    /**
     * @var array<string, list<string>>
     */
    private const MIME_BY_EXTENSION = [
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'ppt' => [
            'application/vnd.ms-powerpoint',
            'application/mspowerpoint',
            'application/powerpoint',
            'application/x-mspowerpoint',
        ],
        'pptx' => [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
        ],
    ];

    /**
     * @return array<string, int|string|null>
     */
    public function storeUploadedDocument(UploadedFile $file, KmPengajuan $document): array
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'File upload tidak valid, rusak, atau ukurannya terlalu besar.',
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string) ($file->getMimeType() ?: mime_content_type($file->getPathname()));

        if (! isset(self::MIME_BY_EXTENSION[$extension])
            || ! in_array($mime, self::MIME_BY_EXTENSION[$extension], true)) {
            throw ValidationException::withMessages([
                'file' => 'Isi file tidak sesuai dengan format PDF, PPT, atau PPTX yang diizinkan.',
            ]);
        }

        if ($extension === 'pdf' && ! $this->hasPdfSignature($file)) {
            throw ValidationException::withMessages([
                'file' => 'Isi file tidak sesuai dengan format PDF yang valid.',
            ]);
        }

        $path = sprintf(
            'documents/%d/%s.%s',
            $document->getKey(),
            Str::uuid()->toString(),
            $extension,
        );
        $stream = fopen($file->getPathname(), 'rb');

        if ($stream === false) {
            throw ValidationException::withMessages(['file' => 'File upload tidak dapat dibaca.']);
        }

        try {
            Storage::disk(self::DISK)->writeStream($path, $stream, ['visibility' => 'private']);
        } finally {
            fclose($stream);
        }

        $checksum = hash_file('sha256', $file->getPathname());
        if ($checksum === false || hash_file('sha256', Storage::disk(self::DISK)->path($path)) !== $checksum) {
            Storage::disk(self::DISK)->delete($path);
            throw ValidationException::withMessages([
                'file' => 'Checksum file upload tidak dapat diverifikasi.',
            ]);
        }

        $isPdf = in_array($mime, ['application/pdf', 'application/x-pdf'], true);

        // Tentukan thumbnail_status awal berdasarkan MIME
        $thumbnailStatus = $isPdf
            ? KmThumbnailStatus::PENDING->value
            : KmThumbnailStatus::UNSUPPORTED->value;

        return [
            'file' => basename($path),
            'file_name' => $file->getClientOriginalName(),
            'file_disk' => self::DISK,
            'file_path' => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type' => $extension === 'pdf' ? 'application/pdf' : $mime,
            'file_size_bytes' => max(0, (int) $file->getSize()),
            'file_checksum_sha256' => $checksum,
            'file_migrated_at' => now(),
            // Reset thumbnail pipeline saat file baru disimpan
            'thumbnail_status' => $thumbnailStatus,
            'thumbnail_failure_reason' => null,
        ];
    }

    private function hasPdfSignature(UploadedFile $file): bool
    {
        $stream = fopen($file->getPathname(), 'rb');
        if ($stream === false) {
            return false;
        }

        try {
            return fread($stream, 5) === '%PDF-';
        } finally {
            fclose($stream);
        }
    }

    public function discardStoredPath(?string $path): void
    {
        if ($path !== null && $this->isSafePrivatePath($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function streamPreview(KmPengajuan $document): BinaryFileResponse
    {
        if (Schema::hasTable('km_document_versions')) {
            $versionId = $document->published_version_id ?? $document->current_version_id;
            if ($versionId !== null) {
                $version = KmDocumentVersion::query()->find($versionId);
                if ($version === null) {
                    abort(404, 'Versi dokumen tidak tersedia.');
                }

                return $this->streamVersionPreview($version);
            }
        }

        $path = $this->verifiedLocalPath($document);

        if (! $document->isPreviewableFile()) {
            abort(415, 'Preview hanya tersedia untuk dokumen PDF. Unduhan dinonaktifkan.');
        }

        $response = response()->file($path, $this->securityHeaders($document));
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $this->safeDownloadName($document),
        );
        $response->setPrivate();

        return $response;
    }

    public function streamVersionPreview(KmDocumentVersion $version): BinaryFileResponse
    {
        if (! $version->isReady()) {
            abort(415, 'Versi dokumen belum siap dipratinjau.');
        }
        $path = $this->verifiedVersionPdfPath($version);
        $name = pathinfo((string) $version->original_name, PATHINFO_FILENAME);
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', basename($name)) ?: 'dokumen-km-'.$version->km_pengajuan_id;
        $response = response()->file($path, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $name.'.pdf');
        $response->setPrivate();

        return $response;
    }

    public function streamAdminRecovery(
        KmDocumentVersion $version,
        User $actor,
        string $reason,
        string $requestId,
    ): BinaryFileResponse {
        if ($version->processing_status !== \App\Enums\KnowledgeManagement\KmProcessingStatus::FAILED
            || $version->antivirus_status !== 'clean') {
            abort(409, 'Recovery hanya tersedia untuk file bersih yang gagal diproses.');
        }
        $path = $this->verifiedVersionOriginalPath($version);
        DB::table('km_document_recovery_audits')->insert([
            'document_version_id' => $version->getKey(),
            'actor_id' => $actor->getKey(),
            'reason' => trim($reason),
            'checksum_sha256' => $version->original_checksum_sha256,
            'request_id' => $requestId,
            'created_at' => now(),
        ]);
        $response = response()->download(
            $path,
            basename((string) $version->original_name),
            [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
            ],
        );
        $response->setPrivate();

        return $response;
    }

    private function verifiedLocalPath(KmPengajuan $document): string
    {
        if ($document->file_disk !== self::DISK
            || ! is_string($document->file_path)
            || ! $this->isSafePrivatePath($document->file_path, (int) $document->getKey())
            || ! Storage::disk(self::DISK)->exists($document->file_path)) {
            abort(404, 'File dokumen tidak tersedia pada private storage.');
        }

        $path = Storage::disk(self::DISK)->path($document->file_path);
        $checksum = hash_file('sha256', $path);
        if ($checksum === false
            || ! is_string($document->file_checksum_sha256)
            || ! hash_equals($document->file_checksum_sha256, $checksum)) {
            abort(409, 'Checksum file dokumen tidak sesuai.');
        }

        return $path;
    }

    private function verifiedVersionPdfPath(KmDocumentVersion $version): string
    {
        if ($version->normalized_pdf_disk !== self::DISK
            || ! is_string($version->normalized_pdf_path)
            || ! $this->isSafeVersionPdfPath($version)
            || ! Storage::disk(self::DISK)->exists($version->normalized_pdf_path)) {
            abort(404, 'PDF versi tidak tersedia pada private storage.');
        }
        $path = Storage::disk(self::DISK)->path($version->normalized_pdf_path);
        $checksum = hash_file('sha256', $path);
        if ($checksum === false
            || ! is_string($version->normalized_pdf_checksum_sha256)
            || ! hash_equals($version->normalized_pdf_checksum_sha256, $checksum)) {
            abort(409, 'Checksum PDF versi tidak sesuai.');
        }

        return $path;
    }

    private function verifiedVersionOriginalPath(KmDocumentVersion $version): string
    {
        if ($version->original_disk !== self::DISK
            || ! is_string($version->original_path)
            || ! $this->isSafePrivatePath($version->original_path, (int) $version->km_pengajuan_id)
            || ! Storage::disk(self::DISK)->exists($version->original_path)) {
            abort(404, 'File original versi tidak tersedia.');
        }
        $path = Storage::disk(self::DISK)->path($version->original_path);
        $checksum = hash_file('sha256', $path);
        if ($checksum === false
            || ! is_string($version->original_checksum_sha256)
            || ! hash_equals($version->original_checksum_sha256, $checksum)) {
            abort(409, 'Checksum file original versi tidak sesuai.');
        }

        return $path;
    }

    private function isSafeVersionPdfPath(KmDocumentVersion $version): bool
    {
        $path = str_replace('\\', '/', (string) $version->normalized_pdf_path);
        if ($path === (string) $version->original_path) {
            return $this->isSafePrivatePath($path, (int) $version->km_pengajuan_id);
        }

        return preg_match(
            '#^documents/'.preg_quote((string) $version->km_pengajuan_id, '#')
            .'/versions/'.preg_quote((string) $version->getKey(), '#').'/normalized\.pdf$#',
            $path,
        ) === 1;
    }

    /**
     * @return array<string, string>
     */
    private function securityHeaders(KmPengajuan $document): array
    {
        return [
            'Content-Type' => $document->isPreviewableFile()
                ? 'application/pdf'
                : (string) $document->file_mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ];
    }

    public function hasVerifiedVersionPdf(KmDocumentVersion $version): bool
    {
        try {
            $this->verifiedVersionPdfPath($version);

            return true;
        } catch (HttpExceptionInterface) {
            return false;
        }
    }

    private function safeDownloadName(KmPengajuan $document): string
    {
        $name = basename((string) ($document->file_original_name ?: $document->file_name));
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?: '';

        if ($name === '') {
            $extension = match ($document->file_mime_type) {
                'application/pdf', 'application/x-pdf' => 'pdf',
                'application/vnd.ms-powerpoint' => 'ppt',
                default => 'pptx',
            };

            return sprintf('dokumen-km-%d.%s', $document->getKey(), $extension);
        }

        return $name;
    }

    private function isSafePrivatePath(string $path, ?int $documentId = null): bool
    {
        $normalized = str_replace('\\', '/', $path);
        $documentSegment = $documentId === null
            ? '[0-9]+'
            : preg_quote((string) $documentId, '#');

        return $normalized !== ''
            && ! str_starts_with($normalized, '/')
            && ! str_contains($normalized, '../')
            && ! str_contains($normalized, "\0")
            && preg_match(
                '#^documents/'.$documentSegment.'/[a-f0-9-]+\.(pdf|ppt|pptx)$#i',
                $normalized,
            ) === 1;
    }
}
