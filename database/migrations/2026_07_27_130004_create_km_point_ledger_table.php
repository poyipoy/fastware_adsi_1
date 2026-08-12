<?php

use App\Services\KnowledgeManagement\KmSchemaAuditService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertMySql();

        foreach (['users', 'km_pengajuans', 'km_insights'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Preflight ledger poin KM gagal: tabel {$table} tidak ditemukan.");
            }
        }
        foreach (['km_total_poin', 'section'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                throw new RuntimeException(
                    "Preflight ledger poin KM gagal: users.{$column} tidak ditemukan."
                );
            }
        }
        foreach ([
            ['users', 'id', 'bigint unsigned'],
            ['km_pengajuans', 'id', 'int'],
            ['km_insights', 'id', 'int'],
        ] as [$table, $column, $expected]) {
            $this->assertColumnType($table, $column, $expected);
        }

        if (Schema::hasTable('km_point_ledger')) {
            $this->assertExistingShape();
            $this->backfillOpeningBalances();

            return;
        }

        Schema::create('km_point_ledger', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('event_type', 48);
            $table->string('event_key', 191);
            $table->integer('points');
            $table->string('department_snapshot')->nullable();
            $table->integer('km_pengajuan_id')->nullable();
            $table->integer('km_insight_id')->nullable();
            $table->json('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('event_key', 'km_point_ledger_event_key_unique');
            $table->index(['user_id', 'created_at', 'id'], 'km_point_ledger_user_created_index');
            $table->index(
                ['department_snapshot', 'created_at'],
                'km_point_ledger_department_created_index'
            );
            $table->index('km_pengajuan_id', 'km_point_ledger_document_index');
            $table->index('km_insight_id', 'km_point_ledger_insight_index');

            $table->foreign('user_id', 'km_point_ledger_user_foreign')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('km_pengajuan_id', 'km_point_ledger_document_foreign')
                ->references('id')
                ->on('km_pengajuans')
                ->nullOnDelete();
            $table->foreign('km_insight_id', 'km_point_ledger_insight_foreign')
                ->references('id')
                ->on('km_insights')
                ->nullOnDelete();
            $table->foreign('created_by', 'km_point_ledger_created_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        $this->backfillOpeningBalances();
    }

    public function down(): void
    {
        $this->guardTestingRollback();
        Schema::dropIfExists('km_point_ledger');
    }

    private function assertExistingShape(): void
    {
        $expected = [
            'id',
            'user_id',
            'event_type',
            'event_key',
            'points',
            'department_snapshot',
            'km_pengajuan_id',
            'km_insight_id',
            'notes',
            'created_by',
            'created_at',
        ];
        $actual = Schema::getColumnListing('km_point_ledger');
        if (array_values(array_diff($expected, $actual)) !== []
            || array_values(array_diff($actual, $expected)) !== []) {
            throw new RuntimeException(
                'Schema drift pada km_point_ledger; reconcile tabel append-only secara eksplisit.'
            );
        }

        /** @var KmSchemaAuditService $audit */
        $audit = app(KmSchemaAuditService::class);
        $indexes = [
            'km_point_ledger_event_key_unique' => [['event_key'], true],
            'km_point_ledger_user_created_index' => [['user_id', 'created_at', 'id'], false],
            'km_point_ledger_department_created_index' => [
                ['department_snapshot', 'created_at'],
                false,
            ],
            'km_point_ledger_document_index' => [['km_pengajuan_id'], false],
            'km_point_ledger_insight_index' => [['km_insight_id'], false],
        ];
        foreach ($indexes as $name => [$columns, $unique]) {
            if ($audit->indexDefinition('km_point_ledger', $name) !== [
                'table' => 'km_point_ledger',
                'name' => $name,
                'columns' => $columns,
                'unique' => $unique,
            ]) {
                throw new RuntimeException(
                    "Schema drift km_point_ledger: index {$name} tidak sesuai."
                );
            }
        }

        $foreignKeys = [
            'km_point_ledger_user_foreign' => [['user_id'], 'users', ['id'], 'RESTRICT'],
            'km_point_ledger_document_foreign' => [
                ['km_pengajuan_id'],
                'km_pengajuans',
                ['id'],
                'SET NULL',
            ],
            'km_point_ledger_insight_foreign' => [
                ['km_insight_id'],
                'km_insights',
                ['id'],
                'SET NULL',
            ],
            'km_point_ledger_created_by_foreign' => [
                ['created_by'],
                'users',
                ['id'],
                'SET NULL',
            ],
        ];
        foreach ($foreignKeys as $name => [$columns, $table, $referencedColumns, $deleteRule]) {
            if ($audit->foreignKeyDefinition('km_point_ledger', $name) !== [
                'table' => 'km_point_ledger',
                'name' => $name,
                'columns' => $columns,
                'references_table' => $table,
                'references_columns' => $referencedColumns,
                'delete_rule' => $deleteRule,
            ]) {
                throw new RuntimeException(
                    "Schema drift km_point_ledger: foreign key {$name} tidak sesuai."
                );
            }
        }
    }

    private function backfillOpeningBalances(): void
    {
        DB::table('users')
            ->where('km_total_poin', '>', 0)
            ->select(['id', 'km_total_poin', 'section'])
            ->orderBy('id')
            ->chunkById(500, function (Collection $users): void {
                $userIds = $users->pluck('id')->map(static fn ($id): int => (int) $id)->all();
                $departments = $this->departmentSnapshots($userIds);
                $createdAt = now();
                $rows = $users->map(static function (object $user) use ($departments, $createdAt): array {
                    $fallback = trim((string) ($user->section ?? ''));

                    return [
                        'user_id' => (int) $user->id,
                        'event_type' => 'opening_balance',
                        'event_key' => 'opening_balance:'.(int) $user->id,
                        'points' => (int) $user->km_total_poin,
                        'department_snapshot' => $departments[(int) $user->id]
                            ?? ($fallback !== '' ? $fallback : null),
                        'km_pengajuan_id' => null,
                        'km_insight_id' => null,
                        'notes' => json_encode([
                            'source' => 'users.km_total_poin',
                            'effective_at' => $createdAt->toIso8601String(),
                        ], JSON_THROW_ON_ERROR),
                        'created_by' => null,
                        'created_at' => $createdAt,
                    ];
                })->all();

                if ($rows !== []) {
                    DB::table('km_point_ledger')->insertOrIgnore($rows);
                }
            });
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, string>
     */
    private function departmentSnapshots(array $userIds): array
    {
        if ($userIds === []
            || ! Schema::hasTable('user_job_positions')
            || ! Schema::hasTable('mst_job_positions')
            || ! Schema::hasTable('mst_departments')
            || ! $this->hasColumns('user_job_positions', ['id', 'user_id', 'mst_job_position_id', 'is_active'])
            || ! $this->hasColumns('mst_job_positions', ['id', 'department_id'])
            || ! $this->hasColumns('mst_departments', ['id', 'name'])) {
            return [];
        }

        $latest = DB::table('user_job_positions')
            ->selectRaw('user_id, MAX(id) AS assignment_id')
            ->whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->groupBy('user_id');

        return DB::query()
            ->fromSub($latest, 'latest')
            ->join('user_job_positions as assignments', 'assignments.id', '=', 'latest.assignment_id')
            ->join('mst_job_positions as positions', 'positions.id', '=', 'assignments.mst_job_position_id')
            ->join('mst_departments as departments', 'departments.id', '=', 'positions.department_id')
            ->pluck('departments.name', 'latest.user_id')
            ->mapWithKeys(static fn ($name, $userId): array => [(int) $userId => (string) $name])
            ->all();
    }

    private function assertMySql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Migration ledger poin KM memerlukan MySQL.');
        }
    }

    /** @param  list<string>  $columns */
    private function hasColumns(string $table, array $columns): bool
    {
        return collect($columns)->every(
            static fn (string $column): bool => Schema::hasColumn($table, $column),
        );
    }

    private function assertColumnType(string $table, string $column, string $expected): void
    {
        $actual = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->value('COLUMN_TYPE');
        $normalized = preg_replace('/\(\d+\)/', '', strtolower((string) $actual));
        if ($normalized !== $expected) {
            throw new RuntimeException(
                "Preflight ledger poin KM gagal: tipe {$table}.{$column} harus {$expected}; actual "
                .($normalized !== '' ? $normalized : 'missing').'.'
            );
        }
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
