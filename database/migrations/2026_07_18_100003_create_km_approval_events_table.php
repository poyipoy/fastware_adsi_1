<?php

use App\Services\KnowledgeManagement\KmSchemaAuditService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'id' => ['bigint unsigned', false, true],
        'km_pengajuan_id' => ['int', false, false],
        'actor_id' => ['bigint unsigned', true, false],
        'actor_name' => ['varchar(255)', true, false],
        'actor_role_snapshot' => ['varchar(255)', true, false],
        'action' => ['varchar(32)', false, false],
        'from_status' => ['tinyint unsigned', true, false],
        'to_status' => ['tinyint unsigned', false, false],
        'reason' => ['text', true, false],
        'metadata' => ['json', true, false],
        'acted_at' => ['timestamp', false, false],
        'created_at' => ['timestamp', false, false],
    ];

    private const INDEXES = [
        ['PRIMARY', ['id'], true],
        ['km_approval_events_document_acted_at_index', ['km_pengajuan_id', 'acted_at'], false],
        ['km_approval_events_actor_acted_at_index', ['actor_id', 'acted_at'], false],
    ];

    private const FOREIGN_KEYS = [
        ['km_approval_events_document_foreign', ['km_pengajuan_id'], 'km_pengajuans', ['id'], 'RESTRICT'],
        ['km_approval_events_actor_foreign', ['actor_id'], 'users', ['id'], 'SET NULL'],
    ];

    public function up(): void
    {
        $this->assertMySql();

        if (Schema::hasTable('km_approval_events')) {
            $this->assertExistingTableShape();

            return;
        }

        Schema::create('km_approval_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->integer('km_pengajuan_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_role_snapshot')->nullable();
            $table->string('action', 32);
            $table->unsignedTinyInteger('from_status')->nullable();
            $table->unsignedTinyInteger('to_status');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('acted_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['km_pengajuan_id', 'acted_at'],
                'km_approval_events_document_acted_at_index'
            );
            $table->index(
                ['actor_id', 'acted_at'],
                'km_approval_events_actor_acted_at_index'
            );
            $table->foreign('km_pengajuan_id', 'km_approval_events_document_foreign')
                ->references('id')
                ->on('km_pengajuans')
                ->restrictOnDelete();
            $table->foreign('actor_id', 'km_approval_events_actor_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    private function assertExistingTableShape(): void
    {
        /** @var KmSchemaAuditService $auditService */
        $auditService = app(KmSchemaAuditService::class);
        $problems = [];
        $actualColumns = Schema::getColumnListing('km_approval_events');
        $missing = array_values(array_diff(array_keys(self::COLUMNS), $actualColumns));
        if ($missing !== []) {
            $problems[] = 'missing columns: '.implode(', ', $missing);
        }

        if (in_array('updated_at', $actualColumns, true)) {
            $problems[] = 'updated_at must not exist because approval events are append-only';
        }

        $unexpected = array_values(array_diff($actualColumns, array_keys(self::COLUMNS), ['updated_at']));
        if ($unexpected !== []) {
            $problems[] = 'unexpected columns: '.implode(', ', $unexpected);
        }

        foreach (self::COLUMNS as $column => [$type, $nullable, $autoIncrement]) {
            $actual = $auditService->columnDefinition('km_approval_events', $column);
            if ($actual === null) {
                continue;
            }

            $actualType = $this->normalizeIntegerType($actual['data_type'], $actual['column_type']);
            $hasAutoIncrement = str_contains($actual['extra'], 'auto_increment');
            if ($actualType !== $type
                || $actual['nullable'] !== $nullable
                || $hasAutoIncrement !== $autoIncrement) {
                $expectedExtra = $autoIncrement ? ', AUTO_INCREMENT' : '';
                $actualExtra = $hasAutoIncrement ? ', AUTO_INCREMENT' : '';
                $problems[] = "{$column} expected {$type}, "
                    .($nullable ? 'NULL' : 'NOT NULL')."{$expectedExtra}; found {$actualType}, "
                    .($actual['nullable'] ? 'NULL' : 'NOT NULL').$actualExtra;
            }
        }

        foreach (self::INDEXES as [$name, $columns, $unique]) {
            $actual = $auditService->indexDefinition('km_approval_events', $name);
            $expected = [
                'table' => 'km_approval_events',
                'name' => $name,
                'columns' => $columns,
                'unique' => $unique,
            ];
            if ($actual !== $expected) {
                $problems[] = "index {$name} expected "
                    .($unique ? 'UNIQUE' : 'NON-UNIQUE').' ('.implode(', ', $columns).'); found '
                    .$this->describeIndex($actual);
            }
        }

        foreach (self::FOREIGN_KEYS as [$name, $columns, $parent, $parentColumns, $deleteRule]) {
            $actual = $auditService->foreignKeyDefinition('km_approval_events', $name);
            $expected = [
                'table' => 'km_approval_events',
                'name' => $name,
                'columns' => $columns,
                'references_table' => $parent,
                'references_columns' => $parentColumns,
                'delete_rule' => $deleteRule,
            ];
            if ($actual !== $expected) {
                $problems[] = "foreign key {$name} expected (".implode(', ', $columns).') -> '
                    .$parent.'('.implode(', ', $parentColumns).") ON DELETE {$deleteRule}; found "
                    .$this->describeForeignKey($actual);
            }
        }

        if ($problems !== []) {
            throw new RuntimeException(
                'Schema drift pada km_approval_events: '.implode('; ', $problems)
                .'. Reconcile the existing append-only table explicitly before retrying; this migration will not alter it automatically.'
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

    private function describeIndex(?array $index): string
    {
        if ($index === null) {
            return 'missing';
        }

        return ($index['unique'] ? 'UNIQUE' : 'NON-UNIQUE')
            .' ('.implode(', ', $index['columns']).')';
    }

    private function describeForeignKey(?array $foreignKey): string
    {
        if ($foreignKey === null) {
            return 'missing';
        }

        return '('.implode(', ', $foreignKey['columns']).') -> '
            .$foreignKey['references_table'].'('.implode(', ', $foreignKey['references_columns']).')'
            .' ON DELETE '.$foreignKey['delete_rule'];
    }

    private function assertMySql(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            throw new RuntimeException(
                "Migration km_approval_events requires MySQL; active driver is {$driver}."
            );
        }
    }

    public function down(): void
    {
        $this->guardTestingRollback();
        Schema::dropIfExists('km_approval_events');
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
