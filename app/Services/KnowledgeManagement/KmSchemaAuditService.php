<?php

namespace App\Services\KnowledgeManagement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use JsonException;
use RuntimeException;
use Throwable;

final class KmSchemaAuditService
{
    public const MANIFEST_VERSION = 1;

    private const REQUIRED_COLUMNS = [
        'km_kategoris' => [
            'id', 'nama_kategori', 'poin_kategori', 'created_at', 'updated_at',
        ],
        'km_pengajuans' => [
            'id', 'id_user', 'id_km_kategori', 'judul', 'keterangan', 'posisi',
            'image', 'file', 'file_name', 'persetujuan', 'status', 'created_at',
            'updated_at', 'modified_by',
        ],
        'km_transaksis' => [
            'id', 'id_km_pengajuan', 'id_user', 'poin', 'level', 'status',
            'created_at', 'updated_at', 'modified_by',
        ],
        'km_lihat_bukus' => [
            'id', 'id_km_transaksi', 'id_km_pengajuan', 'jumlah_lihat',
            'created_at', 'updated_at',
        ],
        'km_sukas' => [
            'id', 'id_user', 'id_km_pengajuan', 'jumlah_like', 'created_at', 'updated_at',
        ],
        'km_insights' => [
            'id', 'id_user', 'id_km_pengajuan', 'content', 'created_at', 'updated_at',
        ],
    ];

    private const USER_REFERENCE_COLUMNS = [
        'km_pengajuans.id_user',
        'km_transaksis.id_user',
        'km_transaksis.modified_by',
        'km_sukas.id_user',
        'km_insights.id_user',
    ];

    /**
     * Named indexes installed by the KM migrations. An existing name is only
     * safe when its ordered columns and uniqueness match this contract.
     */
    private const EXPECTED_INDEXES = [
        ['km_pengajuans', 'km_pengajuans_status_posisi_index', ['status', 'posisi'], false],
        ['km_pengajuans', 'km_pengajuans_user_status_index', ['id_user', 'status'], false],
        ['km_pengajuans', 'km_pengajuans_category_index', ['id_km_kategori'], false],
        ['km_transaksis', 'km_transaksis_user_document_unique', ['id_user', 'id_km_pengajuan'], true],
        ['km_transaksis', 'km_transaksis_status_completed_at_index', ['status', 'completed_at'], false],
        ['km_transaksis', 'km_transaksis_document_index', ['id_km_pengajuan'], false],
        ['km_transaksis', 'km_transaksis_modified_by_index', ['modified_by'], false],
        ['km_sukas', 'km_sukas_user_document_unique', ['id_user', 'id_km_pengajuan'], true],
        ['km_sukas', 'km_sukas_document_index', ['id_km_pengajuan'], false],
        ['km_lihat_bukus', 'km_lihat_bukus_document_unique', ['id_km_pengajuan'], true],
        ['km_lihat_bukus', 'km_lihat_bukus_transaction_index', ['id_km_transaksi'], false],
        ['km_insights', 'km_insights_user_index', ['id_user'], false],
        ['km_insights', 'km_insights_document_index', ['id_km_pengajuan'], false],
        ['km_approval_events', 'PRIMARY', ['id'], true],
        ['km_approval_events', 'km_approval_events_document_acted_at_index', ['km_pengajuan_id', 'acted_at'], false],
        ['km_approval_events', 'km_approval_events_actor_acted_at_index', ['actor_id', 'acted_at'], false],
        ['km_pengajuans', 'km_pengajuans_file_checksum_index', ['file_checksum_sha256'], false],
        ['km_pengajuans', 'km_pengajuans_file_migrated_at_index', ['file_migrated_at'], false],
    ];

