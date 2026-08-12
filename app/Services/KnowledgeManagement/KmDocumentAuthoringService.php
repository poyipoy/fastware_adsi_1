<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Exceptions\KnowledgeManagement\KmAutosaveConflictException;
use App\Models\KmPengajuan;
use App\Models\KmTag;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class KmDocumentAuthoringService
{
    public function __construct(
        private readonly KmFileService $files,
        private readonly KmVersioningService $versions,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createDraft(User $owner, array $payload, UploadedFile $file): KmPengajuan
    {
        $storedPath = null;

        try {
            $document = DB::transaction(function () use ($owner, $payload, $file, &$storedPath): KmPengajuan {
                $document = KmPengajuan::query()->create([
                    'id_user' => $owner->getKey(),
                    'judul' => (string) $payload['judul'],
                    'keterangan' => (string) $payload['keterangan'],
                    'persetujuan' => KmDocumentStatus::DRAFT->legacyApprovalValue(),
                    'status' => KmDocumentStatus::DRAFT->value,
                ]);

                $metadata = $this->files->storeUploadedDocument($file, $document);
                $storedPath = (string) $metadata['file_path'];
                $document->forceFill($metadata)->save();

                $document = $this->synchronizeMetadataLocked($document, $payload);
                $this->versions->initializeDraft($document, $owner);

                return $document->refresh();
            });
        } catch (Throwable $exception) {
            $this->files->discardStoredPath($storedPath);

            throw $exception;
        }

        return $document;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateDraft(
        User $actor,
        int $documentId,
        array $payload,
        ?UploadedFile $file = null,
    ): KmPengajuan {
        $storedPath = null;

        try {
            $document = DB::transaction(function () use (
                $actor,
                $documentId,
                $payload,
                $file,
                &$storedPath,
            ): KmPengajuan {
                $document = KmPengajuan::query()
                    ->whereKey($documentId)
                    ->lockForUpdate()
                    ->firstOrFail();
                Gate::forUser($actor)->authorize('update', $document);

                if ($document->documentStatus() === KmDocumentStatus::PUBLISHED
                    && $document->hasEditableDraftVersion()) {
                    $metadata = null;
                    if ($file !== null) {
                        $metadata = $this->files->storeUploadedDocument($file, $document);
                        $storedPath = (string) $metadata['file_path'];
                    }
                    $this->versions->updateMajorRevisionDraft(
                        $document,
                        $actor,
                        $payload,
                        $metadata,
                    );

                    return $document->refresh();
                }

                $attributes = [
                    'judul' => (string) $payload['judul'],
                    'keterangan' => (string) $payload['keterangan'],
                ];

                if ($file !== null) {
                    $metadata = $this->files->storeUploadedDocument($file, $document);
                    $storedPath = (string) $metadata['file_path'];
                    $attributes = [...$attributes, ...$metadata];
                }

                $document->forceFill($attributes)->save();

                $document = $this->synchronizeMetadataLocked($document, $payload, true);
                $this->versions->synchronizeDraft($document, $actor);

                return $document->refresh();
            });
        } catch (Throwable $exception) {
            $this->files->discardStoredPath($storedPath);

            throw $exception;
        }

        return $document;
    }

    public function synchronizeMetadata(
        KmPengajuan $document,
        array $payload,
        bool $incrementRevision = false,
    ): KmPengajuan {
        return DB::transaction(fn (): KmPengajuan => $this->synchronizeMetadataLocked(
            $document,
            $payload,
            $incrementRevision,
        ));
    }

    public function autosave(KmPengajuan $document, User $owner, array $payload): array
    {
        $clientRevision = (int) $payload['revision'];

        return DB::transaction(function () use ($document, $owner, $payload, $clientRevision): array {
            if ($document->documentStatus() === KmDocumentStatus::PUBLISHED
                && $document->hasEditableDraftVersion()) {
                if ((int) $document->id_user !== (int) $owner->getKey()
                    || (int) $document->draft_revision !== $clientRevision) {
                    $this->throwAutosaveFailure($document, $owner);
                }
                $this->versions->updateMajorRevisionDraft($document, $owner, $payload);
                $fresh = $document->refresh();

                return [
                    'draft_revision' => (int) $fresh->draft_revision,
                    'autosaved_at' => $fresh->autosaved_at?->toIso8601String() ?? now()->toIso8601String(),
                ];
            }

            $affected = DB::table('km_pengajuans')
                ->where('id', $document->getKey())
                ->where('id_user', $owner->getKey())
                ->where('status', KmDocumentStatus::DRAFT->value)
                ->where('draft_revision', $clientRevision)
                ->update($this->autosaveAttributes($payload));

            if ($affected === 0) {
                $this->throwAutosaveFailure($document, $owner);
            }

            $fresh = KmPengajuan::query()->findOrFail($document->getKey());
            if (array_key_exists('tags', $payload)) {
                $this->syncTags($fresh, $payload['tags']);
            }
            if (array_key_exists('co_author_ids', $payload)) {
                $this->syncCoAuthors($fresh, $payload['co_author_ids']);
            }

            return [
                'draft_revision' => (int) $fresh->draft_revision,
                'autosaved_at' => $fresh->autosaved_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        });
    }

    private function autosaveAttributes(array $payload): array
    {
        $attributes = [
            'draft_revision' => DB::raw('draft_revision + 1'),
            'autosaved_at' => now(),
            'updated_at' => now(),
        ];

        foreach (['judul', 'keterangan'] as $field) {
            if (array_key_exists($field, $payload)) {
                $attributes[$field] = (string) $payload[$field];
            }
        }

        if (array_key_exists('reading_minutes', $payload)) {
            $attributes['reading_minutes'] = $payload['reading_minutes'] === null
                ? null
                : (int) $payload['reading_minutes'];
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function synchronizeMetadataLocked(
        KmPengajuan $document,
        array $payload,
        bool $incrementRevision = false,
    ): KmPengajuan {
        if (array_key_exists('reading_minutes', $payload)) {
            $document->reading_minutes = $payload['reading_minutes'] === null
                ? null
                : (int) $payload['reading_minutes'];
        }

        if ($incrementRevision) {
            $document->draft_revision = (int) $document->draft_revision + 1;
        }

        if ($document->isDirty()) {
            $document->save();
        }

        $this->syncTags($document, $payload['tags'] ?? []);
        $this->syncCoAuthors($document, $payload['co_author_ids'] ?? []);

        return $document->refresh();
    }

    private function throwAutosaveFailure(KmPengajuan $document, User $owner): never
    {
        $fresh = KmPengajuan::query()->findOrFail($document->getKey());

        if ((int) $fresh->id_user !== (int) $owner->getKey()) {
            throw new AuthorizationException('Draft ini bukan milik Anda.');
        }

        if ($fresh->documentStatus() !== KmDocumentStatus::DRAFT
            && ! $fresh->hasEditableDraftVersion()) {
            throw ValidationException::withMessages([
                'document' => 'Hanya dokumen berstatus Draf yang dapat di-autosave.',
            ]);
        }

        throw new KmAutosaveConflictException(
            (int) $fresh->draft_revision,
            $fresh->autosaved_at?->toIso8601String() ?? '',
        );
    }

    private function syncTags(KmPengajuan $document, array $tagNames): void
    {
        $tagIds = collect(KmDocumentAuthoringRules::normalizeTags($tagNames))
            ->map(function (string $name): int {
                $slug = Str::lower(Str::slug($name));

                return (int) KmTag::query()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name],
                )->getKey();
            })
            ->values()
            ->all();

        $document->tags()->sync($tagIds);
    }

    private function syncCoAuthors(KmPengajuan $document, array $coAuthorIds): void
    {
        $ids = array_map('intval', array_values($coAuthorIds));
        $validIds = User::query()
            ->whereIn('id', $ids)
            ->where('is_active', false)
            ->whereKeyNot((int) $document->id_user)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $expectedIds = collect($ids)->unique()->sort()->values()->all();
        if ($validIds !== $expectedIds) {
            throw ValidationException::withMessages([
                'co_author_ids' => 'Co-author harus aktif, unik, dan berbeda dari pemilik dokumen.',
            ]);
        }

        $document->coAuthors()->sync($validIds);
    }
}
