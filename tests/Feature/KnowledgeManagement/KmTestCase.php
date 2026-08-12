<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmProcessingStatus;
use App\Enums\KnowledgeManagement\KmVersionStatus;
use App\Models\KmDocumentVersion;
use App\Models\KmPengajuan;
use App\Models\MstJobPosition;
use App\Models\User;
use App\Models\UserJobPosition;
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
            // Engagement Foundation
            '2026_07_27_130001_create_km_notifications_table.php',
            '2026_07_27_130002_add_km_reading_progress_to_km_transaksis.php',
            '2026_07_27_130003_extend_km_insights_social.php',
            '2026_07_27_130004_create_km_point_ledger_table.php',
            // Roadmap KM priority 1, 3, and 4
            '2026_07_29_140001_create_km_document_versions.php',
            '2026_07_29_140002_create_km_access_targeting_and_publication.php',
            '2026_07_29_140003_create_km_compliance_tracking.php',
            '2026_07_29_140004_create_km_gamification_exports_and_hris.php',
            '2026_08_02_160001_backfill_km_job_position_effective_from.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    protected function grantKmApprovalAccess(
        User $user,
        bool $assignmentActive = true,
        bool $positionActive = true,
        string $positionName = 'HRGA & Legal Staff',
    ): User {
        $position = MstJobPosition::query()->firstOrCreate(
            ['position_name' => $positionName],
            ['is_active' => $positionActive],
        );

        if ((bool) $position->is_active !== $positionActive) {
            $position->update(['is_active' => $positionActive]);
        }

        UserJobPosition::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'mst_job_position_id' => $position->getKey(),
            ],
            [
                'is_active' => $assignmentActive,
                'effective_from' => today()->toDateString(),
                'assignment_source' => 'km_test',
            ],
        );

        return $user;
    }

    protected function attachReadyDraftVersion(
        KmPengajuan $document,
        ?User $creator = null,
    ): KmDocumentVersion {
        $creator ??= $document->user;
        $checksum = hash('sha256', 'km-test-version-'.$document->getKey());
        $version = KmDocumentVersion::query()->create([
            'km_pengajuan_id' => $document->getKey(),
            'version_major' => 1,
            'version_minor' => 0,
            'change_type' => 'major',
            'change_note' => 'Fixture versi siap diproses.',
            'version_status' => KmVersionStatus::DRAFT,
            'title' => (string) $document->judul,
            'synopsis' => $document->keterangan,
            'category_id' => $document->id_km_kategori,
            'audience' => $document->posisi,
            'original_disk' => 'km_private',
            'original_path' => 'documents/'.$document->getKey().'/versions/test/original.pdf',
            'original_name' => 'materi-test.pdf',
            'original_mime_type' => 'application/pdf',
            'original_size_bytes' => 1024,
            'original_checksum_sha256' => $checksum,
            'normalized_pdf_disk' => 'km_private',
            'normalized_pdf_path' => 'documents/'.$document->getKey().'/versions/test/normalized.pdf',
            'normalized_pdf_size_bytes' => 1024,
            'normalized_pdf_checksum_sha256' => $checksum,
            'processing_status' => KmProcessingStatus::READY,
            'antivirus_status' => 'clean',
            'processing_attempts' => 0,
            'created_by' => $creator?->getKey() ?? $document->id_user,
            'processed_at' => now(),
        ]);
        $document->forceFill([
            'current_version_id' => $version->getKey(),
            'file_disk' => $version->original_disk,
            'file_path' => $version->original_path,
            'file_original_name' => $version->original_name,
            'file_mime_type' => $version->original_mime_type,
            'file_size_bytes' => $version->original_size_bytes,
            'file_checksum_sha256' => $version->original_checksum_sha256,
            'file_migrated_at' => now(),
        ])->save();

        return $version->refresh();
    }

    protected function dropKmTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            // Roadmap KM priority 1, 3, and 4
            'km_hris_outbound_events',
            'km_export_audits',
            'km_user_badges',
            'km_badges',
            'km_completion_events',
            'km_assignment_users',
            'km_assignments',
            'km_reading_sessions',
            'km_publication_recipients',
            'km_publication_batches',
            'km_document_version_job_positions',
            'km_document_version_departments',
            'km_organization_assignment_audits',
            'km_access_audits',
            'km_access_rules',
            'km_document_recovery_audits',
            'km_document_version_authors',
            'km_document_version_tags',
            'km_document_versions',
            // Engagement Foundation
            'km_notifications',
            'km_point_ledger',
            'km_insight_mentions',
            'km_insight_reactions',
            // ---
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
            'mst_departments',
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

        Schema::create('mst_departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mst_job_positions', function (Blueprint $table): void {
            $table->id();
            $table->string('position_name')->unique();
            $table->unsignedBigInteger('department_id')->nullable();
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
