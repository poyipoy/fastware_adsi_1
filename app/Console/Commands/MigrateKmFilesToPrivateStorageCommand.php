<?php

namespace App\Console\Commands;

use App\Models\KmPengajuan;
use App\Services\KnowledgeManagement\KmFileService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MigrateKmFilesToPrivateStorageCommand extends Command
{
    private const MANIFEST_VERSION = 2;

    /**
     * @var list<string>
     */
    private const FILE_METADATA_FIELDS = [
        'file_disk',
        'file_path',
        'file_original_name',
        'file_mime_type',
        'file_size_bytes',
        'file_checksum_sha256',
        'file_migrated_at',
    ];

    protected $signature = 'km:migrate-private-files
        {--dry-run : Laporkan kandidat tanpa memindahkan file}
        {--limit=100 : Jumlah maksimum dokumen per batch}
        {--restore-manifest= : Pulihkan metadata dan file public dari manifest}';

    protected $description = 'Memigrasikan dokumen KM legacy ke private storage secara idempotent';

    public function handle(): int
    {
        $restoreManifest = $this->option('restore-manifest');
        if (is_string($restoreManifest) && $restoreManifest !== '') {
            return $this->restore($restoreManifest);
        }

        $limit = max(1, min(1000, (int) $this->option('limit')));
        $documents = $this->migrationCandidates($limit, $this->recordedMigrationKeys());

        $this->info(sprintf('Ditemukan %d kandidat migrasi file KM.', $documents->count()));
        if ((bool) $this->option('dry-run')) {
            foreach ($documents as $document) {
                $this->line(sprintf('DRY-RUN document_id=%d file=%s', $document->id, basename((string) $document->file)));
            }

            return self::SUCCESS;
        }

        if ($documents->isEmpty()) {
            return self::SUCCESS;
        }

        $manifest = [
            'version' => self::MANIFEST_VERSION,
            'created_at' => now()->toIso8601String(),
            'database' => DB::getDatabaseName(),
            'entries' => [],
        ];
        $manifestPath = sprintf('file-migrations/%s.json', now()->format('Ymd_His_u'));
        $this->writeManifest($manifestPath, $manifest);
        $failed = false;

        foreach ($documents as $document) {
            try {
                $entry = $this->migrateDocument($document);
                $manifest['entries'][] = $entry;

                try {
                    $this->writeManifest($manifestPath, $manifest);
                } catch (Throwable $exception) {
                    $this->restoreEntry($entry, deleteDestinationAfterRestore: true);
                    array_pop($manifest['entries']);

                    throw new RuntimeException(
                        'Manifest gagal diperbarui; migrasi dokumen telah dikompensasi.',
                        previous: $exception,
                    );
                }

                $label = ($entry['recovered_after_commit'] ?? false) ? 'PASS recovery' : 'PASS';
                $this->info(sprintf('%s document_id=%d', $label, $document->id));
            } catch (Throwable $exception) {
                $failed = true;
                $this->error(sprintf('FAIL document_id=%d: %s', $document->id, $exception->getMessage()));
            }
        }

        $this->line('Manifest: '.Storage::disk(KmFileService::DISK)->path($manifestPath));

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function migrateDocument(KmPengajuan $document): array
    {
        $legacyName = (string) $document->file;
        if ($legacyName === '' || basename($legacyName) !== $legacyName) {
            throw new RuntimeException('Nama file legacy mengandung path yang tidak aman.');
        }

        $source = public_path('assets/image/'.$legacyName);
        if ($this->hasCommittedPrivateMetadata($document)) {
            return $this->finalizeCommittedDocument($document, $legacyName, $source);
        }

        if (! $this->hasUntouchedLegacyMetadata($document)) {
            throw new RuntimeException('Metadata file berada pada state parsial yang tidak dapat dimigrasikan otomatis.');
        }

        if (! File::isFile($source)) {
            throw new RuntimeException('File sumber legacy tidak ditemukan.');
        }

        $checksum = hash_file('sha256', $source);
        if ($checksum === false) {
            throw new RuntimeException('Checksum sumber tidak dapat dihitung.');
        }

        $extension = strtolower(pathinfo($legacyName, PATHINFO_EXTENSION));
        if (! in_array($extension, ['pdf', 'ppt', 'pptx'], true)) {
            throw new RuntimeException('Ekstensi file legacy tidak didukung.');
        }
        $mime = $this->validatedMime($source, $extension);

        $destination = sprintf(
            'documents/%d/%s.%s',
            $document->getKey(),
            Str::uuid()->toString(),
            $extension,
        );
        $backup = sprintf('legacy-backup/%d/%s', $document->getKey(), $legacyName);

        try {
            $stream = fopen($source, 'rb');
            if ($stream === false) {
                throw new RuntimeException('File sumber tidak dapat dibaca.');
            }

            try {
                $written = Storage::disk(KmFileService::DISK)
                    ->writeStream($destination, $stream, ['visibility' => 'private']);
            } finally {
                fclose($stream);
            }

            if (! $written) {
                throw new RuntimeException('File sumber gagal disalin ke private storage.');
            }

            $destinationPath = Storage::disk(KmFileService::DISK)->path($destination);
            $this->assertFileChecksum(
                $destinationPath,
                $checksum,
                'Private destination hilang atau checksum source dan destination berbeda.',
            );
        } catch (Throwable $exception) {
            try {
                $this->removePrivateFileOrFail($destination);
            } catch (Throwable $cleanupException) {
                throw new RuntimeException(
                    'Copy private gagal dan hasil parsial tidak dapat dibersihkan: '
                    .$cleanupException->getMessage(),
                    previous: $exception,
                );
            }

            throw $exception;
        }

        if (! Storage::disk(KmFileService::DISK)->makeDirectory(dirname($backup))) {
            $this->removePrivateFileOrFail($destination);
            throw new RuntimeException('Direktori backup private tidak dapat dibuat.');
        }

        $backupPath = Storage::disk(KmFileService::DISK)->path($backup);
        $backupExistedBefore = File::exists($backupPath);
        if ($backupExistedBefore && hash_file('sha256', $backupPath) !== $checksum) {
            $this->removePrivateFileOrFail($destination);
            throw new RuntimeException('Backup private dengan nama sama memiliki checksum berbeda.');
        }

        $oldMetadata = [];
        $newMetadata = [];
        $destinationSize = Storage::disk(KmFileService::DISK)->size($destination);

        try {
            DB::transaction(function () use (
                $document,
                $destination,
                $destinationPath,
                $backupPath,
                $source,
                $legacyName,
                $checksum,
                $mime,
                $destinationSize,
                &$oldMetadata,
                &$newMetadata,
            ): void {
                $locked = KmPengajuan::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();
                if (! $this->hasUntouchedLegacyMetadata($locked)) {
                    throw new RuntimeException('Row sudah dimigrasikan oleh proses lain.');
                }
                if ((string) $locked->file !== $legacyName) {
                    throw new RuntimeException('Nama file legacy berubah sebelum metadata diperbarui.');
                }

                $this->assertFileChecksum(
                    $destinationPath,
                    $checksum,
                    'Private destination berubah sebelum commit metadata.',
                );
                $this->assertFileChecksum(
                    $source,
                    $checksum,
                    'File sumber legacy berubah sebelum commit metadata.',
                );
                if (File::exists($backupPath)) {
                    $this->assertFileChecksum(
                        $backupPath,
                        $checksum,
                        'Backup private yang sudah ada berubah sebelum commit metadata.',
                    );
                }

                $oldMetadata = $this->rawFileMetadata($locked);
                $saved = $locked->forceFill([
                    'file_disk' => KmFileService::DISK,
                    'file_path' => $destination,
                    'file_original_name' => $locked->file_name ?: $legacyName,
                    'file_mime_type' => $mime,
                    'file_size_bytes' => $destinationSize,
                    'file_checksum_sha256' => $checksum,
                    'file_migrated_at' => now(),
                ])->save();

                if (! $saved) {
                    throw new RuntimeException('Metadata private file gagal disimpan.');
                }

                $newMetadata = $this->rawFileMetadata($locked);
            });
        } catch (Throwable $exception) {
            try {
                $this->removePrivateFileOrFail($destination);
            } catch (Throwable $cleanupException) {
                throw new RuntimeException(
                    'Update metadata gagal dan private destination tidak dapat dibersihkan: '
                    .$cleanupException->getMessage(),
                    previous: $exception,
                );
            }

            throw $exception;
        }

        try {
            $this->movePublicSourceToBackup(
                $source,
                $backupPath,
                $destinationPath,
                $checksum,
            );
        } catch (Throwable $exception) {
            try {
                $this->compensateCommittedMigration(
                    $document->getKey(),
                    $oldMetadata,
                    $newMetadata,
                    $backupPath,
                    $source,
                    $destination,
                    $destinationPath,
                    $checksum,
                    $backupExistedBefore,
                );
            } catch (Throwable $compensationException) {
                throw new RuntimeException(
                    'Finalisasi backup gagal dan kompensasi tidak tuntas: '
                    .$compensationException->getMessage(),
                    previous: $exception,
                );
            }

            throw $exception;
        }

        return [
            'document_id' => $document->getKey(),
            'source' => $source,
            'destination' => $destination,
            'backup' => $backup,
            'checksum_sha256' => $checksum,
            'old_metadata' => $oldMetadata,
            'new_metadata' => $newMetadata,
        ];
    }

    /**
     * Include both untouched legacy rows and crash-recovery rows whose private
     * metadata was committed while the verified public source still remains.
     * The lazy scan prevents loading every already-migrated document at once.
     *
     * @param  array<string, true>  $recordedKeys
     * @return Collection<int, KmPengajuan>
     */
    private function migrationCandidates(int $limit, array $recordedKeys): Collection
    {
        $documents = new Collection();

        $query = KmPengajuan::query()
            ->whereNotNull('file')
            ->where(function ($query): void {
                $query
                    ->whereNull('file_disk')
                    ->orWhereNull('file_path')
                    ->orWhere(function ($query): void {
                        $query
                            ->where('file_disk', KmFileService::DISK)
                            ->whereNotNull('file_path');
                    });
            });

        foreach ($query->lazyById() as $document) {
            if (! $this->isPotentialLegacyCandidate($document)
                && ! $this->isCommittedRecoveryCandidate($document, $recordedKeys)) {
                continue;
            }

            $documents->push($document);
            if ($documents->count() >= $limit) {
                break;
            }
        }

        return $documents;
    }

    private function isPotentialLegacyCandidate(KmPengajuan $document): bool
    {
        return $document->file_disk === null || $document->file_path === null;
    }

    private function hasUntouchedLegacyMetadata(KmPengajuan $document): bool
    {
        foreach (self::FILE_METADATA_FIELDS as $field) {
            if ($document->getRawOriginal($field) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, null>
     */
    private function emptyFileMetadata(): array
    {
        return array_fill_keys(self::FILE_METADATA_FIELDS, null);
    }

    private function hasCommittedPrivateMetadata(KmPengajuan $document): bool
    {
        if ($document->file_disk !== KmFileService::DISK
            || ! is_string($document->file_path)
            || $document->file_path === ''
            || ! is_string($document->file_original_name)
            || trim($document->file_original_name) === ''
            || ! is_string($document->file_mime_type)
            || preg_match('/^[0-9]+$/', (string) $document->getRawOriginal('file_size_bytes')) !== 1
            || ! is_string($document->file_checksum_sha256)
            || preg_match('/^[a-f0-9]{64}$/', $document->file_checksum_sha256) !== 1
            || $document->getRawOriginal('file_migrated_at') === null) {
            return false;
        }

        try {
            $this->assertSafePrivateDestination((int) $document->getKey(), $document->file_path);
        } catch (RuntimeException) {
            return false;
        }

        $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));

        return $this->mimeMatchesExtension($extension, $document->file_mime_type);
    }

    /**
     * @param  array<string, true>  $recordedKeys
     */
    private function isCommittedRecoveryCandidate(KmPengajuan $document, array $recordedKeys): bool
    {
        if (! $this->hasCommittedPrivateMetadata($document)) {
            return false;
        }

        $legacyName = (string) $document->file;
        if ($legacyName === ''
            || basename($legacyName) !== $legacyName
            || basename((string) $document->file_path) === $legacyName
            || isset($recordedKeys[$this->migrationKey($document)])) {
            return false;
        }

        $source = public_path('assets/image/'.$legacyName);
        $backup = Storage::disk(KmFileService::DISK)->path(
            'legacy-backup/'.$document->getKey().'/'.$legacyName,
        );

        return File::isFile($source) || File::isFile($backup);
    }

    /**
     * @return array<string, true>
     */
    private function recordedMigrationKeys(): array
    {
        $keys = [];
        foreach (Storage::disk(KmFileService::DISK)->allFiles('file-migrations') as $path) {
            try {
                $manifest = json_decode(
                    Storage::disk(KmFileService::DISK)->get($path),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (Throwable) {
                continue;
            }

            if (! is_array($manifest)
                || ($manifest['version'] ?? null) !== self::MANIFEST_VERSION
                || ($manifest['database'] ?? null) !== DB::getDatabaseName()
                || ! is_array($manifest['entries'] ?? null)) {
                continue;
            }

            foreach ($manifest['entries'] as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                try {
                    $this->assertSafeManifestEntry($entry);
                } catch (Throwable) {
                    continue;
                }

                $keys[$this->manifestEntryKey($entry)] = true;
            }
        }

        return $keys;
    }

    private function migrationKey(KmPengajuan $document): string
    {
        return implode('|', [
            (string) $document->getKey(),
            (string) $document->file_path,
            (string) $document->file_checksum_sha256,
        ]);
    }

    /** @param array<string, mixed> $entry */
    private function manifestEntryKey(array $entry): string
    {
        return implode('|', [
            (string) $entry['document_id'],
            (string) $entry['destination'],
            (string) $entry['checksum_sha256'],
        ]);
    }

    private function restore(string $manifestArgument): int
    {
        try {
            $manifestPath = $this->resolveManifestPath($manifestArgument);
            $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $this->assertManifestEnvelope($manifest);
        } catch (Throwable $exception) {
            $this->error('Manifest restore ditolak: '.$exception->getMessage());

            return self::FAILURE;
        }

        $failed = false;

        foreach ($manifest['entries'] ?? [] as $entry) {
            try {
                $this->restoreEntry($entry);
                $this->info(sprintf('RESTORED document_id=%d', $entry['document_id']));
            } catch (Throwable $exception) {
                $failed = true;
                $this->error(sprintf('FAIL document_id=%s: %s', $entry['document_id'] ?? '?', $exception->getMessage()));
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function assertManifestEnvelope(mixed $manifest): void
    {
        if (! is_array($manifest)
            || ($manifest['version'] ?? null) !== self::MANIFEST_VERSION
            || ! is_string($manifest['database'] ?? null)
            || ! hash_equals(DB::getDatabaseName(), $manifest['database'])
            || ! is_array($manifest['entries'] ?? null)) {
            throw new RuntimeException(
                'Manifest file migration memiliki version, database, atau daftar entry yang tidak sesuai.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function restoreEntry(
        array $entry,
        bool $deleteDestinationAfterRestore = false,
    ): void {
        $this->assertSafeManifestEntry($entry);
        $oldMetadata = $this->validatedOldMetadata($entry);
        $newMetadata = $this->validatedNewMetadata($entry);

        $backupPath = Storage::disk(KmFileService::DISK)->path((string) $entry['backup']);
        $checksum = (string) $entry['checksum_sha256'];
        $this->assertFileChecksum(
            $backupPath,
            $checksum,
            'Backup private hilang atau checksum tidak sesuai.',
        );

        $destination = (string) $entry['destination'];
        $destinationPath = Storage::disk(KmFileService::DISK)->path($destination);
        $this->assertFileChecksum(
            $destinationPath,
            $checksum,
            'Private destination hilang atau checksum tidak sesuai.',
        );

        $source = (string) $entry['source'];
        if (File::exists($source)) {
            $this->assertFileChecksum(
                $source,
                $checksum,
                'File public tujuan sudah ada dengan checksum berbeda.',
            );
        }

        $sourceCreated = false;
        try {
            DB::transaction(function () use (
                $entry,
                $oldMetadata,
                $newMetadata,
                $backupPath,
                $destinationPath,
                $source,
                $checksum,
                &$sourceCreated,
            ): void {
                $document = KmPengajuan::query()
                    ->whereKey((int) $entry['document_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertFileChecksum(
                    $backupPath,
                    $checksum,
                    'Backup private berubah sebelum restore metadata.',
                );
                $this->assertFileChecksum(
                    $destinationPath,
                    $checksum,
                    'Private destination berubah sebelum restore metadata.',
                );

                $currentMetadata = $this->rawFileMetadata($document);
                if ($this->metadataMatches($currentMetadata, $oldMetadata)) {
                    $sourceCreated = $this->ensureVerifiedPublicCopy($backupPath, $source, $checksum);

                    return;
                }

                if (! $this->metadataMatches($currentMetadata, $newMetadata)) {
                    throw new RuntimeException('Metadata dokumen berubah setelah manifest dibuat.');
                }

                $sourceCreated = $this->ensureVerifiedPublicCopy($backupPath, $source, $checksum);

                if (! $document->forceFill($oldMetadata)->save()) {
                    throw new RuntimeException('Metadata file legacy gagal dipulihkan.');
                }
            });
        } catch (Throwable $exception) {
            if ($sourceCreated) {
                try {
                    $this->removePublicFileOrFail($source);
                } catch (Throwable $compensationException) {
                    throw new RuntimeException(
                        'Restore metadata gagal dan file public hasil copy tidak dapat dikompensasi: '
                        .$compensationException->getMessage(),
                        previous: $exception,
                    );
                }
            }

            throw $exception;
        }

        if ($deleteDestinationAfterRestore) {
            $this->removePrivateFileOrFail($destination);
        }
    }

    private function assertFileChecksum(string $path, string $checksum, string $message): void
    {
        $actual = File::isFile($path) ? hash_file('sha256', $path) : false;
        if (! is_string($actual) || ! hash_equals($checksum, $actual)) {
            throw new RuntimeException($message);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function finalizeCommittedDocument(
        KmPengajuan $document,
        string $legacyName,
        string $source,
    ): array {
        if (! $this->hasCommittedPrivateMetadata($document)) {
            throw new RuntimeException('Metadata private tidak lengkap untuk recovery.');
        }

        $destination = (string) $document->file_path;
        $checksum = (string) $document->file_checksum_sha256;
        $this->assertSafePrivateDestination((int) $document->getKey(), $destination);

        $destinationPath = Storage::disk(KmFileService::DISK)->path($destination);
        $backup = sprintf('legacy-backup/%d/%s', $document->getKey(), $legacyName);
        $backupPath = Storage::disk(KmFileService::DISK)->path($backup);
        $this->assertFileChecksum(
            $destinationPath,
            $checksum,
            'Private destination recovery hilang atau checksum tidak sesuai.',
        );
        if (File::exists($source)) {
            $this->assertFileChecksum(
                $source,
                $checksum,
                'File public recovery berubah setelah metadata private tersimpan.',
            );
        } else {
            $this->assertFileChecksum(
                $backupPath,
                $checksum,
                'Backup private recovery hilang atau checksum tidak sesuai.',
            );
        }

        if (! Storage::disk(KmFileService::DISK)->makeDirectory(dirname($backup))) {
            throw new RuntimeException('Direktori backup private recovery tidak dapat dibuat.');
        }

        $this->movePublicSourceToBackup(
            $source,
            $backupPath,
            $destinationPath,
            $checksum,
        );

        return [
            'document_id' => $document->getKey(),
            'source' => $source,
            'destination' => $destination,
            'backup' => $backup,
            'checksum_sha256' => $checksum,
            'old_metadata' => $this->emptyFileMetadata(),
            'new_metadata' => $this->rawFileMetadata($document),
            'recovered_after_commit' => true,
        ];
    }

    private function movePublicSourceToBackup(
        string $source,
        string $backupPath,
        string $destinationPath,
        string $checksum,
    ): void {
        $this->assertFileChecksum(
            $destinationPath,
            $checksum,
            'Private destination berubah sebelum finalisasi backup.',
        );

        if (! File::exists($source)) {
            $this->assertFileChecksum(
                $backupPath,
                $checksum,
                'File source dan backup tidak tersedia setelah metadata tersimpan.',
            );

            return;
        }

        $this->assertFileChecksum(
            $source,
            $checksum,
            'File sumber legacy berubah sebelum dipindahkan ke backup.',
        );

        if (File::exists($backupPath)) {
            $this->assertFileChecksum(
                $backupPath,
                $checksum,
                'Backup private dengan nama sama memiliki checksum berbeda.',
            );

            if (! File::delete($source)) {
                throw new RuntimeException('File sumber legacy gagal dihapus setelah backup terverifikasi tersedia.');
            }
        } elseif (! File::move($source, $backupPath)) {
            throw new RuntimeException('File sumber legacy gagal dipindahkan ke backup private.');
        }

        $this->assertFileChecksum(
            $backupPath,
            $checksum,
            'Backup private hilang atau checksum tidak sesuai setelah pemindahan.',
        );
        if (File::exists($source)) {
            throw new RuntimeException('File sumber legacy masih berada di public setelah pemindahan.');
        }
        $this->assertFileChecksum(
            $destinationPath,
            $checksum,
            'Private destination berubah setelah finalisasi backup.',
        );
    }

    /**
     * @param  array<string, mixed>  $oldMetadata
     * @param  array<string, mixed>  $newMetadata
     */
    private function compensateCommittedMigration(
        int|string $documentId,
        array $oldMetadata,
        array $newMetadata,
        string $backupPath,
        string $source,
        string $destination,
        string $destinationPath,
        string $checksum,
        bool $backupExistedBefore,
    ): void {
        // Do not expose a legacy row again until a checksum-verified public
        // source exists. The private destination is a safe fallback if the
        // attempted backup move produced a corrupt or partial target.
        $this->ensureVerifiedPublicCopyFromCandidates(
            [$backupPath, $destinationPath],
            $source,
            $checksum,
        );

        $this->rollbackMetadataGuarded(
            $documentId,
            $oldMetadata,
            $newMetadata,
        );

        $this->removePrivateFileOrFail($destination);

        if (! $backupExistedBefore && File::exists($backupPath)) {
            $backupChecksum = hash_file('sha256', $backupPath);
            if (! is_string($backupChecksum) || ! hash_equals($checksum, $backupChecksum)) {
                if (! File::delete($backupPath) || File::exists($backupPath)) {
                    throw new RuntimeException('Backup parsial yang dibuat proses ini gagal dibersihkan.');
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $oldMetadata
     * @param  array<string, mixed>  $newMetadata
     */
    private function rollbackMetadataGuarded(
        int|string $documentId,
        array $oldMetadata,
        array $newMetadata,
    ): void {
        DB::transaction(function () use ($documentId, $oldMetadata, $newMetadata): void {
            $document = KmPengajuan::query()->whereKey($documentId)->lockForUpdate()->firstOrFail();
            $currentMetadata = $this->rawFileMetadata($document);

            if ($this->metadataMatches($currentMetadata, $oldMetadata)) {
                return;
            }

            if (! $this->metadataMatches($currentMetadata, $newMetadata)) {
                throw new RuntimeException(
                    'Metadata berubah setelah commit; rollback otomatis dibatalkan agar perubahan lain tidak tertimpa.',
                );
            }

            if (! $document->forceFill($oldMetadata)->save()) {
                throw new RuntimeException('Metadata legacy gagal dikembalikan setelah finalisasi backup gagal.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function rawFileMetadata(KmPengajuan $document): array
    {
        $metadata = [];
        foreach (self::FILE_METADATA_FIELDS as $field) {
            $metadata[$field] = $document->getRawOriginal($field);
        }

        return $metadata;
    }

    /**
     * @param  array<string, mixed>  $actual
     * @param  array<string, mixed>  $expected
     */
    private function metadataMatches(array $actual, array $expected): bool
    {
        foreach (self::FILE_METADATA_FIELDS as $field) {
            $actualValue = $actual[$field] ?? null;
            $expectedValue = $expected[$field] ?? null;

            if ($actualValue === null || $expectedValue === null) {
                if ($actualValue !== $expectedValue) {
                    return false;
                }

                continue;
            }

            if ((string) $actualValue !== (string) $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $candidatePaths
     */
    private function ensureVerifiedPublicCopyFromCandidates(
        array $candidatePaths,
        string $source,
        string $checksum,
    ): bool {
        if (File::exists($source)) {
            $this->assertFileChecksum(
                $source,
                $checksum,
                'File public hasil kompensasi memiliki checksum berbeda.',
            );

            return false;
        }

        $verifiedCandidate = null;
        foreach ($candidatePaths as $candidatePath) {
            $candidateChecksum = File::isFile($candidatePath)
                ? hash_file('sha256', $candidatePath)
                : false;

            if (is_string($candidateChecksum) && hash_equals($checksum, $candidateChecksum)) {
                $verifiedCandidate = $candidatePath;
                break;
            }
        }

        if ($verifiedCandidate === null) {
            throw new RuntimeException(
                'Backup dan private destination tidak dapat dipakai untuk memulihkan file public.',
            );
        }

        return $this->copyVerifiedFileToPublic($verifiedCandidate, $source, $checksum);
    }

    private function ensureVerifiedPublicCopy(
        string $backupPath,
        string $source,
        string $checksum,
    ): bool {
        if (File::exists($source)) {
            $this->assertFileChecksum(
                $source,
                $checksum,
                'File public hasil kompensasi memiliki checksum berbeda.',
            );

            return false;
        }

        $this->assertFileChecksum(
            $backupPath,
            $checksum,
            'Backup private tidak dapat dipakai untuk memulihkan file public.',
        );

        return $this->copyVerifiedFileToPublic($backupPath, $source, $checksum);
    }

    private function copyVerifiedFileToPublic(
        string $verifiedSource,
        string $publicDestination,
        string $checksum,
    ): bool {
        File::ensureDirectoryExists(dirname($publicDestination));

        if (! File::copy($verifiedSource, $publicDestination)) {
            if (File::exists($publicDestination)) {
                try {
                    $this->removePublicFileOrFail($publicDestination);
                } catch (Throwable $cleanupException) {
                    throw new RuntimeException(
                        'Copy file public gagal dan hasil parsial tidak dapat dihapus: '
                        .$cleanupException->getMessage(),
                        previous: $cleanupException,
                    );
                }
            }

            throw new RuntimeException('Backup private gagal disalin kembali ke public.');
        }

        try {
            $this->assertFileChecksum(
                $publicDestination,
                $checksum,
                'Checksum file public hasil copy tidak sesuai.',
            );
        } catch (Throwable $exception) {
            try {
                $this->removePublicFileOrFail($publicDestination);
            } catch (Throwable $cleanupException) {
                throw new RuntimeException(
                    'Checksum file public hasil copy tidak sesuai dan hasil parsial tidak dapat dihapus: '
                    .$cleanupException->getMessage(),
                    previous: $exception,
                );
            }

            throw $exception;
        }

        return true;
    }

    private function removePublicFileOrFail(string $path): void
    {
        if (! File::exists($path)) {
            return;
        }

        if (! File::delete($path) || File::exists($path)) {
            throw new RuntimeException("File public gagal dihapus: {$path}");
        }
    }

    private function removePrivateFileOrFail(string $path): void
    {
        $disk = Storage::disk(KmFileService::DISK);
        if (! $disk->exists($path)) {
            return;
        }

        if (! $disk->delete($path) || $disk->exists($path)) {
            throw new RuntimeException("File private gagal dihapus: {$path}");
        }
    }

    private function assertSafePrivateDestination(int $documentId, string $destination): void
    {
        $normalized = str_replace('\\', '/', $destination);
        if (preg_match(
            '#^documents/'.preg_quote((string) $documentId, '#').'/[a-f0-9-]+\.(pdf|ppt|pptx)$#i',
            $normalized,
        ) !== 1) {
            throw new RuntimeException('Path private recovery tidak aman.');
        }
    }

    private function resolveManifestPath(string $argument): string
    {
        $root = realpath(Storage::disk(KmFileService::DISK)->path('file-migrations'));
        $path = realpath($argument);

        if ($path === false) {
            $path = realpath(Storage::disk(KmFileService::DISK)->path('file-migrations/'.basename($argument)));
        }

        if ($root === false || $path === false
            || ! str_starts_with(strtolower($path), strtolower($root.DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Manifest harus berada di folder private km/file-migrations.');
        }

        return $path;
    }

    private function validatedMime(string $path, string $extension): string
    {
        $mime = (string) File::mimeType($path);
        if (! $this->mimeMatchesExtension($extension, $mime)) {
            throw new RuntimeException('MIME file legacy tidak sesuai dengan ekstensi.');
        }

        return $mime;
    }

    private function mimeMatchesExtension(string $extension, string $mime): bool
    {
        $allowed = match ($extension) {
            'pdf' => ['application/pdf', 'application/x-pdf'],
            'ppt' => [
                'application/vnd.ms-powerpoint',
                'application/mspowerpoint',
                'application/powerpoint',
                'application/x-mspowerpoint',
            ],
            'pptx' => [
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
            ],
            default => [],
        };

        return in_array($mime, $allowed, true);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function assertSafeManifestEntry(array $entry): void
    {
        $documentId = (int) ($entry['document_id'] ?? 0);
        $source = str_replace('\\', '/', (string) ($entry['source'] ?? ''));
        $expectedSource = str_replace(
            '\\',
            '/',
            public_path('assets/image/'.basename($source)),
        );
        $destination = str_replace('\\', '/', (string) ($entry['destination'] ?? ''));
        $backup = str_replace('\\', '/', (string) ($entry['backup'] ?? ''));
        $checksum = (string) ($entry['checksum_sha256'] ?? '');

        if ($documentId < 1
            || strtolower($source) !== strtolower($expectedSource)
            || preg_match(
                '#^documents/'.preg_quote((string) $documentId, '#').'/[a-f0-9-]+\.(pdf|ppt|pptx)$#i',
                $destination,
            ) !== 1
            || preg_match(
                '#^legacy-backup/'.preg_quote((string) $documentId, '#').'/[^/\\\\]+$#',
                $backup,
            ) !== 1
            || preg_match('/^[a-f0-9]{64}$/', $checksum) !== 1) {
            throw new RuntimeException('Manifest file migration berisi path atau checksum yang tidak aman.');
        }

        $this->validatedOldMetadata($entry);
        $this->validatedNewMetadata($entry);
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, null>
     */
    private function validatedOldMetadata(array $entry): array
    {
        $metadata = $entry['old_metadata'] ?? null;
        if (! is_array($metadata)) {
            throw new RuntimeException('Manifest file migration tidak memiliki old_metadata yang valid.');
        }

        $actualKeys = array_keys($metadata);
        $expectedKeys = self::FILE_METADATA_FIELDS;
        sort($actualKeys);
        sort($expectedKeys);
        if ($actualKeys !== $expectedKeys) {
            throw new RuntimeException(
                'old_metadata manifest wajib memiliki tepat tujuh field metadata file.'
            );
        }

        foreach (self::FILE_METADATA_FIELDS as $field) {
            if ($metadata[$field] !== null) {
                throw new RuntimeException(
                    "old_metadata.{$field} harus null untuk row legacy yang belum dimigrasikan."
                );
            }
        }

        /** @var array<string, null> $metadata */
        return array_intersect_key($metadata, array_flip(self::FILE_METADATA_FIELDS));
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private function validatedNewMetadata(array $entry): array
    {
        $metadata = $entry['new_metadata'] ?? null;
        if (! is_array($metadata)) {
            throw new RuntimeException('Manifest file migration tidak memiliki new_metadata yang valid.');
        }

        $actualKeys = array_keys($metadata);
        $expectedKeys = self::FILE_METADATA_FIELDS;
        sort($actualKeys);
        sort($expectedKeys);
        if ($actualKeys !== $expectedKeys) {
            throw new RuntimeException(
                'new_metadata manifest wajib memiliki tepat tujuh field metadata file.'
            );
        }

        $disk = $metadata['file_disk'];
        $path = $metadata['file_path'];
        $originalName = $metadata['file_original_name'];
        $mime = $metadata['file_mime_type'];
        $size = $metadata['file_size_bytes'];
        $checksum = $metadata['file_checksum_sha256'];
        $migratedAt = $metadata['file_migrated_at'];

        if (! is_string($disk)
            || ! is_string($path)
            || ! is_string($originalName)
            || trim($originalName) === ''
            || ! is_string($mime)
            || ! is_int($size)
            || $size < 0
            || ! is_string($checksum)
            || ! is_string($migratedAt)
            || preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $migratedAt) !== 1) {
            throw new RuntimeException('new_metadata manifest memiliki tipe atau nilai field yang tidak valid.');
        }

        $entryDestination = (string) ($entry['destination'] ?? '');
        $entryChecksum = (string) ($entry['checksum_sha256'] ?? '');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($disk !== KmFileService::DISK
            || $path !== $entryDestination
            || ! hash_equals($entryChecksum, $checksum)
            || ! $this->mimeMatchesExtension($extension, $mime)) {
            throw new RuntimeException(
                'new_metadata manifest tidak konsisten dengan disk, destination, checksum, atau MIME.'
            );
        }

        return array_intersect_key($metadata, array_flip(self::FILE_METADATA_FIELDS));
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function writeManifest(string $path, array $manifest): void
    {
        $written = Storage::disk(KmFileService::DISK)->put(
            $path,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
        if (! $written) {
            throw new RuntimeException('Manifest file migration gagal ditulis ke private storage.');
        }
    }
}
