<?php

namespace App\Jobs\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmThumbnailStatus;
use App\Models\KmPengajuan;
use App\Services\KnowledgeManagement\KmPdfThumbnailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateKmPdfThumbnail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public readonly int $documentId,
        public readonly string $expectedChecksum,
    ) {
    }

    public function handle(KmPdfThumbnailService $service): void
    {
        $document = KmPengajuan::query()->find($this->documentId);
        if ($document === null || ! hash_equals((string) $document->file_checksum_sha256, $this->expectedChecksum)) {
            return;
        }

        if ($document->thumbnail_status === KmThumbnailStatus::READY
            && hash_equals((string) $document->thumbnail_source_checksum, $this->expectedChecksum)) {
            return;
        }

        if (! config('knowledge_management.thumbnail.enabled', false)) {
            $this->markStatus(KmThumbnailStatus::UNAVAILABLE, 'Pembuatan thumbnail dinonaktifkan.');

            return;
        }

        if (! in_array($document->file_mime_type, ['application/pdf', 'application/x-pdf'], true)) {
            $this->markStatus(KmThumbnailStatus::UNSUPPORTED);

            return;
        }

        if ($this->markStatus(KmThumbnailStatus::PROCESSING) === 0) {
            return;
        }

        if (($capabilityError = $service->probeCapability()) !== null) {
            $this->markStatus(KmThumbnailStatus::UNAVAILABLE, $capabilityError);

            return;
        }

        $sourceDisk = (string) config('knowledge_management.disk', 'km_private');
        $sourcePath = Storage::disk($sourceDisk)->path((string) $document->file_path);
        if (($sourceError = $service->sourceValidationError($sourcePath)) !== null) {
            $this->markStatus(KmThumbnailStatus::FAILED, $sourceError);

            return;
        }

        $oldThumbnailPath = $document->thumbnail_path;
        $newThumbnailPath = null;

        try {
            $newThumbnailPath = $service->generate($document, $sourcePath);
            $switched = DB::table('km_pengajuans')
                ->where('id', $this->documentId)
                ->where('file_checksum_sha256', $this->expectedChecksum)
                ->update([
                    'thumbnail_path' => $newThumbnailPath,
                    'thumbnail_status' => KmThumbnailStatus::READY->value,
                    'thumbnail_source_checksum' => $this->expectedChecksum,
                    'thumbnail_generated_at' => now(),
                    'thumbnail_failure_reason' => null,
                    'updated_at' => now(),
                ]);

            if ($switched === 0) {
                $service->deleteThumbnail($newThumbnailPath);

                return;
            }

            if ($oldThumbnailPath !== $newThumbnailPath) {
                $service->deleteThumbnail($oldThumbnailPath);
            }
        } catch (Throwable $exception) {
            $service->deleteThumbnail($newThumbnailPath);
            $this->markStatus(KmThumbnailStatus::FAILED, $service->sanitizeReason($exception->getMessage()));

            if ((string) config('queue.default') !== 'sync') {
                throw $exception;
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        $reason = preg_replace('/[^\x20-\x7E]/', '', $exception->getMessage()) ?? '';

        DB::table('km_pengajuans')
            ->where('id', $this->documentId)
            ->where('file_checksum_sha256', $this->expectedChecksum)
            ->update([
                'thumbnail_status' => KmThumbnailStatus::FAILED->value,
                'thumbnail_failure_reason' => mb_substr('Percobaan maksimum terlampaui: '.trim($reason), 0, 500),
                'updated_at' => now(),
            ]);
    }

    private function markStatus(KmThumbnailStatus $status, ?string $reason = null): int
    {
        return DB::table('km_pengajuans')
            ->where('id', $this->documentId)
            ->where('file_checksum_sha256', $this->expectedChecksum)
            ->update([
                'thumbnail_status' => $status->value,
                'thumbnail_failure_reason' => $reason === null ? null : mb_substr($reason, 0, 500),
                'updated_at' => now(),
            ]);
    }
}
