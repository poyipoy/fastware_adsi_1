<?php

namespace App\Console\Commands;

use App\Enums\KnowledgeManagement\KmProcessingStatus;
use App\Models\KmDocumentVersion;
use App\Services\KnowledgeManagement\KmDocumentProcessingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessPendingKmDocumentsCommand extends Command
{
    protected $signature = 'km:process-pending-documents {--limit=1 : Maksimum versi per eksekusi}';

    protected $description = 'Memproses antivirus, konversi, OCR, teks, dan thumbnail dokumen KM.';

    public function handle(KmDocumentProcessingService $processor): int
    {
        if (! (bool) config('knowledge_management.processing.enabled', false)) {
            $this->warn('Pemrosesan dokumen KM dinonaktifkan oleh konfigurasi.');

            return self::SUCCESS;
        }

        $limit = max(1, min(20, (int) $this->option('limit')));
        $processed = 0;
        $failed = 0;
        $this->recoverStaleClaims();

        for ($index = 0; $index < $limit; $index++) {
            $version = DB::transaction(function (): ?KmDocumentVersion {
                $candidate = KmDocumentVersion::query()
                    ->whereIn('processing_status', [
                        KmProcessingStatus::PENDING->value,
                        KmProcessingStatus::RETRY_PENDING->value,
                    ])
                    ->where(static function ($query): void {
                        $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
                    })
                    ->orderBy('id')
                    ->lock('for update skip locked')
                    ->first();
                if ($candidate === null) {
                    return null;
                }
                $candidate->forceFill([
                    'processing_status' => KmProcessingStatus::PROCESSING,
                    'processing_attempts' => (int) $candidate->processing_attempts + 1,
                    'processing_started_at' => now(),
                    'next_attempt_at' => null,
                ])->save();

                return $candidate->refresh();
            }, 3);

            if ($version === null) {
                break;
            }
            try {
                $processor->process($version);
                $processed++;
                $this->line('READY version='.$version->getKey());
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
                $this->error('FAILED version='.$version->getKey().' '.$exception->getMessage());
            }
        }

        $this->info("processed={$processed} failed={$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function recoverStaleClaims(): void
    {
        $cutoff = now()->subMinutes(max(
            10,
            (int) config('knowledge_management.processing.stale_after_minutes', 60),
        ));
        $maximum = max(1, (int) config('knowledge_management.processing.maximum_attempts', 3));

        KmDocumentVersion::query()
            ->where('processing_status', KmProcessingStatus::PROCESSING->value)
            ->where('processing_started_at', '<=', $cutoff)
            ->where('processing_attempts', '>=', $maximum)
            ->update([
                'processing_status' => KmProcessingStatus::FAILED->value,
                'next_attempt_at' => null,
                'last_error' => 'Pemrosesan terhenti melewati batas stale setelah percobaan terakhir.',
                'updated_at' => now(),
            ]);
        KmDocumentVersion::query()
            ->where('processing_status', KmProcessingStatus::PROCESSING->value)
            ->where('processing_started_at', '<=', $cutoff)
            ->where('processing_attempts', '<', $maximum)
            ->update([
                'processing_status' => KmProcessingStatus::RETRY_PENDING->value,
                'next_attempt_at' => now(),
                'last_error' => 'Pemrosesan sebelumnya terhenti dan dijadwalkan ulang.',
                'updated_at' => now(),
            ]);
    }
}
