<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmApprovalAction;
use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Exceptions\KnowledgeManagement\KmBulkApprovalConflictException;
use App\Models\KmApprovalEvent;
use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class KmApprovalService
{
    public function __construct(
        private readonly KmDocumentWorkflowService $workflow,
        private readonly KmAccessService $access,
        private readonly KmNotificationService $notifications,
        private readonly KmPointLedgerService $ledger,
        private readonly KmVersioningService $versions,
        private readonly KmTargetingService $targeting,
        private readonly KmPublicationNotificationService $publicationNotifications,
        private readonly KmGamificationService $gamification,
    ) {
    }

    public function submit(KmPengajuan $document, User $actor, array $metadata = []): KmPengajuan
    {
        return $this->perform(
            $document,
            $actor,
            KmApprovalAction::SUBMITTED,
            fn (KmPengajuan $locked): KmPengajuan => $this->workflow->submit($locked),
            null,
            [],
            $metadata,
        );
    }

    public function approve(
        KmPengajuan $document,
        User $actor,
        array $attributes,
        array $metadata = [],
    ): KmPengajuan {
        return $this->perform(
            $document,
            $actor,
            KmApprovalAction::APPROVED,
            fn (KmPengajuan $locked): KmPengajuan => $this->workflow->approve($locked),
            null,
            $attributes,
            $metadata,
        );
    }

    public function reject(
        KmPengajuan $document,
        User $actor,
        string $reason,
        array $attributes,
        array $metadata = [],
    ): KmPengajuan {
        return $this->perform(
            $document,
            $actor,
            KmApprovalAction::REJECTED,
            fn (KmPengajuan $locked): KmPengajuan => $this->workflow->reject($locked),
            trim($reason),
            $attributes,
            $metadata,
        );
    }

    public function deactivate(
        KmPengajuan $document,
        User $actor,
        array $metadata = [],
    ): KmPengajuan {
        return $this->perform(
            $document,
            $actor,
            KmApprovalAction::DEACTIVATED,
            fn (KmPengajuan $locked): KmPengajuan => $this->workflow->deactivate($locked),
            null,
            [],
            $metadata,
        );
    }

    /**
     * @param  list<array{document_id: int, id_km_kategori?: int|null}>  $items
     * @param  array<string, mixed>  $metadata
     * @return Collection<int, KmPengajuan>
     */
    public function bulkAct(
        User $actor,
        array $items,
        KmApprovalAction $action,
        ?string $reason = null,
        array $metadata = [],
    ): Collection {
        if (! in_array($action, [KmApprovalAction::APPROVED, KmApprovalAction::REJECTED], true)) {
            throw new KmBulkApprovalConflictException('Aksi bulk approval tidak didukung.');
        }

        $itemMap = collect($items)
            ->keyBy(fn (array $item): int => (int) $item['document_id']);
        $ids = $itemMap->keys()->map(fn (mixed $id): int => (int) $id)->sort()->values()->all();

        if ($ids === [] || count($ids) !== count($items)) {
            throw new KmBulkApprovalConflictException('Daftar dokumen bulk approval kosong atau mengandung duplikat.');
        }

        $trimmedReason = $reason === null ? null : trim($reason);
        if ($action === KmApprovalAction::REJECTED && $trimmedReason === '') {
            throw new KmBulkApprovalConflictException('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use (
            $actor,
            $action,
            $trimmedReason,
            $metadata,
            $itemMap,
            $ids,
        ): Collection {
            /** @var EloquentCollection<int, KmPengajuan> $documents */
            $documents = KmPengajuan::query()
                ->whereKey($ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($documents->count() !== count($ids)) {
                throw new KmBulkApprovalConflictException(
                    'Satu atau lebih dokumen tidak ditemukan. Tidak ada perubahan yang disimpan.'
                );
            }

            $target = $this->targetFor($action);
            $ability = $action === KmApprovalAction::APPROVED ? 'approve' : 'reject';

            $providedCategoryIds = $action === KmApprovalAction::APPROVED
                ? $itemMap->pluck('id_km_kategori')->filter()
                : collect();
            $categoryIds = $providedCategoryIds
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values();
            if ($action === KmApprovalAction::APPROVED
                && ($providedCategoryIds->count() !== $itemMap->count()
                    || KmKategori::query()->whereKey($categoryIds)->count() !== $categoryIds->count())) {
                throw new KmBulkApprovalConflictException(
                    'Kategori setiap dokumen wajib tersedia dan valid. Tidak ada perubahan yang disimpan.'
                );
            }

            foreach ($documents as $document) {
                $this->workflow->assertCanTransition($document, $target);
                Gate::forUser($actor)->authorize($ability, $document);
            }

            return $documents->map(function (KmPengajuan $document) use (
                $actor,
                $action,
                $trimmedReason,
                $metadata,
                $itemMap,
            ): KmPengajuan {
                $attributes = $action === KmApprovalAction::APPROVED
                    ? ['id_km_kategori' => (int) $itemMap->get($document->getKey())['id_km_kategori']]
                    : [];

                return $this->applyLockedAction(
                    $document,
                    $actor,
                    $action,
                    $trimmedReason,
                    $attributes,
                    $metadata,
                );
            })->values();
        }, 3);
    }

    private function perform(
        KmPengajuan $document,
        User $actor,
        KmApprovalAction $action,
        callable $transition,
        ?string $reason,
        array $attributes,
        array $metadata,
    ): KmPengajuan {
        return DB::transaction(function () use (
            $document,
            $actor,
            $action,
            $transition,
            $reason,
            $attributes,
            $metadata,
        ): KmPengajuan {
            $locked = KmPengajuan::query()
                ->whereKey($document->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            return $this->applyLockedAction(
                $locked,
                $actor,
                $action,
                $reason,
                $attributes,
                $metadata,
                $transition,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $metadata
     */
    private function applyLockedAction(
        KmPengajuan $locked,
        User $actor,
        KmApprovalAction $action,
        ?string $reason,
        array $attributes,
        array $metadata,
        ?callable $transition = null,
    ): KmPengajuan {
        $from = $locked->documentStatus();

        $organizationTargetsSubmitted = (bool) ($attributes['organization_targets_submitted'] ?? false);
        $departmentTargets = collect($attributes['target_department_ids'] ?? [])->map('intval')->all();
        $positionTargets = collect($attributes['target_job_position_ids'] ?? [])->map('intval')->all();
        unset(
            $attributes['target_department_ids'],
            $attributes['target_job_position_ids'],
            $attributes['organization_targets_submitted'],
        );

        if ($attributes !== []) {
            $locked->fill($attributes)->save();
        }

        $version = $this->versions->prepareApprovalAction($locked, $actor, $action);
        if ($action === KmApprovalAction::APPROVED && $version !== null
            && $organizationTargetsSubmitted
            && Schema::hasTable('km_document_version_departments')) {
            $this->targeting->sync($version, $departmentTargets, $positionTargets);
        }

        $updated = $transition === null
            ? $this->workflow->transitionLocked($locked, $this->targetFor($action))
            : $transition($locked);
        $to = $updated->documentStatus();

        $eventAttributes = [
            'km_pengajuan_id' => $updated->getKey(),
            'actor_id' => $actor->getKey(),
            'actor_name' => $actor->name,
            'actor_role_snapshot' => $actor->roles?->role ?? (string) $actor->role_id,
            'action' => $action,
            'from_status' => $from?->value,
            'to_status' => ($to ?? KmDocumentStatus::INACTIVE)->value,
            'reason' => $reason,
            'metadata' => $metadata === [] ? null : $metadata,
            'acted_at' => now(),
        ];
        if (Schema::hasColumn('km_approval_events', 'document_version_id')) {
            $eventAttributes['document_version_id'] = $version?->getKey();
        }
        $event = KmApprovalEvent::query()->create($eventAttributes);

        $ownerId = (int) $updated->id_user;
        $notificationData = [
            'document_id' => $updated->getKey(),
            'document_version_id' => $version?->getKey(),
            'title' => $updated->judul,
        ];
        if ($action === KmApprovalAction::SUBMITTED) {
            $this->notifications->recordMany(
                $this->access->eligibleApproverIds(),
                'document_submitted',
                'submitted:'.$updated->getKey().':'.$event->getKey(),
                $notificationData,
            );
        } elseif ($action === KmApprovalAction::APPROVED && $ownerId > 0) {
            $this->ledger->award(
                $ownerId,
                'published_document',
                'published:'.($version?->getKey() ?? $updated->getKey()).':'.$ownerId,
                max(0, (int) config('knowledge_management.points.published_document', 25)),
                (int) $updated->getKey(),
                null,
                null,
                $actor,
                $version?->getKey(),
            );
            $this->notifications->record(
                $ownerId,
                'document_approved',
                'approved:'.$updated->getKey().':'.$event->getKey().':u'.$ownerId,
                $notificationData,
            );
            if ($version !== null) {
                $this->publicationNotifications->queue($version);
            }
            $this->gamification->awardEligible($ownerId);
        } elseif ($action === KmApprovalAction::REJECTED && $ownerId > 0) {
            $this->notifications->record(
                $ownerId,
                'document_rejected',
                'rejected:'.$updated->getKey().':'.$event->getKey().':u'.$ownerId,
                [...$notificationData, 'reason' => $reason],
            );
        }

        return $updated;
    }

    public function workingDaysSince(
        CarbonInterface $submittedAt,
        ?CarbonInterface $until = null,
    ): int {
        $cursor = CarbonImmutable::instance($submittedAt)->startOfDay()->addDay();
        $end = CarbonImmutable::instance($until ?? now())->startOfDay();
        $days = 0;

        while ($cursor->lte($end)) {
            if ($cursor->isWeekday()) {
                $days++;
            }
            $cursor = $cursor->addDay();
        }

        return $days;
    }

    public function hasReminderCandidates(): bool
    {
        return KmPengajuan::query()
            ->where('status', KmDocumentStatus::PENDING_APPROVAL->value)
            ->whereHas('approvalEvents', static fn ($query) => $query
                ->where('action', KmApprovalAction::SUBMITTED->value)
                ->where('acted_at', '<=', now()->subDays(2)))
            ->exists();
    }

    /**
     * @return array{documents: int, notification_attempts: int}
     */
    public function generateDueReminders(): array
    {
        $reminderAt = max(1, (int) config('knowledge_management.approval_sla.reminder_working_days', 2));
        $dueAt = max($reminderAt, (int) config('knowledge_management.approval_sla.due_working_days', 3));
        $approverIds = $this->access->eligibleApproverIds();
        if ($approverIds === []) {
            return ['documents' => 0, 'notification_attempts' => 0];
        }

        $documents = KmPengajuan::query()
            ->select('km_pengajuans.*')
            ->where('status', KmDocumentStatus::PENDING_APPROVAL->value)
            ->addSelect([
                'submit_event_id' => KmApprovalEvent::query()
                    ->select('id')
                    ->whereColumn('km_pengajuan_id', 'km_pengajuans.id')
                    ->where('action', KmApprovalAction::SUBMITTED->value)
                    ->orderByDesc('acted_at')
                    ->orderByDesc('id')
                    ->limit(1),
                'pending_since' => KmApprovalEvent::query()
                    ->select('acted_at')
                    ->whereColumn('km_pengajuan_id', 'km_pengajuans.id')
                    ->where('action', KmApprovalAction::SUBMITTED->value)
                    ->orderByDesc('acted_at')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->lazyById(200, 'km_pengajuans.id', 'id');
        $documentCount = 0;
        $attempts = 0;

        foreach ($documents as $document) {
            if ($document->pending_since === null || $document->submit_event_id === null) {
                continue;
            }
            $workingDays = $this->workingDaysSince(CarbonImmutable::parse($document->pending_since));
            if ($workingDays < $reminderAt) {
                continue;
            }

            $documentCount++;
            $data = ['document_id' => $document->getKey(), 'title' => $document->judul];
            $prefix = 'approval_reminder:'.$document->getKey().':'.$document->submit_event_id;
            $this->notifications->recordMany($approverIds, 'approval_reminder', $prefix, $data);
            $attempts += count($approverIds);

            if ($workingDays >= $dueAt) {
                $prefix = 'approval_overdue:'.$document->getKey().':'.$document->submit_event_id;
                $this->notifications->recordMany($approverIds, 'approval_overdue', $prefix, $data);
                $attempts += count($approverIds);
            }
        }

        return ['documents' => $documentCount, 'notification_attempts' => $attempts];
    }

    private function targetFor(KmApprovalAction $action): KmDocumentStatus
    {
        return match ($action) {
            KmApprovalAction::SUBMITTED => KmDocumentStatus::PENDING_APPROVAL,
            KmApprovalAction::APPROVED => KmDocumentStatus::PUBLISHED,
            KmApprovalAction::REJECTED => KmDocumentStatus::DRAFT,
            KmApprovalAction::DEACTIVATED => KmDocumentStatus::INACTIVE,
        };
    }
}
