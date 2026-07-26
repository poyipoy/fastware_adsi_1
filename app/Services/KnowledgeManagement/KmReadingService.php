<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Models\KmPengajuan;
use App\Models\KmTransaksi;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class KmReadingService
{
    public function __construct(
        private readonly KmAccessService $access,
    ) {
    }

    /**
     * @return array{already_completed: bool, points_awarded: int}
     */
    public function markStarted(User $user, KmPengajuan $document): array
    {
        return DB::transaction(function () use ($user, $document): array {
            $lockedDocument = KmPengajuan::query()
                ->whereKey($document->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if (! $this->access->canView($user, $lockedDocument)) {
                throw new AuthorizationException('Dokumen Knowledge Management tidak dapat diakses.');
            }

            $transaction = $this->lockOrCreateTransaction(
                $user,
                $lockedDocument,
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
            ];
        });
    }

    /**
     * @return array{already_completed: bool, points_awarded: int}
     */
    public function complete(User $user, KmPengajuan $document): array
    {
        return DB::transaction(function () use ($user, $document): array {
            $lockedDocument = KmPengajuan::query()
                ->whereKey($document->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            if (! $this->access->isPublishedDocumentEligible($lockedUser, $lockedDocument)) {
                throw new AuthorizationException('Dokumen tidak memenuhi syarat untuk diselesaikan.');
            }

            $transaction = $this->lockOrCreateTransaction(
                $lockedUser,
                $lockedDocument,
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

            $category = $lockedDocument->kmKategori()->lockForUpdate()->first();
            if ($category === null || ! is_numeric($category->poin_kategori)) {
                throw new DomainException('Kategori atau poin kategori dokumen tidak valid.');
            }

            $points = (int) $category->poin_kategori;
            if ($points < 0) {
                throw new DomainException('Poin kategori tidak boleh bernilai negatif.');
            }

            $completedAt = now();
            $transaction->forceFill([
                'status' => KmReadStatus::COMPLETED->value,
                'poin' => $points,
                'completed_at' => $completedAt,
                'points_awarded_at' => $completedAt,
                'modified_by' => $lockedUser->getKey(),
            ])->save();

            User::query()
                ->whereKey($lockedUser->getKey())
                ->update([
                    'km_total_poin' => DB::raw('COALESCE(km_total_poin, 0) + '.(int) $points),
                ]);

            return [
                'already_completed' => false,
                'points_awarded' => $points,
            ];
        }, 3);
    }

    private function lockOrCreateTransaction(
        User $user,
        KmPengajuan $document,
        KmReadStatus $initialStatus,
    ): KmTransaksi {
        $query = KmTransaksi::query()
            ->where('id_user', $user->getKey())
            ->where('id_km_pengajuan', $document->getKey());

        $transaction = (clone $query)->lockForUpdate()->first();
        if ($transaction !== null) {
            return $transaction;
        }

        try {
            KmTransaksi::query()->create([
                'id_user' => $user->getKey(),
                'id_km_pengajuan' => $document->getKey(),
                'level' => 0,
                'status' => $initialStatus->value,
                'modified_by' => $user->getKey(),
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }
        }

        return (clone $query)->lockForUpdate()->firstOrFail();
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
        string $constraint = 'km_transaksis_user_document_unique',
    ): bool {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            && str_contains(
                strtolower($exception->getMessage()),
                strtolower($constraint),
            );
    }
}
