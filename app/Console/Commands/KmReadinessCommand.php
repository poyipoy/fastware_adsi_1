<?php

namespace App\Console\Commands;

use App\Services\KnowledgeManagement\KmFileService;
use Illuminate\Console\Command;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class KmReadinessCommand extends Command
{
    /** @var list<string> */
    private const FILE_METADATA_COLUMNS = [
        'file_disk',
        'file_path',
        'file_original_name',
        'file_mime_type',
        'file_size_bytes',
        'file_checksum_sha256',
        'file_migrated_at',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const REQUIRED_COLUMNS = [
        'users' => [
            'id',
            'km_total_poin',
        ],
        'km_kategoris' => [
            'id',
            'nama_kategori',
            'poin_kategori',
            'created_at',
            'updated_at',
        ],
        'km_pengajuans' => [
            'id',
            'id_user',
            'id_km_kategori',
            'judul',
            'keterangan',
            'posisi',
            'image',
            'file',
            'file_name',
            'persetujuan',
            'status',
            'created_at',
            'updated_at',
            'modified_by',
            'file_disk',
            'file_path',
            'file_original_name',
            'file_mime_type',
            'file_size_bytes',
            'file_checksum_sha256',
            'file_migrated_at',
        ],
        'km_transaksis' => [
            'id',
            'id_km_pengajuan',
            'id_user',
            'poin',
            'level',
            'status',
            'created_at',
            'updated_at',
            'modified_by',
            'completed_at',
            'points_awarded_at',
        ],
        'km_lihat_bukus' => [
            'id',
            'id_km_transaksi',
            'id_km_pengajuan',
            'jumlah_lihat',
            'created_at',
            'updated_at',
        ],
        'km_sukas' => [
            'id',
            'id_user',
            'id_km_pengajuan',
            'jumlah_like',
            'created_at',
            'updated_at',
        ],
        'km_insights' => [
            'id',
            'id_user',
            'id_km_pengajuan',
            'content',
            'created_at',
            'updated_at',
        ],
        'km_approval_events' => [
            'id',
            'km_pengajuan_id',
            'actor_id',
            'actor_name',
            'actor_role_snapshot',
            'action',
            'from_status',
            'to_status',
            'reason',
            'metadata',
            'acted_at',
            'created_at',
        ],
    ];

    /**
     * Only migration-critical shapes are asserted here. Less critical legacy
     * string/timestamp variations remain covered by the complete column list.
     *
     * @var list<array{
     *     table: string,
     *     column: string,
     *     type?: string,
     *     nullable?: bool,
     *     default?: string,
     *     primary?: bool,
     *     auto_increment?: bool,
     *     integer_compatible?: bool
     * }>
     */
    private const REQUIRED_COLUMN_SHAPES = [
        ['table' => 'users', 'column' => 'id', 'type' => 'bigint unsigned', 'primary' => true, 'auto_increment' => true],
        ['table' => 'users', 'column' => 'km_total_poin', 'integer_compatible' => true],
        ['table' => 'km_kategoris', 'column' => 'id', 'primary' => true, 'auto_increment' => true],
        ['table' => 'km_pengajuans', 'column' => 'id', 'type' => 'int', 'primary' => true, 'auto_increment' => true],
        ['table' => 'km_transaksis', 'column' => 'id', 'type' => 'int', 'primary' => true, 'auto_increment' => true],
        ['table' => 'km_lihat_bukus', 'column' => 'id', 'primary' => true, 'auto_increment' => true],
        ['table' => 'km_sukas', 'column' => 'id', 'primary' => true, 'auto_increment' => true],
        ['table' => 'km_insights', 'column' => 'id', 'type' => 'int', 'primary' => true, 'auto_increment' => true],
        ['table' => 'km_pengajuans', 'column' => 'id_user', 'type' => 'bigint unsigned', 'nullable' => true],
        ['table' => 'km_pengajuans', 'column' => 'id_km_kategori', 'type' => 'bigint', 'nullable' => true],
        ['table' => 'km_transaksis', 'column' => 'id_km_pengajuan', 'type' => 'int', 'nullable' => true],
        ['table' => 'km_transaksis', 'column' => 'id_user', 'type' => 'bigint unsigned', 'nullable' => true],
        ['table' => 'km_transaksis', 'column' => 'modified_by', 'type' => 'bigint unsigned', 'nullable' => true],
        ['table' => 'km_lihat_bukus', 'column' => 'id_km_transaksi', 'type' => 'int', 'nullable' => true],
        ['table' => 'km_lihat_bukus', 'column' => 'id_km_pengajuan', 'type' => 'int', 'nullable' => true],
        ['table' => 'km_sukas', 'column' => 'id_user', 'type' => 'bigint unsigned', 'nullable' => true],
        ['table' => 'km_sukas', 'column' => 'id_km_pengajuan', 'type' => 'int', 'nullable' => true],
        ['table' => 'km_insights', 'column' => 'id_user', 'type' => 'bigint unsigned', 'nullable' => true],
        ['table' => 'km_insights', 'column' => 'id_km_pengajuan', 'type' => 'int', 'nullable' => true],
        ['table' => 'km_lihat_bukus', 'column' => 'jumlah_lihat', 'type' => 'bigint unsigned', 'nullable' => false, 'default' => '0'],
        ['table' => 'km_transaksis', 'column' => 'completed_at', 'type' => 'timestamp', 'nullable' => true],
        ['table' => 'km_transaksis', 'column' => 'points_awarded_at', 'type' => 'timestamp', 'nullable' => true],
        ['table' => 'km_approval_events', 'column' => 'id', 'type' => 'bigint unsigned', 'primary' => true, 'auto_increment' => true],
        ['table' => 'km_approval_events', 'column' => 'km_pengajuan_id', 'type' => 'int', 'nullable' => false],
        ['table' => 'km_approval_events', 'column' => 'actor_id', 'type' => 'bigint unsigned', 'nullable' => true],
        ['table' => 'km_approval_events', 'column' => 'action', 'type' => 'varchar(32)', 'nullable' => false],
        ['table' => 'km_approval_events', 'column' => 'from_status', 'type' => 'tinyint unsigned', 'nullable' => true],
        ['table' => 'km_approval_events', 'column' => 'to_status', 'type' => 'tinyint unsigned', 'nullable' => false],
        ['table' => 'km_approval_events', 'column' => 'metadata', 'type' => 'json', 'nullable' => true],
        ['table' => 'km_approval_events', 'column' => 'acted_at', 'type' => 'timestamp', 'nullable' => false],
        ['table' => 'km_approval_events', 'column' => 'created_at', 'type' => 'timestamp', 'nullable' => false],
        ['table' => 'km_pengajuans', 'column' => 'file_disk', 'type' => 'varchar(32)', 'nullable' => true],
        ['table' => 'km_pengajuans', 'column' => 'file_path', 'type' => 'varchar(1024)', 'nullable' => true],
        ['table' => 'km_pengajuans', 'column' => 'file_original_name', 'type' => 'varchar(255)', 'nullable' => true],
        ['table' => 'km_pengajuans', 'column' => 'file_mime_type', 'type' => 'varchar(127)', 'nullable' => true],
        ['table' => 'km_pengajuans', 'column' => 'file_size_bytes', 'type' => 'bigint unsigned', 'nullable' => true],
        ['table' => 'km_pengajuans', 'column' => 'file_checksum_sha256', 'type' => 'char(64)', 'nullable' => true],
        ['table' => 'km_pengajuans', 'column' => 'file_migrated_at', 'type' => 'timestamp', 'nullable' => true],
    ];

    /**
     * @var list<array{table: string, name: string, columns: list<string>}>
     */
    private const REQUIRED_UNIQUE_INDEXES = [
        [
            'table' => 'km_transaksis',
            'name' => 'km_transaksis_user_document_unique',
            'columns' => ['id_user', 'id_km_pengajuan'],
        ],
        [
            'table' => 'km_sukas',
            'name' => 'km_sukas_user_document_unique',
            'columns' => ['id_user', 'id_km_pengajuan'],
        ],
        [
            'table' => 'km_lihat_bukus',
            'name' => 'km_lihat_bukus_document_unique',
            'columns' => ['id_km_pengajuan'],
        ],
    ];

    /**
     * @var list<array{table: string, name: string, columns: list<string>}>
     */
    private const REQUIRED_NON_UNIQUE_INDEXES = [
        ['table' => 'km_pengajuans', 'name' => 'km_pengajuans_status_posisi_index', 'columns' => ['status', 'posisi']],
        ['table' => 'km_pengajuans', 'name' => 'km_pengajuans_user_status_index', 'columns' => ['id_user', 'status']],
        ['table' => 'km_pengajuans', 'name' => 'km_pengajuans_category_index', 'columns' => ['id_km_kategori']],
        ['table' => 'km_pengajuans', 'name' => 'km_pengajuans_file_checksum_index', 'columns' => ['file_checksum_sha256']],
        ['table' => 'km_pengajuans', 'name' => 'km_pengajuans_file_migrated_at_index', 'columns' => ['file_migrated_at']],
        ['table' => 'km_transaksis', 'name' => 'km_transaksis_status_completed_at_index', 'columns' => ['status', 'completed_at']],
        ['table' => 'km_transaksis', 'name' => 'km_transaksis_document_index', 'columns' => ['id_km_pengajuan']],
        ['table' => 'km_transaksis', 'name' => 'km_transaksis_modified_by_index', 'columns' => ['modified_by']],
        ['table' => 'km_sukas', 'name' => 'km_sukas_document_index', 'columns' => ['id_km_pengajuan']],
        ['table' => 'km_lihat_bukus', 'name' => 'km_lihat_bukus_transaction_index', 'columns' => ['id_km_transaksi']],
        ['table' => 'km_insights', 'name' => 'km_insights_user_index', 'columns' => ['id_user']],
        ['table' => 'km_insights', 'name' => 'km_insights_document_index', 'columns' => ['id_km_pengajuan']],
        ['table' => 'km_approval_events', 'name' => 'km_approval_events_document_acted_at_index', 'columns' => ['km_pengajuan_id', 'acted_at']],
        ['table' => 'km_approval_events', 'name' => 'km_approval_events_actor_acted_at_index', 'columns' => ['actor_id', 'acted_at']],
    ];

    /**
     * @var list<array{
     *     table: string,
     *     name: string,
     *     column: string,
     *     target_table: string,
     *     target_column: string,
     *     delete_rule: string
     * }>
     */
    private const REQUIRED_FOREIGN_KEYS = [
        ['table' => 'km_pengajuans', 'name' => 'km_pengajuans_user_foreign', 'column' => 'id_user', 'target_table' => 'users', 'target_column' => 'id', 'delete_rule' => 'SET NULL'],
        ['table' => 'km_pengajuans', 'name' => 'km_pengajuans_category_foreign', 'column' => 'id_km_kategori', 'target_table' => 'km_kategoris', 'target_column' => 'id', 'delete_rule' => 'SET NULL'],
        ['table' => 'km_transaksis', 'name' => 'km_transaksis_user_foreign', 'column' => 'id_user', 'target_table' => 'users', 'target_column' => 'id', 'delete_rule' => 'CASCADE'],
        ['table' => 'km_transaksis', 'name' => 'km_transaksis_document_foreign', 'column' => 'id_km_pengajuan', 'target_table' => 'km_pengajuans', 'target_column' => 'id', 'delete_rule' => 'CASCADE'],
        ['table' => 'km_transaksis', 'name' => 'km_transaksis_modified_by_foreign', 'column' => 'modified_by', 'target_table' => 'users', 'target_column' => 'id', 'delete_rule' => 'SET NULL'],
        ['table' => 'km_sukas', 'name' => 'km_sukas_user_foreign', 'column' => 'id_user', 'target_table' => 'users', 'target_column' => 'id', 'delete_rule' => 'CASCADE'],
        ['table' => 'km_sukas', 'name' => 'km_sukas_document_foreign', 'column' => 'id_km_pengajuan', 'target_table' => 'km_pengajuans', 'target_column' => 'id', 'delete_rule' => 'CASCADE'],
        ['table' => 'km_insights', 'name' => 'km_insights_user_foreign', 'column' => 'id_user', 'target_table' => 'users', 'target_column' => 'id', 'delete_rule' => 'CASCADE'],
        ['table' => 'km_insights', 'name' => 'km_insights_document_foreign', 'column' => 'id_km_pengajuan', 'target_table' => 'km_pengajuans', 'target_column' => 'id', 'delete_rule' => 'CASCADE'],
        ['table' => 'km_lihat_bukus', 'name' => 'km_lihat_bukus_document_foreign', 'column' => 'id_km_pengajuan', 'target_table' => 'km_pengajuans', 'target_column' => 'id', 'delete_rule' => 'CASCADE'],
        ['table' => 'km_lihat_bukus', 'name' => 'km_lihat_bukus_transaction_foreign', 'column' => 'id_km_transaksi', 'target_table' => 'km_transaksis', 'target_column' => 'id', 'delete_rule' => 'SET NULL'],
        ['table' => 'km_approval_events', 'name' => 'km_approval_events_document_foreign', 'column' => 'km_pengajuan_id', 'target_table' => 'km_pengajuans', 'target_column' => 'id', 'delete_rule' => 'RESTRICT'],
        ['table' => 'km_approval_events', 'name' => 'km_approval_events_actor_foreign', 'column' => 'actor_id', 'target_table' => 'users', 'target_column' => 'id', 'delete_rule' => 'SET NULL'],
    ];

    protected $signature = 'km:readiness
        {--strict : Perlakukan WARN sebagai kegagalan}
        {--json : Cetak hasil sebagai JSON}';

    protected $description = 'Memeriksa kesiapan schema, storage, queue, dan scheduler Knowledge Management';

    /**
     * @var list<array{name: string, status: string, message: string, required: bool}>
     */
    private array $checks = [];

    public function handle(): int
    {
        $this->checks = [];

        try {
            $this->checkSchema();
        } catch (Throwable $exception) {
            $this->record('schema.connection', 'FAIL', $exception->getMessage(), true);
        }

        $this->checkStorage();
        try {
            $this->checkLegacyFiles();
        } catch (Throwable $exception) {
            $this->record('files.readiness', 'FAIL', $exception->getMessage(), true);
        }

        try {
            $this->checkQueue();
        } catch (Throwable $exception) {
            $this->record('queue.readiness', 'WARN', $exception->getMessage(), false);
        }

        $this->checkScheduler();

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'checks' => $this->checks,
                'summary' => [
                    'pass' => $this->count('PASS'),
                    'warn' => $this->count('WARN'),
                    'fail' => $this->count('FAIL'),
                ],
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            foreach ($this->checks as $check) {
                $this->line(sprintf(
                    '[%s] %s: %s',
                    $check['status'],
                    $check['name'],
                    $check['message'],
                ));
            }
        }

        $requiredFailure = collect($this->checks)
            ->contains(fn (array $check): bool => $check['required'] && $check['status'] === 'FAIL');
        $strictFailure = (bool) $this->option('strict')
            && collect($this->checks)->contains(fn (array $check): bool => $check['status'] === 'WARN');

        return $requiredFailure || $strictFailure ? self::FAILURE : self::SUCCESS;
    }

    private function checkSchema(): void
    {
        $tables = [
            'users',
            'km_kategoris',
            'km_pengajuans',
            'km_transaksis',
            'km_lihat_bukus',
            'km_sukas',
            'km_insights',
            'km_approval_events',
        ];
        $missingTables = array_values(array_filter(
            $tables,
            static fn (string $table): bool => ! Schema::hasTable($table),
        ));

        if ($missingTables !== []) {
            $this->record('schema.tables', 'FAIL', 'Tabel hilang: '.implode(', ', $missingTables), true);

            return;
        }

        $missingColumns = [];
        foreach (self::REQUIRED_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $missingColumns[] = $table.'.'.$column;
                }
            }
        }

        if ($missingColumns !== []) {
            $this->record('schema.columns', 'FAIL', 'Kolom hilang: '.implode(', ', $missingColumns), true);
        } else {
            $this->record(
                'schema.columns',
                'PASS',
                'Kolom baseline, hardening, approval, dan metadata file tersedia.',
                true,
            );
        }

        $invalidColumnShapes = $this->invalidColumnShapes();
        if ($invalidColumnShapes !== []) {
            $this->record(
                'schema.column_shapes',
                'FAIL',
                'Shape kolom tidak sesuai: '.implode(', ', $invalidColumnShapes),
                true,
            );
        } else {
            $this->record(
                'schema.column_shapes',
                'PASS',
                'Tipe, nullability, primary key, dan auto-increment penting sesuai.',
                true,
            );
        }

        $invalidIndexes = $this->invalidUniqueIndexes();
        if ($invalidIndexes !== []) {
            $this->record(
                'schema.unique',
                'FAIL',
                'Unique index hilang atau tidak sesuai: '.implode(', ', $invalidIndexes),
                true,
            );
        } else {
            $this->record('schema.unique', 'PASS', 'Constraint idempotensi tersedia.', true);
        }

        $invalidIndexes = $this->invalidNonUniqueIndexes();
        if ($invalidIndexes !== []) {
            $this->record(
                'schema.indexes',
                'FAIL',
                'Index non-unique hilang atau tidak sesuai: '.implode(', ', $invalidIndexes),
                true,
            );
        } else {
            $this->record(
                'schema.indexes',
                'PASS',
                sprintf('%d index non-unique KM wajib tersedia.', count(self::REQUIRED_NON_UNIQUE_INDEXES)),
                true,
            );
        }

        $invalidForeignKeys = $this->invalidForeignKeys();
        if ($invalidForeignKeys !== []) {
            $this->record(
                'schema.foreign_keys',
                'FAIL',
                'Foreign key hilang atau tidak sesuai: '.implode(', ', $invalidForeignKeys),
                true,
            );
        } else {
            $this->record(
                'schema.foreign_keys',
                'PASS',
                sprintf('%d foreign key KM wajib sesuai target dan delete rule.', count(self::REQUIRED_FOREIGN_KEYS)),
                true,
            );
        }
    }

    private function checkStorage(): void
    {
        $configuration = config('filesystems.disks.'.KmFileService::DISK);
        if (! is_array($configuration)
            || ($configuration['driver'] ?? null) !== 'local'
            || ($configuration['visibility'] ?? null) !== 'private'
            || ($configuration['throw'] ?? null) !== true) {
            $this->record('storage.config', 'FAIL', 'Disk km_private belum dikonfigurasi secara aman.', true);

            return;
        }

        $root = (string) ($configuration['root'] ?? '');
        $normalizedRoot = $this->canonicalPath($root);
        $normalizedPublic = $this->canonicalPath(public_path());
        if ($root === ''
            || $normalizedRoot === $normalizedPublic
            || str_starts_with($normalizedRoot, $normalizedPublic.'/')) {
            $this->record('storage.location', 'FAIL', 'Root km_private berada di dalam public web root.', true);

            return;
        }

        if (! File::isDirectory($root) || ! is_readable($root) || ! is_writable($root)) {
            $this->record(
                'storage.permission',
                'FAIL',
                'Direktori km_private belum tersedia atau permission read/write tidak memadai.',
                true,
            );

            return;
        }

        $this->record('storage.private', 'PASS', 'Disk private berada di luar public dan dapat diakses.', true);
    }

    private function checkLegacyFiles(): void
    {
        if (! Schema::hasTable('km_pengajuans')
            || ! Schema::hasColumn('km_pengajuans', 'file_disk')) {
            $this->record('files.legacy', 'FAIL', 'Schema metadata file belum tersedia.', true);

            return;
        }

        $legacyCount = 0;
        $partialCount = 0;
        $publicExposureCount = 0;
        $mismatch = 0;

        $documents = DB::table('km_pengajuans')
            ->where(function ($query): void {
                $query->whereNotNull('file');
                foreach (self::FILE_METADATA_COLUMNS as $column) {
                    $query->orWhereNotNull($column);
                }
            })
            ->orderBy('id')
            ->select(['id', 'file', ...self::FILE_METADATA_COLUMNS])
            ->lazyById(100);

        foreach ($documents as $document) {
            $legacyName = is_string($document->file) ? $document->file : '';
            $safeLegacyName = $legacyName !== '' && basename($legacyName) === $legacyName;
            $allMetadataNull = collect(self::FILE_METADATA_COLUMNS)
                ->every(static fn (string $column): bool => $document->{$column} === null);

            if ($allMetadataNull) {
                if ($safeLegacyName) {
                    $legacyCount++;
                } else {
                    $partialCount++;
                }

                continue;
            }

            if (! $safeLegacyName || ! $this->hasCompletePrivateMetadata($document)) {
                $partialCount++;

                continue;
            }

            if (File::isFile(public_path('assets/image/'.$legacyName))) {
                $publicExposureCount++;
            }

            try {
                $path = (string) $document->file_path;
                if (! Storage::disk(KmFileService::DISK)->exists($path)) {
                    $mismatch++;

                    continue;
                }

                $localPath = Storage::disk(KmFileService::DISK)->path($path);
                $checksum = hash_file('sha256', $localPath);
                $size = filesize($localPath);
                if (! is_string($checksum)
                    || ! hash_equals((string) $document->file_checksum_sha256, $checksum)
                    || ! is_int($size)
                    || $size !== (int) $document->file_size_bytes) {
                    $mismatch++;
                }
            } catch (Throwable) {
                $mismatch++;
            }
        }

        $this->record(
            'files.legacy',
            $legacyCount > 0 ? 'WARN' : 'PASS',
            sprintf('%d dokumen legacy belum dimigrasikan.', $legacyCount),
            false,
        );

        $this->record(
            'files.metadata',
            $partialCount > 0 ? 'FAIL' : 'PASS',
            sprintf('%d dokumen memiliki metadata file parsial atau disk/path tidak valid.', $partialCount),
            true,
        );

        $this->record(
            'files.public_exposure',
            $publicExposureCount > 0 ? 'FAIL' : 'PASS',
            sprintf('%d dokumen private masih memiliki binary pada public/assets/image.', $publicExposureCount),
            true,
        );

        $this->record(
            'files.checksum',
            $mismatch > 0 ? 'FAIL' : 'PASS',
            sprintf('%d private file hilang atau checksum mismatch.', $mismatch),
            true,
        );
    }

    private function hasCompletePrivateMetadata(object $document): bool
    {
        $path = is_string($document->file_path) ? str_replace('\\', '/', $document->file_path) : '';
        $matches = [];
        if ($document->file_disk !== KmFileService::DISK
            || preg_match(
                '#^documents/'.preg_quote((string) $document->id, '#').'/[a-f0-9-]+\.(pdf|ppt|pptx)$#i',
                $path,
                $matches,
            ) !== 1
            || ! is_string($document->file_original_name)
            || trim($document->file_original_name) === ''
            || ! is_string($document->file_mime_type)
            || ! $this->mimeMatchesExtension(strtolower($matches[1]), $document->file_mime_type)
            || preg_match('/^[0-9]+$/', (string) $document->file_size_bytes) !== 1
            || ! is_string($document->file_checksum_sha256)
            || preg_match('/^[a-f0-9]{64}$/', $document->file_checksum_sha256) !== 1
            || $document->file_migrated_at === null) {
            return false;
        }

        return true;
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

    private function checkQueue(): void
    {
        $connection = (string) config('queue.default');
        $driver = (string) config('queue.connections.'.$connection.'.driver', $connection);
        if ($driver === 'sync') {
            $this->record(
                'queue.connection',
                'WARN',
                sprintf('Queue %s memakai driver sync; worker belum diperlukan fase ini.', $connection),
                false,
            );

            return;
        }

        if ($driver === 'database') {
            $queueConnection = config('queue.connections.'.$connection.'.connection');
            $failedConnection = config('queue.failed.database');
            $definitions = [
                [
                    'check' => 'queue.jobs',
                    'label' => 'jobs',
                    'table' => (string) config('queue.connections.'.$connection.'.table', 'jobs'),
                    'connection' => is_string($queueConnection) && $queueConnection !== ''
                        ? $queueConnection
                        : null,
                ],
                [
                    'check' => 'queue.failed_jobs',
                    'label' => 'failed_jobs',
                    'table' => (string) config('queue.failed.table', 'failed_jobs'),
                    'connection' => is_string($failedConnection) && $failedConnection !== ''
                        ? $failedConnection
                        : null,
                ],
            ];

            $results = [];
            foreach ($definitions as $definition) {
                $results[] = $definition + $this->probeQueueTable(
                    $definition['table'],
                    $definition['connection'],
                );
            }

            $unavailable = array_values(array_map(
                static fn (array $result): string => $result['label'],
                array_filter($results, static fn (array $result): bool => ! $result['exists']),
            ));
            $this->record(
                'queue.tables',
                $unavailable === [] ? 'PASS' : 'WARN',
                $unavailable === []
                    ? 'Tabel jobs dan failed_jobs tersedia.'
                    : 'Tabel queue hilang atau tidak dapat diperiksa: '.implode(', ', $unavailable),
                false,
            );

            foreach ($results as $result) {
                $this->record(
                    $result['check'],
                    $result['readable'] ? 'PASS' : 'WARN',
                    $result['message'],
                    false,
                );
            }

            return;
        }

        $this->record(
            'queue.connection',
            'PASS',
            sprintf('Queue connection %s memakai driver %s.', $connection, $driver),
            false,
        );
    }

    /**
     * @return array{exists: bool, readable: bool, message: string}
     */
    private function probeQueueTable(string $table, ?string $connection): array
    {
        if ($table === '') {
            return [
                'exists' => false,
                'readable' => false,
                'message' => 'Nama tabel queue tidak dikonfigurasi.',
            ];
        }

        try {
            $database = DB::connection($connection);
            if (! $database->getSchemaBuilder()->hasTable($table)) {
                return [
                    'exists' => false,
                    'readable' => false,
                    'message' => sprintf('Tabel %s tidak tersedia.', $table),
                ];
            }
        } catch (Throwable $exception) {
            return [
                'exists' => false,
                'readable' => false,
                'message' => sprintf('Tidak dapat memeriksa %s: %s', $table, $exception->getMessage()),
            ];
        }

        try {
            $sampleCount = $database->table($table)
                ->select('id')
                ->limit(1)
                ->get()
                ->count();

            return [
                'exists' => true,
                'readable' => true,
                'message' => sprintf(
                    'SELECT read-only pada %s berhasil (%d row sampel).',
                    $table,
                    $sampleCount,
                ),
            ];
        } catch (Throwable $exception) {
            return [
                'exists' => true,
                'readable' => false,
                'message' => sprintf('Tidak dapat membaca %s: %s', $table, $exception->getMessage()),
            ];
        }
    }

    private function checkScheduler(): void
    {
        $kernel = app_path('Console/Kernel.php');
        if (! File::isFile($kernel)) {
            $this->record('scheduler.code', 'WARN', 'Console Kernel tidak ditemukan.', false);

            return;
        }

        $this->record(
            'scheduler.deployment',
            'WARN',
            'Scheduler code tersedia; cron dan worker eksternal harus diverifikasi operator.',
            false,
        );
    }

    /**
     * @return list<string>
     */
    private function invalidColumnShapes(): array
    {
        $metadata = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->whereIn('table_name', array_values(array_unique(array_column(self::REQUIRED_COLUMN_SHAPES, 'table'))))
            ->whereIn('column_name', array_values(array_unique(array_column(self::REQUIRED_COLUMN_SHAPES, 'column'))))
            ->get([
                'TABLE_NAME as table_name',
                'COLUMN_NAME as column_name',
                'COLUMN_TYPE as column_type',
                'IS_NULLABLE as is_nullable',
                'COLUMN_DEFAULT as column_default',
                'COLUMN_KEY as column_key',
                'EXTRA as extra',
            ])
            ->keyBy(static fn (object $row): string => $row->table_name.'|'.$row->column_name);

        $invalid = [];
        foreach (self::REQUIRED_COLUMN_SHAPES as $required) {
            $key = $required['table'].'|'.$required['column'];
            $row = $metadata->get($key);
            $matches = $row !== null;

            if ($matches && array_key_exists('type', $required)) {
                $matches = strtolower((string) $row->column_type) === $required['type'];
            }
            if ($matches && ($required['integer_compatible'] ?? false)) {
                $matches = preg_match(
                    '/^(tinyint|smallint|mediumint|int|integer|bigint)(\(\d+\))?( unsigned)?$/',
                    strtolower((string) $row->column_type),
                ) === 1;
            }
            if ($matches && array_key_exists('nullable', $required)) {
                $matches = ((string) $row->is_nullable === 'YES') === $required['nullable'];
            }
            if ($matches && array_key_exists('default', $required)) {
                $matches = (string) $row->column_default === $required['default'];
            }
            if ($matches && array_key_exists('primary', $required)) {
                $matches = ((string) $row->column_key === 'PRI') === $required['primary'];
            }
            if ($matches && array_key_exists('auto_increment', $required)) {
                $hasAutoIncrement = str_contains(strtolower((string) $row->extra), 'auto_increment');
                $matches = $hasAutoIncrement === $required['auto_increment'];
            }

            if (! $matches) {
                $invalid[] = $required['table'].'.'.$required['column'];
            }
        }

        return $invalid;
    }

    /**
     * @return list<string>
     */
    private function invalidUniqueIndexes(): array
    {
        return $this->invalidIndexes(self::REQUIRED_UNIQUE_INDEXES, true);
    }

    /**
     * @return list<string>
     */
    private function invalidNonUniqueIndexes(): array
    {
        return $this->invalidIndexes(self::REQUIRED_NON_UNIQUE_INDEXES, false);
    }

    /**
     * @param  list<array{table: string, name: string, columns: list<string>}>  $requiredIndexes
     * @return list<string>
     */
    private function invalidIndexes(array $requiredIndexes, bool $mustBeUnique): array
    {
        $metadata = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->whereIn('table_name', array_column($requiredIndexes, 'table'))
            ->whereIn('index_name', array_column($requiredIndexes, 'name'))
            ->orderBy('table_name')
            ->orderBy('index_name')
            ->orderBy('seq_in_index')
            ->get([
                'TABLE_NAME as table_name',
                'INDEX_NAME as index_name',
                'COLUMN_NAME as column_name',
                'SEQ_IN_INDEX as sequence',
                'NON_UNIQUE as non_unique',
            ])
            ->groupBy(static fn (object $row): string => $row->table_name.'|'.$row->index_name);

        $invalid = [];
        foreach ($requiredIndexes as $required) {
            $key = $required['table'].'|'.$required['name'];
            $rows = $metadata->get($key, collect());
            $columns = $rows
                ->sortBy(static fn (object $row): int => (int) $row->sequence)
                ->map(static fn (object $row): string => (string) $row->column_name)
                ->values()
                ->all();
            $uniquenessMatches = $rows->isNotEmpty()
                && $rows->every(
                    static fn (object $row): bool => ((int) $row->non_unique === 0) === $mustBeUnique,
                );

            if ($columns !== $required['columns'] || ! $uniquenessMatches) {
                $invalid[] = $required['table'].'.'.$required['name'];
            }
        }

        return $invalid;
    }

    /**
     * @return list<string>
     */
    private function invalidForeignKeys(): array
    {
        $metadata = DB::table('information_schema.key_column_usage as kcu')
            ->join('information_schema.referential_constraints as rc', function (JoinClause $join): void {
                $join->on('rc.CONSTRAINT_SCHEMA', '=', 'kcu.CONSTRAINT_SCHEMA')
                    ->on('rc.TABLE_NAME', '=', 'kcu.TABLE_NAME')
                    ->on('rc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME');
            })
            ->where('kcu.CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->whereIn('kcu.TABLE_NAME', array_column(self::REQUIRED_FOREIGN_KEYS, 'table'))
            ->whereIn('kcu.CONSTRAINT_NAME', array_column(self::REQUIRED_FOREIGN_KEYS, 'name'))
            ->whereNotNull('kcu.REFERENCED_TABLE_NAME')
            ->orderBy('kcu.TABLE_NAME')
            ->orderBy('kcu.CONSTRAINT_NAME')
            ->orderBy('kcu.ORDINAL_POSITION')
            ->get([
                'kcu.TABLE_NAME as table_name',
                'kcu.CONSTRAINT_NAME as constraint_name',
                'kcu.COLUMN_NAME as column_name',
                'kcu.REFERENCED_TABLE_NAME as referenced_table_name',
                'kcu.REFERENCED_COLUMN_NAME as referenced_column_name',
                'kcu.ORDINAL_POSITION as ordinal_position',
                'rc.DELETE_RULE as delete_rule',
            ])
            ->groupBy(static fn (object $row): string => $row->table_name.'|'.$row->constraint_name);

        $invalid = [];
        foreach (self::REQUIRED_FOREIGN_KEYS as $required) {
            $key = $required['table'].'|'.$required['name'];
            $rows = $metadata->get($key, collect());
            $row = $rows->first();

            if ($rows->count() !== 1
                || $row === null
                || (string) $row->column_name !== $required['column']
                || (string) $row->referenced_table_name !== $required['target_table']
                || (string) $row->referenced_column_name !== $required['target_column']
                || strtoupper((string) $row->delete_rule) !== $required['delete_rule']) {
                $invalid[] = $required['table'].'.'.$required['name'];
            }
        }

        return $invalid;
    }

    private function canonicalPath(string $path): string
    {
        $resolved = $path !== '' ? realpath($path) : false;
        $normalized = str_replace('\\', '/', $resolved !== false ? $resolved : $path);

        return strtolower(rtrim($normalized, '/'));
    }

    private function record(string $name, string $status, string $message, bool $required): void
    {
        $this->checks[] = compact('name', 'status', 'message', 'required');
    }

    private function count(string $status): int
    {
        return collect($this->checks)->where('status', $status)->count();
    }
}
