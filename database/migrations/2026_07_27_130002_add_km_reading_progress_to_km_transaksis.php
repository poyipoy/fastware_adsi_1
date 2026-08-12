<?php

use App\Services\KnowledgeManagement\KmSchemaAuditService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'last_page',
        'pages_total',
        'unique_pages',
        'unique_pages_count',
        'active_seconds',
        'progress_percent',
        'last_progress_at',
    ];

    public function up(): void
    {
        $this->assertMySql();

        if (! Schema::hasTable('km_transaksis')) {
            throw new RuntimeException('Preflight progress KM gagal: tabel km_transaksis tidak ditemukan.');
        }
        foreach (['id_user', 'status', 'points_awarded_at'] as $column) {
            if (! Schema::hasColumn('km_transaksis', $column)) {
                throw new RuntimeException(
                    "Preflight progress KM gagal: kolom km_transaksis.{$column} tidak ditemukan."
                );
            }
        }

        $present = array_values(array_filter(
            self::COLUMNS,
            static fn (string $column): bool => Schema::hasColumn('km_transaksis', $column),
        ));

        if ($present !== [] && count($present) !== count(self::COLUMNS)) {
            $missing = array_values(array_diff(self::COLUMNS, $present));
            throw new RuntimeException(
                'Schema drift progress KM: hanya sebagian kolom tersedia. Missing: '.implode(', ', $missing).'.'
            );
        }

        if ($present === []) {
            Schema::table('km_transaksis', function (Blueprint $table): void {
                $table->unsignedInteger('last_page')->nullable()->after('points_awarded_at');
                $table->unsignedInteger('pages_total')->nullable()->after('last_page');
                $table->text('unique_pages')->nullable()->after('pages_total');
                $table->unsignedInteger('unique_pages_count')->default(0)->after('unique_pages');
                $table->unsignedBigInteger('active_seconds')->default(0)->after('unique_pages_count');
                $table->unsignedTinyInteger('progress_percent')->default(0)->after('active_seconds');
                $table->timestamp('last_progress_at')->nullable()->after('progress_percent');

                $table->index(
                    ['id_user', 'status', 'last_progress_at'],
                    'km_transaksis_user_status_progress_index'
                );
            });

            return;
        }

        /** @var KmSchemaAuditService $audit */
        $audit = app(KmSchemaAuditService::class);
        $index = $audit->indexDefinition(
            'km_transaksis',
            'km_transaksis_user_status_progress_index'
        );
        if ($index !== [
            'table' => 'km_transaksis',
            'name' => 'km_transaksis_user_status_progress_index',
            'columns' => ['id_user', 'status', 'last_progress_at'],
            'unique' => false,
        ]) {
            throw new RuntimeException(
                'Schema drift progress KM: index km_transaksis_user_status_progress_index tidak sesuai.'
            );
        }
    }

    public function down(): void
    {
        $this->guardTestingRollback();

        if (! Schema::hasTable('km_transaksis')) {
            return;
        }

        /** @var KmSchemaAuditService $audit */
        $audit = app(KmSchemaAuditService::class);
        if ($audit->hasIndex('km_transaksis', 'km_transaksis_user_status_progress_index')) {
            Schema::table('km_transaksis', function (Blueprint $table): void {
                $table->dropIndex('km_transaksis_user_status_progress_index');
            });
        }

        $columns = array_values(array_filter(
            self::COLUMNS,
            static fn (string $column): bool => Schema::hasColumn('km_transaksis', $column),
        ));
        if ($columns !== []) {
            Schema::table('km_transaksis', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    private function assertMySql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Migration progress KM memerlukan MySQL.');
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
