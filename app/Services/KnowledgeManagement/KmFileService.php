<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmThumbnailStatus;
use App\Jobs\KnowledgeManagement\GenerateKmPdfThumbnail;
use App\Models\KmPengajuan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

    /**
     * Dispatch thumbnail setelah response; pemanggil harus sudah menyelesaikan transaction dokumen.
     */
    public function dispatchThumbnailJobIfPdf(KmPengajuan $document): void
    {
        if (! in_array($document->file_mime_type, ['application/pdf', 'application/x-pdf'], true)) {
            return;
        }

        if (empty($document->file_checksum_sha256)) {
            return;
        }

        GenerateKmPdfThumbnail::dispatch(
            $document->getKey(),
            (string) $document->file_checksum_sha256
        )->onQueue((string) config('knowledge_management.queue.thumbnail_job', 'default'))
            ->afterResponse();
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
        $path = $this->verifiedLocalPath($document);

        if (! $document->isPreviewableFile()) {
            abort(415, 'Preview hanya tersedia untuk dokumen PDF. Gunakan tombol download.');
        }

        $response = response()->file($path, $this->securityHeaders($document));
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $this->safeDownloadName($document),
        );
        $response->setPrivate();

        return $response;
    }

    public function streamDownload(KmPengajuan $document): BinaryFileResponse
    {
        $response = response()->download(
            $this->verifiedLocalPath($document),
            $this->safeDownloadName($document),
            $this->securityHeaders($document),
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
