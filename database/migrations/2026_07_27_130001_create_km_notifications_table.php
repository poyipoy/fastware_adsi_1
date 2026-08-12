<?php

use App\Services\KnowledgeManagement\KmSchemaAuditService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertMySql();

        if (! Schema::hasTable('users')) {
            throw new RuntimeException('Preflight notification KM gagal: tabel users tidak ditemukan.');
        }
        $this->assertColumnType('users', 'id', 'bigint unsigned');

        if (Schema::hasTable('km_notifications')) {
            $this->assertExistingTableShape();

            return;
        }

        Schema::create('km_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type', 48);
            $table->string('event_key', 191);
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('event_key', 'km_notifications_event_key_unique');
            $table->index(
                ['user_id', 'read_at', 'id'],
                'km_notifications_user_unread_id_index'
            );
            $table->foreign('user_id', 'km_notifications_user_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $this->guardTestingRollback();
        Schema::dropIfExists('km_notifications');
    }

    private function assertExistingTableShape(): void
    {
        $columns = Schema::getColumnListing('km_notifications');
        $expected = ['id', 'user_id', 'type', 'event_key', 'data', 'read_at', 'created_at'];
        $missing = array_values(array_diff($expected, $columns));
        $unexpected = array_values(array_diff($columns, $expected));

        /** @var KmSchemaAuditService $audit */
        $audit = app(KmSchemaAuditService::class);
        $expectedIndexes = [
            'km_notifications_event_key_unique' => [
                'table' => 'km_notifications',
                'name' => 'km_notifications_event_key_unique',
                'columns' => ['event_key'],
                'unique' => true,
            ],
            'km_notifications_user_unread_id_index' => [
                'table' => 'km_notifications',
                'name' => 'km_notifications_user_unread_id_index',
                'columns' => ['user_id', 'read_at', 'id'],
                'unique' => false,
            ],
        ];

        $problems = [];
        if ($missing !== []) {
            $problems[] = 'missing columns: '.implode(', ', $missing);
        }
        if ($unexpected !== []) {
            $problems[] = 'unexpected columns: '.implode(', ', $unexpected);
        }
        foreach ($expectedIndexes as $name => $definition) {
            if ($audit->indexDefinition('km_notifications', $name) !== $definition) {
                $problems[] = "index {$name} tidak sesuai";
            }
        }

        $foreign = $audit->foreignKeyDefinition('km_notifications', 'km_notifications_user_foreign');
        if ($foreign !== [
            'table' => 'km_notifications',
            'name' => 'km_notifications_user_foreign',
            'columns' => ['user_id'],
            'references_table' => 'users',
            'references_columns' => ['id'],
            'delete_rule' => 'CASCADE',
        ]) {
            $problems[] = 'foreign key km_notifications_user_foreign tidak sesuai';
        }

        if ($problems !== []) {
            throw new RuntimeException(
                'Schema drift pada km_notifications: '.implode('; ', $problems)
                .'. Reconcile schema secara eksplisit sebelum migration diulang.'
            );
        }
    }

    private function assertMySql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Migration notification KM memerlukan MySQL.');
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
                "Preflight notification KM gagal: tipe {$table}.{$column} harus {$expected}; actual "
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