    /**
     * Named foreign keys installed by the KM migrations.
     */
    private const EXPECTED_FOREIGN_KEYS = [
        ['km_pengajuans', 'km_pengajuans_user_foreign', ['id_user'], 'users', ['id'], 'SET NULL'],
        ['km_pengajuans', 'km_pengajuans_category_foreign', ['id_km_kategori'], 'km_kategoris', ['id'], 'SET NULL'],
        ['km_transaksis', 'km_transaksis_user_foreign', ['id_user'], 'users', ['id'], 'CASCADE'],
        ['km_transaksis', 'km_transaksis_document_foreign', ['id_km_pengajuan'], 'km_pengajuans', ['id'], 'CASCADE'],
        ['km_transaksis', 'km_transaksis_modified_by_foreign', ['modified_by'], 'users', ['id'], 'SET NULL'],
        ['km_sukas', 'km_sukas_user_foreign', ['id_user'], 'users', ['id'], 'CASCADE'],
        ['km_sukas', 'km_sukas_document_foreign', ['id_km_pengajuan'], 'km_pengajuans', ['id'], 'CASCADE'],
        ['km_insights', 'km_insights_user_foreign', ['id_user'], 'users', ['id'], 'CASCADE'],
        ['km_insights', 'km_insights_document_foreign', ['id_km_pengajuan'], 'km_pengajuans', ['id'], 'CASCADE'],
        ['km_lihat_bukus', 'km_lihat_bukus_document_foreign', ['id_km_pengajuan'], 'km_pengajuans', ['id'], 'CASCADE'],
        ['km_lihat_bukus', 'km_lihat_bukus_transaction_foreign', ['id_km_transaksi'], 'km_transaksis', ['id'], 'SET NULL'],
        ['km_approval_events', 'km_approval_events_document_foreign', ['km_pengajuan_id'], 'km_pengajuans', ['id'], 'RESTRICT'],
        ['km_approval_events', 'km_approval_events_actor_foreign', ['actor_id'], 'users', ['id'], 'SET NULL'],
    ];

    /**
     * The action describes the deterministic repair permitted by the mission.
     */
    private const ORPHAN_RELATIONS = [
        'km_pengajuans.id_user' => ['km_pengajuans', 'id_user', 'users', 'set_null'],
        'km_pengajuans.id_km_kategori' => ['km_pengajuans', 'id_km_kategori', 'km_kategoris', 'set_null'],
        'km_transaksis.id_user' => ['km_transaksis', 'id_user', 'users', 'delete'],
        'km_transaksis.id_km_pengajuan' => ['km_transaksis', 'id_km_pengajuan', 'km_pengajuans', 'delete'],
        'km_transaksis.modified_by' => ['km_transaksis', 'modified_by', 'users', 'set_null'],
        'km_sukas.id_user' => ['km_sukas', 'id_user', 'users', 'delete'],
        'km_sukas.id_km_pengajuan' => ['km_sukas', 'id_km_pengajuan', 'km_pengajuans', 'delete'],
        'km_insights.id_user' => ['km_insights', 'id_user', 'users', 'delete'],
        'km_insights.id_km_pengajuan' => ['km_insights', 'id_km_pengajuan', 'km_pengajuans', 'delete'],
        'km_lihat_bukus.id_km_pengajuan' => ['km_lihat_bukus', 'id_km_pengajuan', 'km_pengajuans', 'delete'],
        'km_lihat_bukus.id_km_transaksi' => ['km_lihat_bukus', 'id_km_transaksi', 'km_transaksis', 'set_null'],
    ];

