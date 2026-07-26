<?php

namespace Tests\Feature\KnowledgeManagement;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class KmTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $database = (string) DB::connection()->getDatabaseName();
        if (! app()->environment('testing')
            || DB::connection()->getDriverName() !== 'mysql'
            || ! str_ends_with($database, '_testing')) {
            $this->markTestSkipped(
                'KM feature tests require MySQL, APP_ENV=testing, and DB_DATABASE ending with _testing.'
            );
        }

        $this->rebuildKmTestSchema();
    }

    protected function tearDown(): void
    {
        if (app()->environment('testing')
            && str_ends_with((string) DB::connection()->getDatabaseName(), '_testing')) {
            $this->dropKmTestSchema();
        }

        parent::tearDown();
    }

    protected function rebuildKmTestSchema(): void
    {
        $this->dropKmTestSchema();
        $this->createSupportSchema();

        foreach ([
            '2026_07_18_100001_baseline_knowledge_management_schema.php',
            '2026_07_18_100002_harden_knowledge_management_constraints.php',
            '2026_07_18_100003_create_km_approval_events_table.php',
            '2026_07_18_100004_add_private_file_metadata_to_km_pengajuans.php',
            // Jangka Menengah
            '2026_07_18_110001_create_km_bookmarks_table.php',
            '2026_07_18_110002_add_km_thumbnail_pipeline_fields_to_km_pengajuans.php',
            '2026_07_18_110003_create_km_tags_table.php',
            '2026_07_18_110004_create_km_document_tag_table.php',
            '2026_07_18_110005_create_km_document_authors_table.php',
            '2026_07_18_110006_add_km_authoring_metadata_to_km_pengajuans.php',
            // Jangka Panjang
            '2026_07_18_120001_add_km_metadata_fulltext_index_to_km_pengajuans.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    protected function dropKmTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'km_approval_events',
            'km_insights',
            'km_sukas',
            'km_lihat_bukus',
            'km_transaksis',
            // Jangka Menengah
            'km_bookmarks',
            'km_document_tag',
            'km_document_authors',
            'km_progresses',
            // ---
            'km_pengajuans',
            'km_tags',
            'km_kategoris',
            'user_job_positions',
            'mst_job_positions',
            'users',
            'roles',
            'jobs',
            'failed_jobs',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }

    private function createSupportSchema(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('role');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('name');
            $table->string('section')->nullable();
            $table->string('npk')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->bigInteger('km_total_poin')->nullable()->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mst_job_positions', function (Blueprint $table): void {
            $table->id();
            $table->string('position_name')->unique();
            $table->string('job_level')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_job_positions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('mst_job_position_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}
