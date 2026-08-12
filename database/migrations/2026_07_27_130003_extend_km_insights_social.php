<?php

use App\Services\KnowledgeManagement\KmSchemaAuditService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INSIGHT_COLUMNS = [
        'parent_id',
        'edited_at',
        'deleted_at',
        'deleted_by',
        'delete_reason',
        'featured_at',
        'featured_by',
    ];

    public function up(): void
    {
        $this->assertMySql();

        foreach (['users', 'km_pengajuans', 'km_insights'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Preflight fitur sosial KM gagal: tabel {$table} tidak ditemukan.");
            }
        }
        foreach ([
            ['users', 'id', 'bigint unsigned'],
            ['km_pengajuans', 'id', 'int'],
            ['km_insights', 'id', 'int'],
            ['km_insights', 'id_km_pengajuan', 'int'],
        ] as [$table, $column, $expected]) {
            $this->assertColumnType($table, $column, $expected);
        }

        $this->extendInsights();
        $this->createReactions();
        $this->createMentions();
    }

    public function down(): void
    {
        $this->guardTestingRollback();

        Schema::dropIfExists('km_insight_mentions');
        Schema::dropIfExists('km_insight_reactions');

        if (! Schema::hasTable('km_insights')) {
            return;
        }

        /** @var KmSchemaAuditService $audit */
        $audit = app(KmSchemaAuditService::class);
        Schema::table('km_insights', function (Blueprint $table) use ($audit): void {
            foreach ([
                'km_insights_parent_foreign',
                'km_insights_deleted_by_foreign',
                'km_insights_featured_by_foreign',
            ] as $foreign) {
                if ($audit->foreignKeyDefinition('km_insights', $foreign) !== null) {
                    $table->dropForeign($foreign);
                }
            }

            foreach ([
                'km_insights_document_parent_id_index',
                'km_insights_document_featured_at_index',
            ] as $index) {
                if ($audit->hasIndex('km_insights', $index)) {
                    $table->dropIndex($index);
                }
            }
        });

        $columns = array_values(array_filter(
            self::INSIGHT_COLUMNS,
            static fn (string $column): bool => Schema::hasColumn('km_insights', $column),
        ));
        if ($columns !== []) {
            Schema::table('km_insights', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    private function extendInsights(): void
    {
        $present = array_values(array_filter(
            self::INSIGHT_COLUMNS,
            static fn (string $column): bool => Schema::hasColumn('km_insights', $column),
        ));
        if ($present !== [] && count($present) !== count(self::INSIGHT_COLUMNS)) {
            throw new RuntimeException(
                'Schema drift km_insights: hanya sebagian kolom sosial tersedia. Missing: '
                .implode(', ', array_diff(self::INSIGHT_COLUMNS, $present)).'.'
            );
        }

        if ($present === []) {
            Schema::table('km_insights', function (Blueprint $table): void {
                $table->integer('parent_id')->nullable()->after('id_km_pengajuan');
                $table->timestamp('edited_at')->nullable()->after('content');
                $table->timestamp('deleted_at')->nullable()->after('edited_at');
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                $table->string('delete_reason', 500)->nullable()->after('deleted_by');
                $table->timestamp('featured_at')->nullable()->after('delete_reason');
                $table->unsignedBigInteger('featured_by')->nullable()->after('featured_at');

                $table->index(
                    ['id_km_pengajuan', 'parent_id', 'id'],
                    'km_insights_document_parent_id_index'
                );
                $table->index(
                    ['id_km_pengajuan', 'featured_at'],
                    'km_insights_document_featured_at_index'
                );
                $table->foreign('parent_id', 'km_insights_parent_foreign')
                    ->references('id')
                    ->on('km_insights')
                    ->cascadeOnDelete();
                $table->foreign('deleted_by', 'km_insights_deleted_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
                $table->foreign('featured_by', 'km_insights_featured_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });

            return;
        }

        $this->assertIndex(
            'km_insights',
            'km_insights_document_parent_id_index',
            ['id_km_pengajuan', 'parent_id', 'id'],
        );
        $this->assertIndex(
            'km_insights',
            'km_insights_document_featured_at_index',
            ['id_km_pengajuan', 'featured_at'],
        );
        $this->assertForeign(
            'km_insights',
            'km_insights_parent_foreign',
            ['parent_id'],
            'km_insights',
            ['id'],
            'CASCADE',
        );
        $this->assertForeign(
            'km_insights',
            'km_insights_deleted_by_foreign',
            ['deleted_by'],
            'users',
            ['id'],
            'SET NULL',
        );
        $this->assertForeign(
            'km_insights',
            'km_insights_featured_by_foreign',
            ['featured_by'],
            'users',
            ['id'],
            'SET NULL',
        );
    }

    private function createReactions(): void
    {
        if (Schema::hasTable('km_insight_reactions')) {
            $this->assertColumns(
                'km_insight_reactions',
                ['id', 'insight_id', 'user_id', 'reaction', 'created_at', 'updated_at']
            );
            $this->assertIndex(
                'km_insight_reactions',
                'km_insight_reactions_insight_user_unique',
                ['insight_id', 'user_id'],
                true,
            );
            $this->assertIndex(
                'km_insight_reactions',
                'km_insight_reactions_user_insight_index',
                ['user_id', 'insight_id'],
            );
            $this->assertForeign(
                'km_insight_reactions',
                'km_insight_reactions_insight_foreign',
                ['insight_id'],
                'km_insights',
                ['id'],
                'CASCADE',
            );
            $this->assertForeign(
                'km_insight_reactions',
                'km_insight_reactions_user_foreign',
                ['user_id'],
                'users',
                ['id'],
                'CASCADE',
            );
            $this->assertReactionCheck();

            return;
        }

        Schema::create('km_insight_reactions', function (Blueprint $table): void {
            $table->id();
            $table->integer('insight_id');
            $table->unsignedBigInteger('user_id');
            $table->string('reaction', 16);
            $table->timestamps();

            $table->unique(
                ['insight_id', 'user_id'],
                'km_insight_reactions_insight_user_unique'
            );
            $table->index(
                ['user_id', 'insight_id'],
                'km_insight_reactions_user_insight_index'
            );
            $table->foreign('insight_id', 'km_insight_reactions_insight_foreign')
                ->references('id')
                ->on('km_insights')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'km_insight_reactions_user_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        DB::statement(
            'ALTER TABLE `km_insight_reactions` ADD CONSTRAINT `km_insight_reactions_type_check` '
            ."CHECK (`reaction` IN ('helpful', 'insightful', 'agree'))"
        );
    }

    private function createMentions(): void
    {
        if (Schema::hasTable('km_insight_mentions')) {
            $this->assertColumns(
                'km_insight_mentions',
                ['id', 'insight_id', 'mentioned_user_id', 'created_at']
            );
            $this->assertIndex(
                'km_insight_mentions',
                'km_insight_mentions_insight_user_unique',
                ['insight_id', 'mentioned_user_id'],
                true,
            );
            $this->assertIndex(
                'km_insight_mentions',
                'km_insight_mentions_user_insight_index',
                ['mentioned_user_id', 'insight_id'],
            );
            $this->assertForeign(
                'km_insight_mentions',
                'km_insight_mentions_insight_foreign',
                ['insight_id'],
                'km_insights',
                ['id'],
                'CASCADE',
            );
            $this->assertForeign(
                'km_insight_mentions',
                'km_insight_mentions_user_foreign',
                ['mentioned_user_id'],
                'users',
                ['id'],
                'CASCADE',
            );

            return;
        }

        Schema::create('km_insight_mentions', function (Blueprint $table): void {
            $table->id();
            $table->integer('insight_id');
            $table->unsignedBigInteger('mentioned_user_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['insight_id', 'mentioned_user_id'],
                'km_insight_mentions_insight_user_unique'
            );
            $table->index(
                ['mentioned_user_id', 'insight_id'],
                'km_insight_mentions_user_insight_index'
            );
            $table->foreign('insight_id', 'km_insight_mentions_insight_foreign')
                ->references('id')
                ->on('km_insights')
                ->cascadeOnDelete();
            $table->foreign('mentioned_user_id', 'km_insight_mentions_user_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /** @param  list<string>  $expected */
    private function assertColumns(string $table, array $expected): void
    {
        $actual = Schema::getColumnListing($table);
        if (array_values(array_diff($expected, $actual)) !== []
            || array_values(array_diff($actual, $expected)) !== []) {
            throw new RuntimeException("Schema drift pada {$table}; reconcile tabel secara eksplisit.");
        }
    }

    /** @param  list<string>  $columns */
    private function assertIndex(
        string $table,
        string $name,
        array $columns,
        bool $unique = false,
    ): void {
        /** @var KmSchemaAuditService $audit */
        $audit = app(KmSchemaAuditService::class);
        if ($audit->indexDefinition($table, $name) !== [
            'table' => $table,
            'name' => $name,
            'columns' => $columns,
            'unique' => $unique,
        ]) {
            throw new RuntimeException("Schema drift {$table}: index {$name} tidak sesuai.");
        }
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $referencedColumns
     */
    private function assertForeign(
        string $table,
        string $name,
        array $columns,
        string $referencedTable,
        array $referencedColumns,
        string $deleteRule,
    ): void {
        /** @var KmSchemaAuditService $audit */
        $audit = app(KmSchemaAuditService::class);
        if ($audit->foreignKeyDefinition($table, $name) !== [
            'table' => $table,
            'name' => $name,
            'columns' => $columns,
            'references_table' => $referencedTable,
            'references_columns' => $referencedColumns,
            'delete_rule' => $deleteRule,
        ]) {
            throw new RuntimeException("Schema drift {$table}: foreign key {$name} tidak sesuai.");
        }
    }

    private function assertReactionCheck(): void
    {
        $clause = DB::table('information_schema.CHECK_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->where('CONSTRAINT_NAME', 'km_insight_reactions_type_check')
            ->value('CHECK_CLAUSE');
        $normalized = strtolower((string) $clause);
        foreach (['reaction', 'helpful', 'insightful', 'agree'] as $required) {
            if (! str_contains($normalized, $required)) {
                throw new RuntimeException(
                    'Schema drift km_insight_reactions: check constraint reaction tidak sesuai.'
                );
            }
        }
    }

    private function assertMySql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Migration fitur sosial KM memerlukan MySQL.');
        }
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
                "Preflight fitur sosial KM gagal: tipe {$table}.{$column} harus {$expected}; actual "
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
