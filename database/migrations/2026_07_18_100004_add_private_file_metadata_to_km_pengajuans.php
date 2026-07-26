<?php

use App\Services\KnowledgeManagement\KmSchemaAuditService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'file_disk' => ['varchar(32)', true],
        'file_path' => ['varchar(1024)', true],
        'file_original_name' => ['varchar(255)', true],
        'file_mime_type' => ['varchar(127)', true],
        'file_size_bytes' => ['bigint unsigned', true],
        'file_checksum_sha256' => ['char(64)', true],
        'file_migrated_at' => ['timestamp', true],
    ];

    private const INDEXES = [
        ['km_pengajuans_file_checksum_index', ['file_checksum_sha256'], false],
        ['km_pengajuans_file_migrated_at_index', ['file_migrated_at'], false],
    ];

    public function up(): void
    {
        $this->assertMySql();

        if (! Schema::hasTable('km_pengajuans')) {
            throw new RuntimeException(
                'Tabel km_pengajuans belum tersedia. Jalankan baseline migration KM terlebih dahulu.'
            );
        }

        /** @var KmSchemaAuditService $auditService */
        $auditService = app(KmSchemaAuditService::class);
        $this->assertExistingDefinitionsAreSafe($auditService);

        $definitions = [
            'file_disk' => fn (Blueprint $table) => $table->string('file_disk', 32)->nullable(),
            'file_path' => fn (Blueprint $table) => $table->string('file_path', 1024)->nullable(),
            'file_original_name' => fn (Blueprint $table) => $table->string('file_original_name')->nullable(),
            'file_mime_type' => fn (Blueprint $table) => $table->string('file_mime_type', 127)->nullable(),
            'file_size_bytes' => fn (Blueprint $table) => $table->unsignedBigInteger('file_size_bytes')->nullable(),
            'file_checksum_sha256' => fn (Blueprint $table) => $table->char('file_checksum_sha256', 64)->nullable(),
            'file_migrated_at' => fn (Blueprint $table) => $table->timestamp('file_migrated_at')->nullable(),
        ];

        foreach ($definitions as $column => $definition) {
            if (Schema::hasColumn('km_pengajuans', $column)) {
                continue;
            }

            Schema::table('km_pengajuans', function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        }

        foreach (self::INDEXES as [$name, $columns, $unique]) {
            if ($auditService->indexDefinition('km_pengajuans', $name) !== null) {
                continue;
            }

            Schema::table('km_pengajuans', function (Blueprint $table) use ($name, $columns, $unique): void {
                if ($unique) {
                    $table->unique($columns, $name);
                } else {
                    $table->index($columns, $name);
                }
            });
        }
    }

    private function assertExistingDefinitionsAreSafe(KmSchemaAuditService $auditService): void
    {
        $problems = [];
        foreach (self::COLUMNS as $column => [$type, $nullable]) {
            $actual = $auditService->columnDefinition('km_pengajuans', $column);
            if ($actual === null) {
                continue;
            }

            $actualType = $this->normalizeIntegerType($actual['data_type'], $actual['column_type']);
            if ($actualType !== $type || $actual['nullable'] !== $nullable) {
                $problems[] = "column {$column} expected {$type}, "
                    .($nullable ? 'NULL' : 'NOT NULL')."; found {$actualType}, "
                    .($actual['nullable'] ? 'NULL' : 'NOT NULL');
            }
        }

        foreach (self::INDEXES as [$name, $columns, $unique]) {
            $actual = $auditService->indexDefinition('km_pengajuans', $name);
            if ($actual === null) {
                continue;
            }

            $expected = [
                'table' => 'km_pengajuans',
                'name' => $name,
                'columns' => $columns,
                'unique' => $unique,
            ];
            if ($actual !== $expected) {
                $problems[] = "index {$name} expected "
                    .($unique ? 'UNIQUE' : 'NON-UNIQUE').' ('.implode(', ', $columns).'); found '
                    .($actual['unique'] ? 'UNIQUE' : 'NON-UNIQUE')
                    .' ('.implode(', ', $actual['columns']).')';
            }
        }

        if ($problems !== []) {
            throw new RuntimeException(
                'Schema drift pada metadata file privat km_pengajuans: '.implode('; ', $problems)
                .'. Reconcile the existing columns and named indexes before retrying; this migration will not overwrite them automatically.'
            );
        }
    }

    private function normalizeIntegerType(string $dataType, string $columnType): string
    {
        if (! in_array($dataType, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'], true)) {
            return $columnType;
        }

        return (string) preg_replace('/\(\d+\)/', '', $columnType);
    }

    private function assertMySql(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            throw new RuntimeException(
                "Migration metadata file privat KM requires MySQL; active driver is {$driver}."
            );
        }
    }

    public function down(): void
    {
        $this->guardTestingRollback();

        if (! Schema::hasTable('km_pengajuans')) {
            return;
        }

        $presentMetadataColumns = array_values(array_filter(
            array_keys(self::COLUMNS),
            static fn (string $column): bool => Schema::hasColumn('km_pengajuans', $column),
        ));
        if ($presentMetadataColumns !== []
            && DB::table('km_pengajuans')
                ->where(function ($query) use ($presentMetadataColumns): void {
                    foreach ($presentMetadataColumns as $column) {
                        $query->orWhereNotNull($column);
                    }
                })
                ->exists()) {
            throw new RuntimeException(
                'Rollback metadata file KM dibatalkan karena masih ada row dengan metadata private. '
                .'Jalankan km:migrate-private-files --restore-manifest=<path>, verifikasi file public, '
                .'lalu ulangi rollback.'
            );
        }

        Schema::table('km_pengajuans', function (Blueprint $table): void {
            if ($this->indexExists('km_pengajuans', 'km_pengajuans_file_checksum_index')) {
                $table->dropIndex('km_pengajuans_file_checksum_index');
            }

            if ($this->indexExists('km_pengajuans', 'km_pengajuans_file_migrated_at_index')) {
                $table->dropIndex('km_pengajuans_file_migrated_at_index');
            }
        });

        $columns = [
            'file_disk',
            'file_path',
            'file_original_name',
            'file_mime_type',
            'file_size_bytes',
            'file_checksum_sha256',
            'file_migrated_at',
        ];

        foreach ($columns as $column) {
            if (! Schema::hasColumn('km_pengajuans', $column)) {
                continue;
            }

            Schema::table('km_pengajuans', function (Blueprint $table) use ($column): void {
                $table->dropColumn($column);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return collect(Schema::getIndexes($table))
                ->contains(fn (array $definition): bool => ($definition['name'] ?? null) === $index);
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function guardTestingRollback(): void
    {
        if (! app()->environment('testing') || ! str_ends_with(DB::getDatabaseName(), '_testing')) {
            throw new RuntimeException(
                'Rollback migration KM hanya diizinkan pada APP_ENV=testing dan database *_testing.'
            );
        }
    }
};
