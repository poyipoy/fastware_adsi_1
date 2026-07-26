<?php

use App\Services\KnowledgeManagement\KmSchemaAuditService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'km_kategoris',
        'km_pengajuans',
        'km_transaksis',
        'km_lihat_bukus',
        'km_sukas',
        'km_insights',
    ];

    private const INDEXES = [
        ['km_pengajuans', ['status', 'posisi'], 'km_pengajuans_status_posisi_index', false],
        ['km_pengajuans', ['id_user', 'status'], 'km_pengajuans_user_status_index', false],
        ['km_pengajuans', ['id_km_kategori'], 'km_pengajuans_category_index', false],
        ['km_transaksis', ['id_user', 'id_km_pengajuan'], 'km_transaksis_user_document_unique', true],
        ['km_transaksis', ['status', 'completed_at'], 'km_transaksis_status_completed_at_index', false],
        ['km_transaksis', ['id_km_pengajuan'], 'km_transaksis_document_index', false],
        ['km_transaksis', ['modified_by'], 'km_transaksis_modified_by_index', false],
        ['km_sukas', ['id_user', 'id_km_pengajuan'], 'km_sukas_user_document_unique', true],
        ['km_sukas', ['id_km_pengajuan'], 'km_sukas_document_index', false],
        ['km_lihat_bukus', ['id_km_pengajuan'], 'km_lihat_bukus_document_unique', true],
        ['km_lihat_bukus', ['id_km_transaksi'], 'km_lihat_bukus_transaction_index', false],
        ['km_insights', ['id_user'], 'km_insights_user_index', false],
        ['km_insights', ['id_km_pengajuan'], 'km_insights_document_index', false],
    ];

    private const FOREIGN_KEYS = [
        ['km_pengajuans', 'id_user', 'users', 'id', 'km_pengajuans_user_foreign', 'null'],
        ['km_pengajuans', 'id_km_kategori', 'km_kategoris', 'id', 'km_pengajuans_category_foreign', 'null'],
        ['km_transaksis', 'id_user', 'users', 'id', 'km_transaksis_user_foreign', 'cascade'],
        ['km_transaksis', 'id_km_pengajuan', 'km_pengajuans', 'id', 'km_transaksis_document_foreign', 'cascade'],
        ['km_transaksis', 'modified_by', 'users', 'id', 'km_transaksis_modified_by_foreign', 'null'],
        ['km_sukas', 'id_user', 'users', 'id', 'km_sukas_user_foreign', 'cascade'],
        ['km_sukas', 'id_km_pengajuan', 'km_pengajuans', 'id', 'km_sukas_document_foreign', 'cascade'],
        ['km_insights', 'id_user', 'users', 'id', 'km_insights_user_foreign', 'cascade'],
        ['km_insights', 'id_km_pengajuan', 'km_pengajuans', 'id', 'km_insights_document_foreign', 'cascade'],
        ['km_lihat_bukus', 'id_km_pengajuan', 'km_pengajuans', 'id', 'km_lihat_bukus_document_foreign', 'cascade'],
        ['km_lihat_bukus', 'id_km_transaksi', 'km_transaksis', 'id', 'km_lihat_bukus_transaction_foreign', 'null'],
    ];

    public function up(): void
    {
        $this->assertMySql();

        /** @var KmSchemaAuditService $auditService */
        $auditService = app(KmSchemaAuditService::class);
        $auditService->assertReadyForHardening();

        foreach (self::TABLES as $table) {
            $this->ensurePrimaryKeyAndAutoIncrement($table);
        }

        foreach ([
            ['km_pengajuans', 'id_user'],
            ['km_transaksis', 'id_user'],
            ['km_transaksis', 'modified_by'],
            ['km_sukas', 'id_user'],
            ['km_insights', 'id_user'],
        ] as [$table, $column]) {
            $this->normalizeUnsignedBigIntegerReference($table, $column);
        }

        // MySQL rejects ON DELETE SET NULL when the child column is NOT NULL.
        // Legacy snapshots may have the correct signed type but stricter
        // nullability, so normalize these two explicitly before adding FKs.
        $this->normalizeNullableIntegerReference('km_pengajuans', 'id_km_kategori', 'BIGINT');
        $this->normalizeNullableIntegerReference('km_lihat_bukus', 'id_km_transaksi', 'INT');

        DB::table('km_lihat_bukus')->whereNull('jumlah_lihat')->update(['jumlah_lihat' => 0]);
        DB::statement(
            'ALTER TABLE `km_lihat_bukus` MODIFY `jumlah_lihat` BIGINT UNSIGNED NOT NULL DEFAULT 0'
        );

        Schema::table('km_transaksis', function (Blueprint $table): void {
            if (! Schema::hasColumn('km_transaksis', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('km_transaksis', 'points_awarded_at')) {
                $table->timestamp('points_awarded_at')->nullable()->after('completed_at');
            }
        });

        DB::statement(
            'UPDATE `km_transaksis` '
            .'SET `completed_at` = COALESCE(`completed_at`, `updated_at`, `created_at`), '
            .'`points_awarded_at` = COALESCE(`points_awarded_at`, `updated_at`, `created_at`) '
            .'WHERE `status` = 3'
        );

        foreach (self::INDEXES as [$table, $columns, $name, $unique]) {
            $this->addIndex($auditService, $table, $columns, $name, $unique);
        }

        foreach (self::FOREIGN_KEYS as [$table, $column, $parent, $parentColumn, $name, $delete]) {
            $this->addForeignKey(
                $auditService,
                $table,
                $column,
                $parent,
                $parentColumn,
                $name,
                $delete
            );
        }
    }

    public function down(): void
    {
        $this->guardTestingRollback();
        $this->assertMySql();

        /** @var KmSchemaAuditService $auditService */
        $auditService = app(KmSchemaAuditService::class);

        foreach (array_reverse(self::FOREIGN_KEYS) as [$table, , , , $name]) {
            if ($auditService->hasForeignKey($table, $name)) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
            }
        }

        foreach (array_reverse(self::INDEXES) as [$table, , $name]) {
            if ($auditService->hasIndex($table, $name)) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
            }
        }

        if (Schema::hasColumn('km_transaksis', 'points_awarded_at')) {
            Schema::table('km_transaksis', function (Blueprint $table): void {
                $table->dropColumn('points_awarded_at');
            });
        }

        if (Schema::hasColumn('km_transaksis', 'completed_at')) {
            Schema::table('km_transaksis', function (Blueprint $table): void {
                $table->dropColumn('completed_at');
            });
        }

        if (Schema::hasColumn('km_lihat_bukus', 'jumlah_lihat')) {
            DB::statement(
                'ALTER TABLE `km_lihat_bukus` MODIFY `jumlah_lihat` VARCHAR(255) NULL DEFAULT NULL'
            );
        }
    }

    private function ensurePrimaryKeyAndAutoIncrement(string $table): void
    {
        $database = DB::connection()->getDatabaseName();
        $primaryColumns = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', 'PRIMARY')
            ->orderBy('SEQ_IN_INDEX')
            ->pluck('COLUMN_NAME')
            ->all();

        if ($primaryColumns !== [] && $primaryColumns !== ['id']) {
            throw new RuntimeException(
                "Cannot harden {$table}: its existing primary key is not the single id column."
            );
        }

        if ($primaryColumns === []) {
            DB::statement("ALTER TABLE `{$table}` ADD PRIMARY KEY (`id`)");
        }

        $id = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', 'id')
            ->first(['COLUMN_TYPE', 'EXTRA']);

        if ($id === null) {
            throw new RuntimeException("Cannot harden {$table}: id column is missing.");
        }

        if (str_contains(strtolower((string) $id->EXTRA), 'auto_increment')) {
            return;
        }

        $columnType = strtolower((string) $id->COLUMN_TYPE);
        if (preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)(\(\d+\))?( unsigned)?$/', $columnType) !== 1) {
            throw new RuntimeException("Cannot safely add AUTO_INCREMENT to {$table}.id of type {$columnType}.");
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `id` {$columnType} NOT NULL AUTO_INCREMENT");
    }

    private function normalizeUnsignedBigIntegerReference(string $table, string $column): void
    {
        $database = DB::connection()->getDatabaseName();
        $metadata = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first(['COLUMN_TYPE', 'IS_NULLABLE']);

        if ($metadata === null) {
            throw new RuntimeException("Cannot harden {$table}: {$column} column is missing.");
        }

        if (strtolower((string) $metadata->COLUMN_TYPE) === 'bigint unsigned'
            && $metadata->IS_NULLABLE === 'YES') {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NULL DEFAULT NULL"
        );
    }

    private function normalizeNullableIntegerReference(
        string $table,
        string $column,
        string $expectedType,
    ): void {
        $database = DB::connection()->getDatabaseName();
        $metadata = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first(['COLUMN_TYPE', 'IS_NULLABLE']);

        if ($metadata === null) {
            throw new RuntimeException("Cannot harden {$table}: {$column} column is missing.");
        }

        $columnType = strtoupper((string) $metadata->COLUMN_TYPE);
        if ($columnType !== $expectedType) {
            throw new RuntimeException(
                "Cannot harden {$table}: {$column} must be {$expectedType} before SET NULL; found {$columnType}."
            );
        }

        if ($metadata->IS_NULLABLE === 'YES') {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$table}` MODIFY `{$column}` {$expectedType} NULL DEFAULT NULL"
        );
    }

    private function addIndex(
        KmSchemaAuditService $auditService,
        string $table,
        array $columns,
        string $name,
        bool $unique
    ): void {
        $actual = $auditService->indexDefinition($table, $name);
        if ($actual !== null) {
            $expected = [
                'table' => $table,
                'name' => $name,
                'columns' => $columns,
                'unique' => $unique,
            ];
            if ($actual !== $expected) {
                $expectedKind = $unique ? 'UNIQUE' : 'NON-UNIQUE';
                $actualKind = $actual['unique'] ? 'UNIQUE' : 'NON-UNIQUE';

                throw new RuntimeException(
                    "Cannot harden {$table}: named index {$name} has schema drift. "
                    ."Expected {$expectedKind} (".implode(', ', $columns).') but found '
                    ."{$actualKind} (".implode(', ', $actual['columns']).'). '
                    .'Review and reconcile this existing named index before retrying; the migration will not replace it automatically.'
                );
            }

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name, $unique): void {
            if ($unique) {
                $blueprint->unique($columns, $name);
            } else {
                $blueprint->index($columns, $name);
            }
        });
    }

    private function addForeignKey(
        KmSchemaAuditService $auditService,
        string $table,
        string $column,
        string $parent,
        string $parentColumn,
        string $name,
        string $delete
    ): void {
        $deleteRule = $delete === 'cascade' ? 'CASCADE' : 'SET NULL';
        $actual = $auditService->foreignKeyDefinition($table, $name);
        if ($actual !== null) {
            $expected = [
                'table' => $table,
                'name' => $name,
                'columns' => [$column],
                'references_table' => $parent,
                'references_columns' => [$parentColumn],
                'delete_rule' => $deleteRule,
            ];
            if ($actual !== $expected) {
                throw new RuntimeException(
                    "Cannot harden {$table}: named foreign key {$name} has schema drift. "
                    ."Expected {$table}.{$column} -> {$parent}.{$parentColumn} ON DELETE {$deleteRule}; "
                    .'found '.implode(', ', $actual['columns']).' -> '
                    .$actual['references_table'].'.'.implode(', ', $actual['references_columns'])
                    .' ON DELETE '.$actual['delete_rule'].'. '
                    .'Review and reconcile this existing named foreign key before retrying; the migration will not replace it automatically.'
                );
            }

            return;
        }

        Schema::table(
            $table,
            function (Blueprint $blueprint) use (
                $column,
                $parent,
                $parentColumn,
                $name,
                $delete
            ): void {
                $foreign = $blueprint
                    ->foreign($column, $name)
                    ->references($parentColumn)
                    ->on($parent);

                if ($delete === 'cascade') {
                    $foreign->cascadeOnDelete();
                } else {
                    $foreign->nullOnDelete();
                }
            }
        );
    }

    private function guardTestingRollback(): void
    {
        $database = (string) DB::connection()->getDatabaseName();

        if (! app()->environment('testing') || ! str_ends_with($database, '_testing')) {
            throw new RuntimeException(
                'KM hardening rollback is only allowed when APP_ENV=testing and DB_DATABASE ends with _testing.'
            );
        }
    }

    private function assertMySql(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'mysql') {
            throw new RuntimeException("KM schema hardening requires MySQL; active driver is {$driver}.");
        }
    }
};