    public function audit(): array
    {
        $connection = DB::connection();

        return $connection->transaction(function () use ($connection): array {
            $driver = $connection->getDriverName();
            $database = (string) $connection->getDatabaseName();
            $report = [
                'manifest_version' => self::MANIFEST_VERSION,
                'generated_at' => now()->toIso8601String(),
                'connection' => $connection->getName(),
                'driver' => $driver,
                'database' => $database,
                'required_schema' => self::REQUIRED_COLUMNS,
                'schema' => [],
                'findings' => $this->emptyFindings(),
            ];

            if ($driver !== 'mysql') {
                $report['findings']['unsupported_driver'][] = $driver;
                $report['database_checksum'] = [
                    'algorithm' => 'sha256',
                    'value' => hash('sha256', $driver.':'.$database),
                    'tables' => [],
                ];
                $report['summary'] = $this->summarize($report);

                return $report;
            }

            foreach (self::REQUIRED_COLUMNS as $table => $requiredColumns) {
                if (! Schema::hasTable($table)) {
                    $report['findings']['missing_tables'][] = $table;

                    continue;
                }

                $metadata = $this->tableMetadata($table);
                $report['schema'][$table] = $metadata;
                $actualColumns = array_column($metadata['columns'], 'name');
                $missingColumns = array_values(array_diff($requiredColumns, $actualColumns));

                if ($missingColumns !== []) {
                    $report['findings']['missing_columns'][$table] = $missingColumns;
                }

                if (strtolower((string) ($metadata['engine'] ?? '')) !== 'innodb') {
                    $report['findings']['unsupported_engines'][$table] = $metadata['engine'];
                }

                $primaryColumns = $metadata['indexes']['PRIMARY']['columns'] ?? [];
                if ($primaryColumns !== [] && $primaryColumns !== ['id']) {
                    $report['findings']['invalid_primary_keys'][$table] = $primaryColumns;
                }

                $idColumn = collect($metadata['columns'])->firstWhere('name', 'id');
                if ($idColumn !== null && ! in_array($idColumn['data_type'], ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'], true)) {
                    $report['findings']['invalid_id_types'][$table] = $idColumn['column_type'];
                }
            }

            if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'id')) {
                $report['findings']['missing_parent_schema'][] = 'users.id';
            } else {
                $usersMetadata = $this->tableMetadata('users');
                if (($usersMetadata['indexes']['PRIMARY']['columns'] ?? []) !== ['id']) {
                    $report['findings']['missing_parent_schema'][] = 'users.id PRIMARY KEY';
                }
            }

            $this->auditReferenceTypes($report);
            $this->auditConstraintShapes($report);

            foreach (array_keys(self::REQUIRED_COLUMNS) as $table) {
                if (! $this->hasRequiredColumns($table, ['id'])) {
                    continue;
                }

                $report['findings']['duplicate_primary_ids'][$table] = $this->duplicatePrimaryIds($table);
                $report['findings']['null_primary_ids'][$table] = DB::table($table)->whereNull('id')->count();
            }

            if ($this->hasRequiredColumns('km_transaksis', ['id', 'id_user', 'id_km_pengajuan'])) {
                $report['findings']['duplicate_transactions'] = $this->duplicateGroups(
                    'km_transaksis',
                    ['id_user', 'id_km_pengajuan']
                );
            }

            if ($this->hasRequiredColumns('km_sukas', ['id', 'id_user', 'id_km_pengajuan'])) {
                $report['findings']['duplicate_likes'] = $this->duplicateGroups(
                    'km_sukas',
                    ['id_user', 'id_km_pengajuan']
                );
            }

            if ($this->hasRequiredColumns('km_lihat_bukus', ['id', 'id_km_pengajuan', 'jumlah_lihat'])) {
                $report['findings']['duplicate_view_counters'] = $this->duplicateGroups(
                    'km_lihat_bukus',
                    ['id_km_pengajuan']
                );
                $report['findings']['invalid_view_counters'] = $this->invalidNumericRows(
                    'km_lihat_bukus',
                    'jumlah_lihat'
                );
            }

            foreach (self::USER_REFERENCE_COLUMNS as $qualifiedColumn) {
                [$table, $column] = explode('.', $qualifiedColumn, 2);
                if ($this->hasRequiredColumns($table, ['id', $column])) {
                    $report['findings']['invalid_user_references'][$qualifiedColumn] = $this->invalidNumericRows(
                        $table,
                        $column,
                        $table === 'km_pengajuans' ? ['id', $column] : ['*']
                    );
                }
            }

            foreach (self::ORPHAN_RELATIONS as $name => [$table, $column, $parentTable, $action]) {
                if (! $this->hasRequiredColumns($table, ['id', $column])
                    || ! $this->hasRequiredColumns($parentTable, ['id'])) {
                    continue;
                }

                $columns = $table === 'km_pengajuans' ? ['id', $column] : ['*'];
                $report['findings']['orphans'][$name] = [
                    'action' => $action,
                    'rows' => $this->orphanRows($table, $column, $parentTable, $columns),
                ];
            }

            $report['findings']['transaction_view_dependencies'] = $this->transactionViewDependencies($report);

            $report['database_checksum'] = $this->fingerprint();
            $report['summary'] = $this->summarize($report);

            return $report;
        }, 1);
    }

    public function assertReadyForHardening(?array $report = null): void
    {
        $report ??= $this->audit();

        if ($this->isSafeForHardening($report)) {
            return;
        }

        $blocking = collect($report['summary']['blocking_counts'] ?? [])
            ->filter(fn (int $count): bool => $count > 0)
            ->map(fn (int $count, string $name): string => "{$name}={$count}")
            ->implode(', ');
        $constraintDrift = array_keys($report['findings']['constraint_shape_mismatches'] ?? []);
        $constraintMessage = $constraintDrift === []
            ? ''
            : ' Unsafe named constraint definitions: '.implode(', ', $constraintDrift)
                .'. Reconcile each name with the expected ordered columns, uniqueness, reference target, '
                .'and delete rule recorded in the audit manifest before retrying';

        throw new RuntimeException(
            'KM schema hardening preflight failed'.($blocking !== '' ? ": {$blocking}" : '')
            .$constraintMessage
            .'. Run `php artisan km:audit-schema --write-manifest --strict`, repair the reported data '
            .'with `php artisan km:repair-schema <manifest> --apply`, then retry the migration.'
        );
    }

    public function isSafeForHardening(array $report): bool
    {
        return (bool) ($report['summary']['safe_for_hardening'] ?? false);
    }

    /**
     * Produce a content hash without persisting business data in the manifest.
     */
    public function fingerprint(): array
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('KM schema fingerprinting is only supported on MySQL.');
        }

        $tables = [];

        foreach (array_keys(self::REQUIRED_COLUMNS) as $table) {
            if (! Schema::hasTable($table)) {
                $tables[$table] = null;

                continue;
            }

            $metadata = $this->tableMetadata($table);
            $rowHashes = [];
            foreach (DB::table($table)->orderBy('id')->cursor() as $row) {
                $rowHashes[] = hash('sha256', $this->canonicalJson((array) $row));
            }
            sort($rowHashes, SORT_STRING);

            $tables[$table] = hash('sha256', $this->canonicalJson([
                'columns' => $metadata['columns'],
                'indexes' => $metadata['indexes'],
                'foreign_keys' => $metadata['foreign_keys'],
                'rows' => $rowHashes,
            ]));
        }

        return [
            'algorithm' => 'sha256',
            'value' => hash('sha256', $this->canonicalJson($tables)),
            'tables' => $tables,
        ];
    }

    public function writeManifest(array $report, ?string $path = null): string
    {
        $path ??= storage_path(
            'app/private/km/schema-audits/'.now()->format('Ymd_His_u').'.json'
        );

        $this->writeJsonAtomically($report, $path);

        return $path;
    }

    /**
     * Persist mutable repair state beside the immutable audit manifest.
     *
     * The previous valid journal is copied to a backup before the primary is
     * atomically replaced. A process crash can therefore leave either the old
     * primary or its backup, but never requires rewriting the audit evidence.
     */
    public function writeRepairJournal(array $journal, string $path): string
    {
        File::ensureDirectoryExists(dirname($path), 0750, true);

        if (File::isFile($path)) {
            $current = File::get($path);
            $this->writeBytesAtomically($current, $path.'.bak');
        }

        $this->writeJsonAtomically($journal, $path);

        return $path;
    }

    private function writeJsonAtomically(array $payload, string $path): void
    {
        File::ensureDirectoryExists(dirname($path), 0750, true);
        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ).PHP_EOL;

        $this->writeBytesAtomically($json, $path);
    }

    private function writeBytesAtomically(string $contents, string $path): void
    {
        try {
            File::replace($path, $contents, 0640);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to atomically write KM manifest state: {$path}",
                previous: $exception,
            );
        }

        if (! File::isFile($path)) {
            throw new RuntimeException("Atomic KM manifest state is missing after write: {$path}");
        }

        $persisted = File::get($path);
        if (! hash_equals(hash('sha256', $contents), hash('sha256', $persisted))) {
            throw new RuntimeException("Atomic KM manifest state failed verification: {$path}");
        }
    }

    public function hasIndex(string $table, string $index): bool
    {
        return $this->indexDefinition($table, $index) !== null;
    }

    public function hasForeignKey(string $table, string $constraint): bool
    {
        return $this->foreignKeyDefinition($table, $constraint) !== null;
    }

    /**
     * @return array{table: string, name: string, data_type: string, column_type: string, nullable: bool, default: mixed, extra: string}|null
     */
    public function columnDefinition(string $table, string $column): ?array
    {
        if (! Schema::hasTable($table) || DB::connection()->getDriverName() !== 'mysql') {
            return null;
        }

        $metadata = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first(['DATA_TYPE', 'COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT', 'EXTRA']);

        if ($metadata === null) {
            return null;
        }

        return [
            'table' => $table,
            'name' => $column,
            'data_type' => strtolower((string) $metadata->DATA_TYPE),
            'column_type' => strtolower((string) $metadata->COLUMN_TYPE),
            'nullable' => $metadata->IS_NULLABLE === 'YES',
            'default' => $metadata->COLUMN_DEFAULT,
            'extra' => strtolower((string) $metadata->EXTRA),
        ];
    }

    /**
     * @return array{table: string, name: string, columns: list<string>, unique: bool}|null
     */
    public function indexDefinition(string $table, string $index): ?array
    {
        if (! Schema::hasTable($table) || DB::connection()->getDriverName() !== 'mysql') {
            return null;
        }

        $rows = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->orderBy('SEQ_IN_INDEX')
            ->get(['COLUMN_NAME', 'NON_UNIQUE']);

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'table' => $table,
            'name' => $index,
            'columns' => $rows->pluck('COLUMN_NAME')->map(fn (mixed $column): string => (string) $column)->all(),
            'unique' => (int) $rows->first()->NON_UNIQUE === 0,
        ];
    }

    /**
     * @return array{table: string, name: string, columns: list<string>, references_table: string, references_columns: list<string>, delete_rule: string}|null
     */
    public function foreignKeyDefinition(string $table, string $constraint): ?array
    {
        if (! Schema::hasTable($table) || DB::connection()->getDriverName() !== 'mysql') {
            return null;
        }

        $rows = DB::select(
            <<<'SQL'
                SELECT
                    kcu.COLUMN_NAME,
                    kcu.REFERENCED_TABLE_NAME,
                    kcu.REFERENCED_COLUMN_NAME,
                    rc.DELETE_RULE
                FROM information_schema.KEY_COLUMN_USAGE kcu
                JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                  ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                 AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                 AND rc.TABLE_NAME = kcu.TABLE_NAME
                WHERE kcu.CONSTRAINT_SCHEMA = ?
                  AND kcu.TABLE_NAME = ?
                  AND kcu.CONSTRAINT_NAME = ?
                  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY kcu.ORDINAL_POSITION
                SQL,
            [DB::connection()->getDatabaseName(), $table, $constraint]
        );

        if ($rows === []) {
            return null;
        }

        return [
            'table' => $table,
            'name' => $constraint,
            'columns' => array_map(fn (object $row): string => (string) $row->COLUMN_NAME, $rows),
            'references_table' => (string) $rows[0]->REFERENCED_TABLE_NAME,
            'references_columns' => array_map(
                fn (object $row): string => (string) $row->REFERENCED_COLUMN_NAME,
                $rows
            ),
            'delete_rule' => strtoupper((string) $rows[0]->DELETE_RULE),
        ];
    }

    public function hardeningConstraintsInstalled(): bool
    {
        foreach ([
            ['km_transaksis', 'km_transaksis_user_document_unique'],
            ['km_sukas', 'km_sukas_user_document_unique'],
            ['km_lihat_bukus', 'km_lihat_bukus_document_unique'],
        ] as [$table, $index]) {
            if ($this->hasIndex($table, $index)) {
                return true;
            }
        }

        foreach ([
            ['km_pengajuans', 'km_pengajuans_user_foreign'],
            ['km_transaksis', 'km_transaksis_user_foreign'],
            ['km_lihat_bukus', 'km_lihat_bukus_document_foreign'],
        ] as [$table, $foreignKey]) {
            if ($this->hasForeignKey($table, $foreignKey)) {
                return true;
            }
        }

        return false;
    }

    public function requiredColumns(): array
    {
        return self::REQUIRED_COLUMNS;
    }

    private function emptyFindings(): array
    {
        return [
            'unsupported_driver' => [],
            'missing_tables' => [],
            'missing_columns' => [],
            'missing_parent_schema' => [],
            'unsupported_engines' => [],
            'invalid_primary_keys' => [],
            'invalid_id_types' => [],
            'incompatible_reference_types' => [],
            'constraint_shape_mismatches' => [],
            'duplicate_primary_ids' => [],
            'null_primary_ids' => [],
            'duplicate_transactions' => [],
            'duplicate_likes' => [],
            'duplicate_view_counters' => [],
            'invalid_view_counters' => [],
            'invalid_user_references' => [],
            'orphans' => [],
            'transaction_view_dependencies' => [],
        ];
    }

    private function summarize(array $report): array
    {
        $findings = $report['findings'];
        $counts = [
            'unsupported_driver' => count($findings['unsupported_driver']),
            'missing_tables' => count($findings['missing_tables']),
            'missing_columns' => $this->nestedCount($findings['missing_columns']),
            'missing_parent_schema' => count($findings['missing_parent_schema']),
            'unsupported_engines' => count($findings['unsupported_engines']),
            'invalid_primary_keys' => count($findings['invalid_primary_keys']),
            'invalid_id_types' => count($findings['invalid_id_types']),
            'incompatible_reference_types' => count($findings['incompatible_reference_types']),
            'constraint_shape_mismatches' => count($findings['constraint_shape_mismatches']),
            'duplicate_primary_ids' => $this->nestedCount($findings['duplicate_primary_ids']),
            'null_primary_ids' => array_sum(array_map('intval', $findings['null_primary_ids'])),
            'duplicate_transactions' => count($findings['duplicate_transactions']),
            'duplicate_likes' => count($findings['duplicate_likes']),
            'duplicate_view_counters' => count($findings['duplicate_view_counters']),
            'invalid_view_counters' => count($findings['invalid_view_counters']),
            'invalid_user_references' => $this->nestedCount($findings['invalid_user_references']),
            'orphan_references' => collect($findings['orphans'])
                ->sum(fn (array $finding): int => count($finding['rows'] ?? [])),
        ];

        return [
            'safe_for_hardening' => array_sum($counts) === 0,
            'blocking_counts' => $counts,
            'blocking_total' => array_sum($counts),
        ];
    }

    private function nestedCount(array $items): int
    {
        return collect($items)->sum(function (mixed $item): int {
            if (! is_array($item)) {
                return (int) $item;
            }

            return array_is_list($item) ? count($item) : $this->nestedCount($item);
        });
    }

    private function duplicatePrimaryIds(string $table): array
    {
        return DB::table($table)
            ->select('id', DB::raw('COUNT(*) AS duplicate_count'))
            ->whereNotNull('id')
            ->groupBy('id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => [
                'id' => $row->id,
                'count' => (int) $row->duplicate_count,
            ])
            ->all();
    }

    private function duplicateGroups(string $table, array $columns): array
    {
        $query = DB::table($table)
            ->select([...$columns, DB::raw('COUNT(*) AS duplicate_count')]);

        foreach ($columns as $column) {
            $query->whereNotNull($column);
        }

        $groups = $query
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1')
            ->orderBy($columns[0])
            ->get();

        return $groups->map(function (object $group) use ($table, $columns): array {
            $rows = DB::table($table);
            $key = [];

            foreach ($columns as $column) {
                $value = $group->{$column};
                $key[$column] = $value;
                $rows->where($column, $value);
            }

            return [
                'key' => $key,
                'count' => (int) $group->duplicate_count,
                'rows' => $rows->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all(),
            ];
        })->all();
    }

    private function invalidNumericRows(
        string $table,
        string $column,
        array $columns = ['*']
    ): array {
        $qualifiedColumns = $columns === ['*'] ? ["{$table}.*"] : $columns;

        return DB::table($table)
            ->select($qualifiedColumns)
            ->whereNotNull($column)
            ->whereRaw(
                "(TRIM(CAST(`{$column}` AS CHAR)) NOT REGEXP '^[0-9]+$' "
                ."OR CAST(`{$column}` AS DECIMAL(65, 0)) < 0 "
                ."OR CAST(`{$column}` AS DECIMAL(65, 0)) > 18446744073709551615)"
            )
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    private function orphanRows(
        string $table,
        string $column,
        string $parentTable,
        array $columns
    ): array {
        $selection = $columns === ['*']
            ? ['child.*']
            : array_map(fn (string $selected): string => "child.{$selected}", $columns);

        return DB::table("{$table} AS child")
            ->leftJoin("{$parentTable} AS parent", "child.{$column}", '=', 'parent.id')
            ->whereNotNull("child.{$column}")
            ->whereNull('parent.id')
            ->select($selection)
            ->orderBy('child.id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    private function auditReferenceTypes(array &$report): void
    {
        $expectedTypes = [
            'users.id' => 'bigint unsigned',
            'km_kategoris.id' => 'bigint',
            'km_pengajuans.id' => 'int',
            'km_pengajuans.id_km_kategori' => 'bigint',
            'km_transaksis.id' => 'int',
            'km_transaksis.id_km_pengajuan' => 'int',
            'km_lihat_bukus.id' => 'bigint',
            'km_lihat_bukus.id_km_transaksi' => 'int',
            'km_lihat_bukus.id_km_pengajuan' => 'int',
            'km_sukas.id' => 'bigint',
            'km_sukas.id_km_pengajuan' => 'int',
            'km_insights.id' => 'int',
            'km_insights.id_km_pengajuan' => 'int',
        ];

        foreach ($expectedTypes as $qualifiedColumn => $expected) {
            [$table, $column] = explode('.', $qualifiedColumn, 2);
            if (! $this->hasRequiredColumns($table, [$column])) {
                continue;
            }

            $actual = $this->integerColumnType($table, $column);
            if ($actual !== $expected) {
                $report['findings']['incompatible_reference_types'][$qualifiedColumn] = [
                    'expected' => $expected,
                    'actual' => $actual,
                ];
            }
        }
    }

    private function auditConstraintShapes(array &$report): void
    {
        foreach (self::EXPECTED_INDEXES as [$table, $name, $columns, $unique]) {
            $actual = $this->indexDefinition($table, $name);
            if ($actual === null) {
                continue;
            }

            $expected = [
                'table' => $table,
                'name' => $name,
                'columns' => $columns,
                'unique' => $unique,
            ];
            if ($actual !== $expected) {
                $report['findings']['constraint_shape_mismatches']["index:{$table}.{$name}"] = [
                    'kind' => 'index',
                    'expected' => $expected,
                    'actual' => $actual,
                ];
            }
        }

        foreach (
            self::EXPECTED_FOREIGN_KEYS as [$table, $name, $columns, $parent, $parentColumns, $deleteRule]
        ) {
            $actual = $this->foreignKeyDefinition($table, $name);
            if ($actual === null) {
                continue;
            }

            $expected = [
                'table' => $table,
                'name' => $name,
                'columns' => $columns,
                'references_table' => $parent,
                'references_columns' => $parentColumns,
                'delete_rule' => $deleteRule,
            ];
            if ($actual !== $expected) {
                $report['findings']['constraint_shape_mismatches']["foreign_key:{$table}.{$name}"] = [
                    'kind' => 'foreign_key',
                    'expected' => $expected,
                    'actual' => $actual,
                ];
            }
        }
    }

    private function integerColumnType(string $table, string $column): ?string
    {
        $columnType = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->value('COLUMN_TYPE');

        if ($columnType === null) {
            return null;
        }

        return preg_replace('/\(\d+\)/', '', strtolower((string) $columnType));
    }

    private function transactionViewDependencies(array $report): array
    {
        if (! $this->hasRequiredColumns('km_lihat_bukus', ['id', 'id_km_transaksi'])
            || ! $this->hasRequiredColumns('km_transaksis', ['id'])) {
            return [];
        }

        $removedTransactionIds = [];
        foreach ($report['findings']['duplicate_transactions'] ?? [] as $group) {
            $ids = collect($group['rows'] ?? [])->pluck('id')->sort()->values();
            $removedTransactionIds = [...$removedTransactionIds, ...$ids->slice(1)->all()];
        }

        foreach (['km_transaksis.id_user', 'km_transaksis.id_km_pengajuan'] as $name) {
            $removedTransactionIds = [
                ...$removedTransactionIds,
                ...collect($report['findings']['invalid_user_references'][$name] ?? [])->pluck('id')->all(),
                ...collect($report['findings']['orphans'][$name]['rows'] ?? [])->pluck('id')->all(),
            ];
        }

        $removedTransactionIds = array_values(array_unique(
            array_filter($removedTransactionIds, fn (mixed $id): bool => $id !== null),
            SORT_REGULAR
        ));

        if ($removedTransactionIds === []) {
            return [];
        }

        return DB::table('km_lihat_bukus')
            ->whereIn('id_km_transaksi', $removedTransactionIds)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    private function hasRequiredColumns(string $table, array $columns): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function tableMetadata(string $table): array
    {
        $database = DB::connection()->getDatabaseName();
        $tableRow = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->first(['ENGINE', 'TABLE_COLLATION']);
        $columns = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->orderBy('ORDINAL_POSITION')
            ->get([
                'COLUMN_NAME', 'DATA_TYPE', 'COLUMN_TYPE', 'IS_NULLABLE',
                'COLUMN_DEFAULT', 'EXTRA', 'ORDINAL_POSITION',
            ])
            ->map(fn (object $column): array => [
                'name' => $column->COLUMN_NAME,
                'data_type' => strtolower($column->DATA_TYPE),
                'column_type' => strtolower($column->COLUMN_TYPE),
                'nullable' => $column->IS_NULLABLE === 'YES',
                'default' => $column->COLUMN_DEFAULT,
                'extra' => strtolower((string) $column->EXTRA),
                'position' => (int) $column->ORDINAL_POSITION,
            ])
            ->all();
        $indexRows = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->orderBy('INDEX_NAME')
            ->orderBy('SEQ_IN_INDEX')
            ->get(['INDEX_NAME', 'NON_UNIQUE', 'SEQ_IN_INDEX', 'COLUMN_NAME']);
        $indexes = [];
        foreach ($indexRows as $indexRow) {
            $name = $indexRow->INDEX_NAME;
            $indexes[$name] ??= [
                'unique' => (int) $indexRow->NON_UNIQUE === 0,
                'columns' => [],
            ];
            $indexes[$name]['columns'][] = $indexRow->COLUMN_NAME;
        }

        $foreignKeyRows = DB::select(
            <<<'SQL'
                SELECT
                    kcu.CONSTRAINT_NAME,
                    kcu.COLUMN_NAME,
                    kcu.REFERENCED_TABLE_NAME,
                    kcu.REFERENCED_COLUMN_NAME,
                    rc.UPDATE_RULE,
                    rc.DELETE_RULE
                FROM information_schema.KEY_COLUMN_USAGE kcu
                JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                  ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                 AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                 AND rc.TABLE_NAME = kcu.TABLE_NAME
                WHERE kcu.CONSTRAINT_SCHEMA = ?
                  AND kcu.TABLE_NAME = ?
                  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION
                SQL,
            [$database, $table]
        );
        $foreignKeys = collect($foreignKeyRows)
            ->map(fn (object $foreignKey): array => [
                'name' => $foreignKey->CONSTRAINT_NAME,
                'column' => $foreignKey->COLUMN_NAME,
                'references_table' => $foreignKey->REFERENCED_TABLE_NAME,
                'references_column' => $foreignKey->REFERENCED_COLUMN_NAME,
                'update_rule' => $foreignKey->UPDATE_RULE,
                'delete_rule' => $foreignKey->DELETE_RULE,
            ])
            ->all();

        return [
            'engine' => $tableRow?->ENGINE,
            'collation' => $tableRow?->TABLE_COLLATION,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ];
    }

    /**
     * @throws JsonException
     */
    private function canonicalJson(array $value): string
    {
        $normalized = $this->normalize($value);

        return json_encode(
            $normalized,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        );
    }

    private function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            if ($value === null || is_bool($value)) {
                return $value;
            }

            return (string) $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
        }

        ksort($value);

        return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
    }
}
