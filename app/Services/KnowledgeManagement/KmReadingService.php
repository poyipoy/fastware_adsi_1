<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Models\KmDocumentVersion;
use App\Models\KmPengajuan;
use App\Models\KmTransaksi;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KmReadingService
{
    public function __construct(
        private readonly KmAccessService $access,
        private readonly KmPointLedgerService $ledger,
        private readonly KmVersioningService $versions,
        private readonly KmReadingSessionService $sessions,
        private readonly KmCompletionService $completions,
    ) {
    }

    /**
     * @return array<string, bool|int|null>
     */
    public function markStarted(
        User $user,
        KmPengajuan $document,
        ?KmDocumentVersion $version = null,
    ): array
    {
        return DB::transaction(function () use ($user, $document, $version): array {
            [$lockedDocument, $lockedVersion] = $this->lockReadableContext($user, $document, $version);
            $transaction = $this->lockOrCreateTransaction(
                $user,
                $lockedDocument,
                $lockedVersion,
                KmReadStatus::READING,
            );

            $alreadyCompleted = $transaction->readStatus() === KmReadStatus::COMPLETED
                || $transaction->points_awarded_at !== null;

            if (! $alreadyCompleted && $transaction->readStatus() !== KmReadStatus::READING) {
                $transaction->forceFill([
                    'status' => KmReadStatus::READING->value,
                    'modified_by' => $user->getKey(),
                ])->save();
            }

            $this->incrementDocumentView($lockedDocument, $transaction);

            return [
                'already_completed' => $alreadyCompleted,
                'points_awarded' => 0,
                ...$this->progressState($transaction, $lockedDocument, $lockedVersion),
            ];
        });
    }

    /**
     * @param  array{last_page: int, pages_total: int, pages: list<int>, active_delta: int}  $progress
     * @return array<string, bool|int|null>
     */
    public function updateProgress(
        User $user,
        KmPengajuan $document,
        array $progress,
        ?KmDocumentVersion $version = null,
    ): array
    {
        return DB::transaction(function () use ($user, $document, $progress, $version): array {
            [$lockedDocument, $lockedVersion] = $this->lockReadableContext($user, $document, $version);

            $transaction = $this->lockOrCreateTransaction(
                $user,
                $lockedDocument,
                $lockedVersion,
                KmReadStatus::READING,
            );
            if ($transaction->readStatus() === KmReadStatus::COMPLETED) {
                return $this->progressState($transaction, $lockedDocument, $lockedVersion);
            }
            if ($transaction->readStatus() !== KmReadStatus::READING) {
                throw new DomainException('Status baca transaksi Knowledge Management tidak valid.');
            }

            $pagesTotal = (int) $progress['pages_total'];
            $lastPage = (int) $progress['last_page'];
            $pages = array_values(array_unique(array_map('intval', $progress['pages'])));
            if ($pagesTotal <= 0 || $lastPage <= 0 || $lastPage > $pagesTotal) {
                throw new DomainException('Nomor halaman progress tidak valid.');
            }
            foreach ($pages as $page) {
                if ($page <= 0 || $page > $pagesTotal) {
                    throw new DomainException('Daftar halaman progress tidak valid.');
                }
            }

            if ($transaction->pages_total !== null && (int) $transaction->pages_total !== $pagesTotal) {
                throw new DomainException(
                    'Jumlah halaman dokumen berubah. Tutup viewer lalu buka kembali dokumen.'
                );
            }

            $bitmap = $this->decodeBitmap($transaction->unique_pages, $pagesTotal);
            foreach ([...$pages, $lastPage] as $page) {
                $this->setPageBit($bitmap, $page);
            }
            $uniqueCount = $this->countPageBits($bitmap, $pagesTotal);
            $progressPercent = min(100, (int) floor(($uniqueCount / $pagesTotal) * 100));
            $maximumDelta = max(
                0,
                (int) config('knowledge_management.reading.maximum_active_delta_seconds', 120),
            );
            $activeDelta = min(
                $this->sessions->creditedDelta(
                    $user,
                    $lockedDocument,
                    $lockedVersion?->getKey() ?? $this->versions->versionIdForReading($lockedDocument),
                    $progress,
                ),
                $maximumDelta,
            );

            $transaction->forceFill([
                'last_page' => max((int) ($transaction->last_page ?? 0), $lastPage),
                'pages_total' => $pagesTotal,
                'unique_pages' => base64_encode($bitmap),
                'unique_pages_count' => $uniqueCount,
                'active_seconds' => (int) ($transaction->active_seconds ?? 0) + $activeDelta,
                'progress_percent' => $progressPercent,
                'last_progress_at' => now(),
                'modified_by' => $user->getKey(),
            ])->save();
            return $this->progressState($transaction->refresh(), $lockedDocument, $lockedVersion);
        }, 3);
    }

    /**
     * @return array{already_completed: bool, points_awarded: int}
     */
    public function complete(
        User $user,
        KmPengajuan $document,
        ?KmDocumentVersion $version = null,
    ): array
    {
        return DB::transaction(function () use ($user, $document, $version): array {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            [$lockedDocument, $lockedVersion] = $this->lockReadableContext($lockedUser, $document, $version);

            $transaction = $this->lockOrCreateTransaction(
                $lockedUser,
                $lockedDocument,
                $lockedVersion,
                KmReadStatus::READING,
            );

            if ($transaction->readStatus() === KmReadStatus::COMPLETED
                || $transaction->points_awarded_at !== null) {
                return [
                    'already_completed' => true,
                    'points_awarded' => 0,
                ];
            }

            if ($transaction->readStatus() !== KmReadStatus::READING) {
                throw new DomainException('Status baca transaksi Knowledge Management tidak valid.');
            }
            if (($lockedVersion?->isReady() ?? $lockedDocument->isPreviewableFile())
                && ! $this->isCompletionEligible($transaction, $lockedDocument, $lockedVersion)) {
                $requiredPages = $this->requiredUniquePages((int) ($transaction->pages_total ?? 0));
                $requiredSeconds = $this->requiredActiveSeconds((int) ($transaction->pages_total ?? 0));
                throw new DomainException(sprintf(
                    'Belum memenuhi syarat selesai: buka minimal %d halaman unik dan baca aktif minimal %d detik.',
                    $requiredPages,
                    $requiredSeconds,
                ));
            }

            $points = max(0, (int) config('knowledge_management.points.completion', 5));
            $completedAt = now();
            $versionId = $lockedVersion?->getKey()
                ?? $this->versions->versionIdForReading($lockedDocument);
            $awarded = $this->ledger->award(
                $lockedUser,
                'completion',
                'completion:'.$lockedUser->getKey().':'
                    .($versionId ?? $lockedDocument->getKey()),
                $points,
                (int) $lockedDocument->getKey(),
                null,
                ['completion_type' => 'official'],
                $lockedUser,
                $versionId,
            );

            $transaction->forceFill([
                'status' => KmReadStatus::COMPLETED->value,
                'poin' => $points,
                'completed_at' => $completedAt,
                'points_awarded_at' => $completedAt,
                'modified_by' => $lockedUser->getKey(),
            ])->save();
            $this->completions->recordOfficial(
                $lockedUser,
                $lockedDocument,
                $transaction->refresh(),
            );

            return [
                'already_completed' => false,
                'points_awarded' => $awarded ? $points : 0,
            ];
        }, 3);
    }

    /** @return array{KmPengajuan, KmDocumentVersion|null} */
    private function lockReadableContext(
        User $user,
        KmPengajuan $document,
        ?KmDocumentVersion $version,
    ): array
    {
        $lockedDocument = KmPengajuan::query()
            ->whereKey($document->getKey())
            ->lockForUpdate()
            ->firstOrFail();
        if ($version !== null) {
            $lockedVersion = KmDocumentVersion::query()
                ->whereKey($version->getKey())
                ->where('km_pengajuan_id', $lockedDocument->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if (! $this->access->canReadVersion($user, $lockedDocument, $lockedVersion)) {
                throw new AuthorizationException('Versi dokumen Knowledge Management tidak dapat diakses.');
            }

            return [$lockedDocument, $lockedVersion];
        }

        if (! $this->access->isPublishedDocumentEligible($user, $lockedDocument)) {
            throw new AuthorizationException('Dokumen Knowledge Management tidak dapat diakses.');
        }

        $versionId = $this->versions->versionIdForReading($lockedDocument);
        $lockedVersion = $versionId === null
            ? null
            : KmDocumentVersion::query()->whereKey($versionId)->lockForUpdate()->first();

        return [$lockedDocument, $lockedVersion];
    }

    private function lockOrCreateTransaction(
        User $user,
        KmPengajuan $document,
        ?KmDocumentVersion $version,
        KmReadStatus $initialStatus,
    ): KmTransaksi {
        $versionId = $version?->getKey() ?? $this->versions->versionIdForReading($document);
        $query = KmTransaksi::query()
            ->where('id_user', $user->getKey())
            ->where('id_km_pengajuan', $document->getKey());
        if ($versionId !== null) {
            $query->where('document_version_id', $versionId);
        }

        $transaction = (clone $query)->lockForUpdate()->first();
        if ($transaction !== null) {
            return $transaction;
        }

        try {
            $attributes = [
                'id_user' => $user->getKey(),
                'id_km_pengajuan' => $document->getKey(),
                'level' => 0,
                'status' => $initialStatus->value,
                'modified_by' => $user->getKey(),
            ];
            if (Schema::hasColumn('km_transaksis', 'document_version_id')) {
                $attributes['document_version_id'] = $versionId;
            }
            KmTransaksi::query()->create($attributes);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }
        }

        return (clone $query)->lockForUpdate()->firstOrFail();
    }

    /**
     * @return array{last_page: int|null, pages_total: int|null, unique_pages_count: int, active_seconds: int, progress_percent: int, completion_eligible: bool}
     */
    private function progressState(
        KmTransaksi $transaction,
        KmPengajuan $document,
        ?KmDocumentVersion $version = null,
    ): array
    {
        return [
            'last_page' => $transaction->last_page === null ? null : (int) $transaction->last_page,
            'pages_total' => $transaction->pages_total === null ? null : (int) $transaction->pages_total,
            'unique_pages_count' => (int) ($transaction->unique_pages_count ?? 0),
            'active_seconds' => (int) ($transaction->active_seconds ?? 0),
            'progress_percent' => (int) ($transaction->progress_percent ?? 0),
            'completion_eligible' => $this->isCompletionEligible($transaction, $document, $version),
        ];
    }

    private function isCompletionEligible(
        KmTransaksi $transaction,
        KmPengajuan $document,
        ?KmDocumentVersion $version = null,
    ): bool
    {
        if ($transaction->readStatus() === KmReadStatus::COMPLETED) {
            return true;
        }
        if (! ($version?->isReady() ?? $document->isPreviewableFile())) {
            return true;
        }

        $pagesTotal = (int) ($transaction->pages_total ?? 0);

        return $pagesTotal > 0
            && (int) ($transaction->unique_pages_count ?? 0) >= $this->requiredUniquePages($pagesTotal)
            && (int) ($transaction->active_seconds ?? 0) >= $this->requiredActiveSeconds($pagesTotal);
    }

    private function requiredUniquePages(int $pagesTotal): int
    {
        $ratio = min(1, max(0, (float) config('knowledge_management.reading.unique_page_ratio', 0.9)));

        return $pagesTotal > 0 ? (int) ceil($ratio * $pagesTotal) : 1;
    }

    private function requiredActiveSeconds(int $pagesTotal): int
    {
        $minimum = max(0, (int) config('knowledge_management.reading.minimum_active_seconds', 60));
        $perPage = max(0, (int) config('knowledge_management.reading.seconds_per_page', 20));
        $maximum = max($minimum, (int) config('knowledge_management.reading.maximum_required_seconds', 900));

        return max($minimum, min($perPage * max(1, $pagesTotal), $maximum));
    }

    private function decodeBitmap(?string $encoded, int $pagesTotal): string
    {
        $bytesRequired = (int) ceil($pagesTotal / 8);
        if ($encoded === null || $encoded === '') {
            return str_repeat("\0", $bytesRequired);
        }

        $decoded = base64_decode($encoded, true);
        if ($decoded === false || strlen($decoded) > $bytesRequired) {
            throw new DomainException('Bitmap progress tersimpan tidak valid. Hubungi administrator.');
        }

        return str_pad($decoded, $bytesRequired, "\0");
    }

    private function setPageBit(string &$bitmap, int $page): void
    {
        $offset = $page - 1;
        $byte = intdiv($offset, 8);
        $bit = $offset % 8;
        $bitmap[$byte] = chr(ord($bitmap[$byte]) | (1 << $bit));
    }

    private function countPageBits(string $bitmap, int $pagesTotal): int
    {
        $count = 0;
        $bytes = strlen($bitmap);
        for ($index = 0; $index < $bytes; $index++) {
            $count += substr_count(decbin(ord($bitmap[$index])), '1');
        }

        return min($count, $pagesTotal);
    }

    private function incrementDocumentView(KmPengajuan $document, KmTransaksi $transaction): void
    {
        $now = now();
        try {
            DB::table('km_lihat_bukus')->insert([
                'id_km_transaksi' => $transaction->getKey(),
                'id_km_pengajuan' => $document->getKey(),
                'jumlah_lihat' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation(
                $exception,
                'km_lihat_bukus_document_unique',
            )) {
                throw $exception;
            }
        }

        DB::table('km_lihat_bukus')
            ->where('id_km_pengajuan', $document->getKey())
            ->update([
                'jumlah_lihat' => DB::raw('jumlah_lihat + 1'),
                'updated_at' => $now,
            ]);
    }

    private function isUniqueConstraintViolation(
        QueryException $exception,
        ?string $constraint = null,
    ): bool {
        if (! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
            return false;
        }

        $constraints = $constraint === null
            ? ['km_transaksis_user_version_unique', 'km_transaksis_user_document_unique']
            : [$constraint];
        $message = strtolower($exception->getMessage());

        return collect($constraints)->contains(
            static fn (string $name): bool => str_contains($message, strtolower($name)),
        );
    }
}
