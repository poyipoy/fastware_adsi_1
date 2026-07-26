<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmApprovalAction;
use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Exceptions\KnowledgeManagement\KmBulkApprovalConflictException;
use App\Models\KmApprovalEvent;
use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class KmApprovalService
{
    public function __construct(
        private readonly KmDocumentWorkflowService $workflow,
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

        if ($attributes !== []) {
            $locked->fill($attributes)->save();
        }

        $updated = $transition === null
            ? $this->workflow->transitionLocked($locked, $this->targetFor($action))
            : $transition($locked);
        $to = $updated->documentStatus();

        KmApprovalEvent::query()->create([
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
        ]);

        return $updated;
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
