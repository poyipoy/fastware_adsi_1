<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmApprovalAction;
use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmProcessingStatus;
use App\Enums\KnowledgeManagement\KmVersionChangeType;
use App\Enums\KnowledgeManagement\KmVersionStatus;
use App\Models\KmDocumentVersion;
use App\Models\KmPengajuan;
use App\Models\KmTag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KmVersioningService
{
    private ?bool $ready = null;

    public function schemaReady(): bool
    {
        return $this->ready ??= Schema::hasTable('km_document_versions')
            && Schema::hasColumn('km_pengajuans', 'current_version_id');
    }

    public function initializeDraft(KmPengajuan $document, User $actor): ?KmDocumentVersion
    {
        if (! $this->schemaReady()) {
            return null;
        }

        $locked = KmPengajuan::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();
        if ($locked->current_version_id !== null) {
            return KmDocumentVersion::query()->find($locked->current_version_id);
        }

        $version = KmDocumentVersion::query()->create([
            ...$this->snapshotFromDocument($locked),
            'version_major' => 1,
            'version_minor' => 0,
            'change_type' => KmVersionChangeType::MAJOR,
            'change_note' => 'Versi awal dokumen.',
            'version_status' => KmVersionStatus::DRAFT,
            'created_by' => $actor->getKey(),
        ]);
        $this->copyLegacyPivots($locked, $version);
        $locked->forceFill(['current_version_id' => $version->getKey()])->save();

        return $version;
    }

    public function synchronizeDraft(KmPengajuan $document, User $actor): ?KmDocumentVersion
    {
        if (! $this->schemaReady()) {
            return null;
        }

        $version = $this->initializeDraft($document, $actor);
        if ($version === null) {
            return null;
        }
        $version = KmDocumentVersion::query()->whereKey($version->getKey())->lockForUpdate()->firstOrFail();
        if ($version->version_status !== KmVersionStatus::DRAFT) {
            throw ValidationException::withMessages([
                'document' => 'Hanya versi draf yang dapat diperbarui.',
            ]);
        }

        $version->forceFill($this->snapshotFromDocument($document->refresh()))->save();
        $this->copyLegacyPivots($document, $version);

        return $version->refresh();
    }

    public function createMajorRevision(
        KmPengajuan $document,
        User $actor,
        string $changeNote,
    ): KmDocumentVersion {
        if (! $this->schemaReady()) {
            throw ValidationException::withMessages([
                'document' => 'Schema versioning KM belum tersedia.',
            ]);
        }

        return DB::transaction(function () use ($document, $actor, $changeNote): KmDocumentVersion {
            $locked = KmPengajuan::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->documentStatus() !== KmDocumentStatus::PUBLISHED
                || $locked->published_version_id === null) {
                throw ValidationException::withMessages([
                    'document' => 'Revisi major hanya dapat dibuat dari dokumen yang sedang dipublikasikan.',
                ]);
            }
            $existingDraft = KmDocumentVersion::query()
                ->where('km_pengajuan_id', $locked->getKey())
                ->where('version_status', KmVersionStatus::DRAFT->value)
                ->lockForUpdate()
                ->first();
            if ($existingDraft !== null) {
                throw ValidationException::withMessages([
                    'document' => 'Dokumen sudah memiliki revisi draf yang belum diselesaikan.',
                ]);
            }

            $source = KmDocumentVersion::query()
                ->whereKey($locked->published_version_id)
                ->lockForUpdate()
                ->firstOrFail();
            $nextMajor = (int) KmDocumentVersion::query()
                ->where('km_pengajuan_id', $locked->getKey())
                ->max('version_major') + 1;
            $version = KmDocumentVersion::query()->create([
                ...$source->only([
                    'title', 'synopsis', 'category_id', 'audience', 'reading_minutes',
                    'original_disk', 'original_path', 'original_name', 'original_mime_type',
                    'original_size_bytes', 'original_checksum_sha256',
                    'normalized_pdf_disk', 'normalized_pdf_path', 'normalized_pdf_size_bytes',
                    'normalized_pdf_checksum_sha256', 'page_count', 'extracted_text',
                    'processing_status', 'antivirus_status', 'processed_at',
                ]),
                'km_pengajuan_id' => $locked->getKey(),
                'version_major' => max(1, $nextMajor),
                'version_minor' => 0,
                'change_type' => KmVersionChangeType::MAJOR,
                'change_note' => trim($changeNote),
                'version_status' => KmVersionStatus::DRAFT,
                'processing_attempts' => 0,
                'last_error' => null,
                'next_attempt_at' => null,
                'created_by' => $actor->getKey(),
            ]);
            $version->tags()->sync($source->tags()->pluck('km_tags.id')->all());
            $version->coAuthors()->sync($source->coAuthors()->pluck('users.id')->all());
            $this->copyOrganizationTargets($source, $version);
            $locked->forceFill(['current_version_id' => $version->getKey()])->save();

            return $version->refresh();
        }, 3);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed>|null $fileMetadata
     */
    public function updateMajorRevisionDraft(
        KmPengajuan $document,
        User $actor,
        array $payload,
        ?array $fileMetadata = null,
    ): KmDocumentVersion {
        if (! $this->schemaReady() || $document->current_version_id === null) {
            throw ValidationException::withMessages(['document' => 'Revisi draf tidak tersedia.']);
        }

        $version = KmDocumentVersion::query()
            ->whereKey($document->current_version_id)
            ->where('km_pengajuan_id', $document->getKey())
            ->lockForUpdate()
            ->firstOrFail();
        if ($version->version_status !== KmVersionStatus::DRAFT) {
            throw ValidationException::withMessages(['document' => 'Versi aktif bukan revisi draf.']);
        }

        $attributes = [
            'title' => (string) ($payload['judul'] ?? $version->title),
            'synopsis' => (string) ($payload['keterangan'] ?? $version->synopsis),
            'reading_minutes' => array_key_exists('reading_minutes', $payload)
                ? ($payload['reading_minutes'] === null ? null : (int) $payload['reading_minutes'])
                : $version->reading_minutes,
        ];
        if ($fileMetadata !== null) {
            $extension = strtolower(pathinfo((string) $fileMetadata['file_path'], PATHINFO_EXTENSION));
            $ready = $extension === 'pdf'
                && ! (bool) config('knowledge_management.processing.enabled', false);
            $attributes = [
                ...$attributes,
                'original_disk' => $fileMetadata['file_disk'],
                'original_path' => $fileMetadata['file_path'],
                'original_name' => $fileMetadata['file_original_name'],
                'original_mime_type' => $fileMetadata['file_mime_type'],
                'original_size_bytes' => $fileMetadata['file_size_bytes'],
                'original_checksum_sha256' => $fileMetadata['file_checksum_sha256'],
                'normalized_pdf_disk' => $ready ? $fileMetadata['file_disk'] : null,
                'normalized_pdf_path' => $ready ? $fileMetadata['file_path'] : null,
                'normalized_pdf_size_bytes' => $ready ? $fileMetadata['file_size_bytes'] : null,
                'normalized_pdf_checksum_sha256' => $ready ? $fileMetadata['file_checksum_sha256'] : null,
                'page_count' => null,
                'extracted_text' => null,
                'processing_status' => $ready ? KmProcessingStatus::READY : KmProcessingStatus::PENDING,
                'antivirus_status' => $ready ? 'legacy_unscanned' : 'pending',
                'processing_attempts' => 0,
                'last_error' => null,
                'next_attempt_at' => null,
                'processed_at' => $ready ? now() : null,
            ];
        }
        $version->forceFill($attributes)->save();

        if (array_key_exists('tags', $payload)) {
            $tagIds = collect(KmDocumentAuthoringRules::normalizeTags($payload['tags']))
                ->map(static function (string $name): int {
                    $slug = Str::lower(Str::slug($name));

                    return (int) KmTag::query()->firstOrCreate(
                        ['slug' => $slug],
                        ['name' => $name],
                    )->getKey();
                })->all();
            $version->tags()->sync($tagIds);
        }
        if (array_key_exists('co_author_ids', $payload)) {
            $requested = collect($payload['co_author_ids'])->map('intval')->unique()->sort()->values();
            $valid = User::query()
                ->whereIn('id', $requested)
                ->where('is_active', false)
                ->whereKeyNot((int) $document->id_user)
                ->pluck('id')->map('intval')->sort()->values();
            if ($requested->all() !== $valid->all()) {
                throw ValidationException::withMessages([
                    'co_author_ids' => 'Co-author harus aktif, unik, dan berbeda dari pemilik dokumen.',
                ]);
            }
            $version->coAuthors()->sync($valid->all());
        }

        $document->forceFill([
            'draft_revision' => (int) $document->draft_revision + 1,
            'autosaved_at' => now(),
        ])->save();

        return $version->refresh();
    }

    /** @param array{tags?: list<string>} $metadata */
    public function createMinorRevision(
        KmPengajuan $document,
        User $actor,
        string $changeNote,
        array $metadata = [],
    ): KmDocumentVersion {
        if (! $this->schemaReady()) {
            throw ValidationException::withMessages(['document' => 'Schema versioning KM belum tersedia.']);
        }

        return DB::transaction(function () use ($document, $actor, $changeNote, $metadata): KmDocumentVersion {
            $locked = KmPengajuan::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();
            $source = KmDocumentVersion::query()
                ->whereKey($locked->published_version_id)
                ->lockForUpdate()
                ->firstOrFail();
            $nextMinor = (int) KmDocumentVersion::query()
                ->where('km_pengajuan_id', $locked->getKey())
                ->where('version_major', $source->version_major)
                ->max('version_minor') + 1;
            $source->forceFill([
                'version_status' => KmVersionStatus::WITHDRAWN,
                'withdrawn_at' => now(),
            ])->save();

            $version = KmDocumentVersion::query()->create([
                ...$source->only([
                    'title', 'synopsis', 'category_id', 'audience', 'reading_minutes',
                    'original_disk', 'original_path', 'original_name', 'original_mime_type',
                    'original_size_bytes', 'original_checksum_sha256',
                    'normalized_pdf_disk', 'normalized_pdf_path', 'normalized_pdf_size_bytes',
                    'normalized_pdf_checksum_sha256', 'page_count', 'extracted_text',
                    'processing_status', 'antivirus_status', 'processed_at',
                ]),
                'km_pengajuan_id' => $locked->getKey(),
                'version_major' => (int) $source->version_major,
                'version_minor' => max(1, $nextMinor),
                'change_type' => KmVersionChangeType::MINOR,
                'change_note' => trim($changeNote),
                'version_status' => KmVersionStatus::PUBLISHED,
                'processing_attempts' => 0,
                'created_by' => $actor->getKey(),
                'approved_by' => $actor->getKey(),
                'published_at' => now(),
            ]);
            $version->tags()->sync($source->tags()->pluck('km_tags.id')->all());
            $version->coAuthors()->sync($source->coAuthors()->pluck('users.id')->all());
            $this->copyOrganizationTargets($source, $version);
            if (array_key_exists('tag_ids', $metadata)) {
                $version->tags()->sync(array_map('intval', $metadata['tag_ids']));
            }
            $locked->tags()->sync($version->tags()->pluck('km_tags.id')->all());
            $locked->forceFill([
                'current_version_id' => $version->getKey(),
                'published_version_id' => $version->getKey(),
            ])->save();

            return $version->refresh();
        }, 3);
    }

    public function prepareApprovalAction(
        KmPengajuan $document,
        User $actor,
        KmApprovalAction $action,
    ): ?KmDocumentVersion {
        if (! $this->schemaReady() || $document->current_version_id === null) {
            return null;
        }

        $version = KmDocumentVersion::query()
            ->whereKey($document->current_version_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($action === KmApprovalAction::SUBMITTED) {
            if (! $version->isReady()) {
                throw ValidationException::withMessages([
                    'file' => 'Versi dokumen masih menunggu pemrosesan dan belum dapat dikirim.',
                ]);
            }
            if ($document->published_version_id !== null
                && (int) $document->published_version_id !== (int) $version->getKey()) {
                KmDocumentVersion::query()->whereKey($document->published_version_id)->update([
                    'version_status' => KmVersionStatus::WITHDRAWN->value,
                    'withdrawn_at' => now(),
                ]);
                $document->published_version_id = null;
            }
            $this->projectVersionToDocument($version, $document);
            $document->tags()->sync($version->tags()->pluck('km_tags.id')->all());
            $document->coAuthors()->sync($version->coAuthors()->pluck('users.id')->all());
            $version->forceFill(['version_status' => KmVersionStatus::PENDING_APPROVAL])->save();
        } elseif ($action === KmApprovalAction::APPROVED) {
            $this->synchronizeVersionFromLegacyProjection($version, $document);
            $version->forceFill([
                'version_status' => KmVersionStatus::PUBLISHED,
                'approved_by' => $actor->getKey(),
                'published_at' => now(),
                'withdrawn_at' => null,
            ])->save();
            $document->published_version_id = $version->getKey();
        } elseif ($action === KmApprovalAction::REJECTED) {
            $version->forceFill(['version_status' => KmVersionStatus::DRAFT])->save();
        } elseif ($action === KmApprovalAction::DEACTIVATED) {
            $version->forceFill([
                'version_status' => KmVersionStatus::WITHDRAWN,
                'withdrawn_at' => now(),
            ])->save();
            $document->published_version_id = null;
        }

        if ($document->isDirty()) {
            $document->save();
        }

        return $version->refresh();
    }

    public function versionIdForReading(KmPengajuan $document): ?int
    {
        if (! $this->schemaReady()) {
            return null;
        }

        $id = $document->published_version_id ?? $document->current_version_id;

        return $id === null ? null : (int) $id;
    }

    /** @return array<string, mixed> */
    private function snapshotFromDocument(KmPengajuan $document): array
    {
        $extension = strtolower(pathinfo((string) $document->file_path, PATHINFO_EXTENSION));
        $isPdf = $extension === 'pdf' && $document->hasCompletePrivateFileMetadata();
        $processingEnabled = (bool) config('knowledge_management.processing.enabled', false);
        $ready = $isPdf && ! $processingEnabled;

        return [
            'km_pengajuan_id' => $document->getKey(),
            'title' => (string) $document->judul,
            'synopsis' => $document->keterangan,
            'category_id' => $document->id_km_kategori,
            'audience' => $document->posisi,
            'reading_minutes' => $document->reading_minutes,
            'original_disk' => $document->file_disk,
            'original_path' => $document->file_path,
            'original_name' => $document->file_original_name ?: $document->file_name,
            'original_mime_type' => $document->file_mime_type,
            'original_size_bytes' => $document->file_size_bytes,
            'original_checksum_sha256' => $document->file_checksum_sha256,
            'normalized_pdf_disk' => $ready ? $document->file_disk : null,
            'normalized_pdf_path' => $ready ? $document->file_path : null,
            'normalized_pdf_size_bytes' => $ready ? $document->file_size_bytes : null,
            'normalized_pdf_checksum_sha256' => $ready ? $document->file_checksum_sha256 : null,
            'processing_status' => $ready
                ? KmProcessingStatus::READY
                : KmProcessingStatus::PENDING,
            'antivirus_status' => $ready ? 'legacy_unscanned' : 'pending',
            'processing_attempts' => 0,
            'last_error' => null,
            'next_attempt_at' => null,
            'processed_at' => $ready ? now() : null,
        ];
    }

    private function copyLegacyPivots(KmPengajuan $document, KmDocumentVersion $version): void
    {
        $version->tags()->sync($document->tags()->pluck('km_tags.id')->all());
        $version->coAuthors()->sync($document->coAuthors()->pluck('users.id')->all());
    }

    private function copyOrganizationTargets(
        KmDocumentVersion $source,
        KmDocumentVersion $target,
    ): void {
        if (! Schema::hasTable('km_document_version_departments')) {
            return;
        }

        $target->targetDepartments()->sync(
            $source->targetDepartments()->pluck('mst_departments.id')->all(),
        );
        $target->targetJobPositions()->sync(
            $source->targetJobPositions()->pluck('mst_job_positions.id')->all(),
        );
    }

    private function projectVersionToDocument(
        KmDocumentVersion $version,
        KmPengajuan $document,
    ): void {
        $document->forceFill([
            'judul' => $version->title,
            'keterangan' => $version->synopsis,
            'id_km_kategori' => $version->category_id,
            'posisi' => $version->audience,
            'reading_minutes' => $version->reading_minutes,
            'file_disk' => $version->original_disk,
            'file_path' => $version->original_path,
            'file_original_name' => $version->original_name,
            'file_mime_type' => $version->original_mime_type,
            'file_size_bytes' => $version->original_size_bytes,
            'file_checksum_sha256' => $version->original_checksum_sha256,
        ]);
    }

    private function synchronizeVersionFromLegacyProjection(
        KmDocumentVersion $version,
        KmPengajuan $document,
    ): void {
        $version->forceFill([
            'title' => $document->judul,
            'synopsis' => $document->keterangan,
            'category_id' => $document->id_km_kategori,
            'audience' => $document->posisi,
            'reading_minutes' => $document->reading_minutes,
        ])->save();
    }
}
