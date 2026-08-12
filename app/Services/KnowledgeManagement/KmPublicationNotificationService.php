<?php

namespace App\Services\KnowledgeManagement;

use App\Models\KmDocumentVersion;
use App\Models\KmPublicationBatch;
use App\Models\KmPublicationRecipient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class KmPublicationNotificationService
{
    public function __construct(
        private readonly KmAccessService $access,
        private readonly KmTargetingService $targeting,
        private readonly KmNotificationService $notifications,
    ) {
    }

    public function queue(KmDocumentVersion $version): ?KmPublicationBatch
    {
        if (! Schema::hasTable('km_publication_batches')) {
            return null;
        }

        $batch = KmPublicationBatch::query()->firstOrCreate(
            ['document_version_id' => $version->getKey()],
            ['status' => 'pending'],
        );
        $this->snapshotRecipients($batch, $version->loadMissing('document'));

        return $batch->refresh();
    }

    /** @return array{batches: int, recipients: int, notifications: int} */
    public function dispatch(int $limit = 5): array
    {
        $totals = ['batches' => 0, 'recipients' => 0, 'notifications' => 0];
        if (! Schema::hasTable('km_publication_batches')) {
            return $totals;
        }

        for ($index = 0; $index < max(1, min($limit, 50)); $index++) {
            $batch = $this->claimNextBatch();
            if ($batch === null) {
                break;
            }

            try {
                $result = $this->expandAndNotify($batch);
                $totals['batches']++;
                $totals['recipients'] += $result['recipients'];
                $totals['notifications'] += $result['notifications'];
            } catch (Throwable $exception) {
                report($exception);
                $batch->forceFill([
                    'status' => 'retry_pending',
                    'last_error' => mb_substr($exception->getMessage(), 0, 4000),
                ])->save();
            }
        }

        return $totals;
    }

    private function claimNextBatch(): ?KmPublicationBatch
    {
        return DB::transaction(function (): ?KmPublicationBatch {
            KmPublicationBatch::query()
                ->where('status', 'processing')
                ->where('updated_at', '<=', now()->subMinutes(30))
                ->update([
                    'status' => 'retry_pending',
                    'last_error' => 'Dispatch sebelumnya terhenti dan dijadwalkan ulang.',
                    'updated_at' => now(),
                ]);
            $batch = KmPublicationBatch::query()
                ->whereIn('status', ['pending', 'retry_pending'])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
            if ($batch === null) {
                return null;
            }

            $batch->forceFill([
                'status' => 'processing',
                'started_at' => $batch->started_at ?? now(),
                'last_error' => null,
            ])->save();

            return $batch;
        }, 3);
    }

    /** @return array{recipients: int, notifications: int} */
    private function expandAndNotify(KmPublicationBatch $batch): array
    {
        $version = $batch->version()->with('document')->firstOrFail();
        $document = $version->document;
        // Compatibility for batches created before recipient snapshotting was added.
        $this->snapshotRecipients($batch, $version);

        $notifications = 0;
        KmPublicationRecipient::query()
            ->where('publication_batch_id', $batch->getKey())
            ->whereNull('notified_at')
            ->orderBy('id')
            ->chunkById(200, function ($recipients) use ($batch, $version, $document, &$notifications): void {
                foreach ($recipients as $recipient) {
                    $this->notifications->record(
                        (int) $recipient->user_id,
                        'new_material',
                        'new_material:v'.$version->getKey().':u'.$recipient->user_id,
                        [
                            'document_id' => $document->getKey(),
                            'document_version_id' => $version->getKey(),
                            'title' => $version->title,
                        ],
                    );
                    $recipient->forceFill(['notified_at' => now()])->save();
                    $notifications++;
                }
            });

        $recipientCount = KmPublicationRecipient::query()
            ->where('publication_batch_id', $batch->getKey())->count();
        $batch->forceFill([
            'status' => 'completed',
            'recipient_count' => $recipientCount,
            'processed_count' => $recipientCount,
            'completed_at' => now(),
            'last_error' => null,
        ])->save();

        return ['recipients' => $recipientCount, 'notifications' => $notifications];
    }

    private function snapshotRecipients(
        KmPublicationBatch $batch,
        KmDocumentVersion $version,
    ): void {
        $document = $version->document;
        User::query()
            ->where('is_active', false)
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($batch, $document): void {
                foreach ($users as $user) {
                    if (! $this->access->isPublishedDocumentEligible($user, $document)) {
                        continue;
                    }
                    $snapshot = $this->targeting->snapshot($user);
                    KmPublicationRecipient::query()->firstOrCreate([
                        'publication_batch_id' => $batch->getKey(),
                        'user_id' => $user->getKey(),
                    ], [
                        'department_snapshot' => $snapshot['department'],
                        'job_position_snapshot' => $snapshot['position'],
                    ]);
                }
            });

        $batch->forceFill([
            'recipient_count' => KmPublicationRecipient::query()
                ->where('publication_batch_id', $batch->getKey())
                ->count(),
        ])->save();
    }
}
