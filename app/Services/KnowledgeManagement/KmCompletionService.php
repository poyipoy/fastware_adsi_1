<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Models\KmAssignmentUser;
use App\Models\KmCompletionEvent;
use App\Models\KmDocumentVersion;
use App\Models\KmPengajuan;
use App\Models\KmTransaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class KmCompletionService
{
    public function __construct(
        private readonly KmPointLedgerService $ledger,
        private readonly KmNotificationService $notifications,
        private readonly KmGamificationService $gamification,
    ) {
    }

    public function recordOfficial(
        User $user,
        KmPengajuan $document,
        KmTransaksi $transaction,
    ): ?KmCompletionEvent {
        $versionId = $transaction->document_version_id;
        if ($versionId === null || ! Schema::hasTable('km_completion_events')) {
            return null;
        }

        return $this->record(
            $user,
            $document,
            (int) $versionId,
            $transaction,
            'official',
            $user,
            null,
            [
                'unique_pages_count' => (int) $transaction->unique_pages_count,
                'pages_total' => (int) $transaction->pages_total,
                'active_seconds' => (int) $transaction->active_seconds,
                'progress_percent' => (int) $transaction->progress_percent,
                'acknowledged' => true,
            ],
        );
    }

    public function manualOverride(
        User $actor,
        User $user,
        KmDocumentVersion $version,
        string $reason,
    ): KmCompletionEvent {
        return DB::transaction(function () use ($actor, $user, $version, $reason): KmCompletionEvent {
            $document = KmPengajuan::query()->whereKey($version->km_pengajuan_id)->lockForUpdate()->firstOrFail();
            $transaction = KmTransaksi::query()
                ->where('id_user', $user->getKey())
                ->where('id_km_pengajuan', $document->getKey())
                ->where('document_version_id', $version->getKey())
                ->lockForUpdate()->first();
            if (($transaction !== null
                    && ($transaction->readStatus() === KmReadStatus::COMPLETED
                        || $transaction->completed_at !== null))
                || KmCompletionEvent::query()
                    ->where('user_id', $user->getKey())
                    ->where('document_version_id', $version->getKey())
                    ->exists()) {
                throw ValidationException::withMessages([
                    'reason' => 'Pengguna sudah memiliki completion untuk versi ini.',
                ]);
            }
            if ($transaction === null) {
                $transaction = KmTransaksi::query()->create([
                    'id_user' => $user->getKey(),
                    'id_km_pengajuan' => $document->getKey(),
                    'document_version_id' => $version->getKey(),
                    'level' => 0,
                    'status' => KmReadStatus::READING->value,
                    'modified_by' => $actor->getKey(),
                ]);
            }
            $points = max(0, (int) config('knowledge_management.points.completion', 5));
            $completedAt = $transaction->completed_at ?? now();
            $this->ledger->award(
                $user,
                'completion',
                'completion:'.$user->getKey().':'.$version->getKey(),
                $points,
                (int) $document->getKey(),
                null,
                ['completion_type' => 'manual_accessibility'],
                $actor,
                (int) $version->getKey(),
            );
            $transaction->forceFill([
                'status' => KmReadStatus::COMPLETED->value,
                'poin' => $points,
                'completed_at' => $completedAt,
                'points_awarded_at' => $transaction->points_awarded_at ?? $completedAt,
                'modified_by' => $actor->getKey(),
            ])->save();

            $event = $this->record(
                $user,
                $document,
                (int) $version->getKey(),
                $transaction,
                'manual_accessibility',
                $actor,
                $reason,
                ['acknowledged' => false, 'accessibility_override' => true],
            );
            $this->notifications->record(
                $user,
                'completion_overridden',
                'completion_overridden:'.$event->getKey().':u'.$user->getKey(),
                [
                    'document_id' => $document->getKey(),
                    'document_version_id' => $version->getKey(),
                    'title' => $version->title,
                    'reason' => $reason,
                ],
            );

            return $event;
        }, 3);
    }

    /** @param array<string, mixed> $evidence */
    private function record(
        User $user,
        KmPengajuan $document,
        int $versionId,
        KmTransaksi $transaction,
        string $type,
        User $actor,
        ?string $reason,
        array $evidence,
    ): KmCompletionEvent {
        $event = KmCompletionEvent::query()->firstOrCreate([
            'event_key' => 'completion:'.$user->getKey().':'.$versionId,
        ], [
            'user_id' => $user->getKey(),
            'document_id' => $document->getKey(),
            'document_version_id' => $versionId,
            'transaction_id' => $transaction->getKey(),
            'completion_type' => $type,
            'acknowledged_at' => $type === 'official' ? now() : null,
            'actor_id' => $actor->getKey(),
            'reason' => $reason,
            'evidence_snapshot' => $evidence,
            'completed_at' => $transaction->completed_at ?? now(),
        ]);

        if (Schema::hasTable('km_assignment_users')) {
            KmAssignmentUser::query()
                ->where('user_id', $user->getKey())
                ->whereNull('completed_at')
                ->whereNull('exempted_at')
                ->whereHas('assignment', static fn ($query) => $query
                    ->where('document_version_id', $versionId))
                ->update([
                    'completed_at' => $event->completed_at,
                    'completion_event_id' => $event->getKey(),
                    'updated_at' => now(),
                ]);
        }
        $this->gamification->awardEligible($user);

        return $event;
    }
}
