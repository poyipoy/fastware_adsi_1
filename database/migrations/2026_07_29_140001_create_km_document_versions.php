<?php

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmProcessingStatus;
use App\Enums\KnowledgeManagement\KmVersionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Migration versioning KM memerlukan MySQL.');
        }

        foreach (['users', 'km_pengajuans', 'km_kategoris', 'km_tags', 'km_document_tag', 'km_document_authors'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Preflight versioning KM gagal: tabel {$table} tidak ditemukan.");
            }
        }

        $this->createVersions();
        $this->createVersionPivots();
        $this->createRecoveryAudit();
        $this->extendLegacyTables();
        $this->backfillVersions();
        $this->replaceProgressIdentity();
        $this->addForeignKeys();
    }

    public function down(): void
    {
        $this->guardTestingRollback();

        $this->dropForeignIfExists('km_point_ledger', 'km_point_ledger_version_foreign');
        $this->dropForeignIfExists('km_insights', 'km_insights_version_foreign');
        $this->dropForeignIfExists('km_transaksis', 'km_transaksis_version_foreign');
        $this->dropForeignIfExists('km_approval_events', 'km_approval_events_version_foreign');
        $this->dropForeignIfExists('km_pengajuans', 'km_pengajuans_current_version_foreign');
        $this->dropForeignIfExists('km_pengajuans', 'km_pengajuans_published_version_foreign');

        if (Schema::hasTable('km_transaksis')) {
            $this->dropIndexIfExists('km_transaksis', 'km_transaksis_user_version_unique');
            if (! $this->indexExists('km_transaksis', 'km_transaksis_user_document_unique')) {
                Schema::table('km_transaksis', static function (Blueprint $table): void {
                    $table->unique(
                        ['id_user', 'id_km_pengajuan'],
                        'km_transaksis_user_document_unique'
                    );
                });
            }
        }

        foreach (['km_point_ledger', 'km_insights', 'km_transaksis', 'km_approval_events'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'document_version_id')) {
                Schema::table($table, static fn (Blueprint $blueprint) => $blueprint->dropColumn('document_version_id'));
            }
        }
        if (Schema::hasTable('km_pengajuans')) {
            $columns = array_values(array_filter(
                ['current_version_id', 'published_version_id'],
                static fn (string $column): bool => Schema::hasColumn('km_pengajuans', $column),
            ));
            if ($columns !== []) {
                Schema::table('km_pengajuans', static fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }

        Schema::dropIfExists('km_document_version_authors');
        Schema::dropIfExists('km_document_version_tags');
        Schema::dropIfExists('km_document_recovery_audits');
        Schema::dropIfExists('km_document_versions');
    }

    private function createVersions(): void
    {
        if (Schema::hasTable('km_document_versions')) {
            return;
        }

        Schema::create('km_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->integer('km_pengajuan_id');
            $table->unsignedInteger('version_major')->default(1);
            $table->unsignedInteger('version_minor')->default(0);
            $table->string('change_type', 16)->default('major');
            $table->text('change_note');
            $table->string('version_status', 32)->default('draft');

            $table->string('title');
            $table->text('synopsis')->nullable();
            // km_kategoris.id adalah signed BIGINT pada schema legacy.
            $table->bigInteger('category_id')->nullable();
            $table->string('audience')->nullable();
            $table->unsignedSmallInteger('reading_minutes')->nullable();

            $table->string('original_disk', 32)->nullable();
            $table->string('original_path', 1024)->nullable();
            $table->string('original_name')->nullable();
            $table->string('original_mime_type', 127)->nullable();
            $table->unsignedBigInteger('original_size_bytes')->nullable();
            $table->char('original_checksum_sha256', 64)->nullable();

            $table->string('normalized_pdf_disk', 32)->nullable();
            $table->string('normalized_pdf_path', 1024)->nullable();
            $table->unsignedBigInteger('normalized_pdf_size_bytes')->nullable();
            $table->char('normalized_pdf_checksum_sha256', 64)->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->longText('extracted_text')->nullable();

            $table->string('processing_status', 32)->default('pending_processing');
            $table->string('antivirus_status', 24)->default('pending');
            $table->unsignedTinyInteger('processing_attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['km_pengajuan_id', 'version_major', 'version_minor'],
                'km_document_versions_number_unique'
            );
            $table->index(
                ['processing_status', 'next_attempt_at', 'id'],
                'km_document_versions_processing_index'
            );
            $table->index(
                ['version_status', 'published_at', 'id'],
                'km_document_versions_publication_index'
            );
            $table->fullText(
                ['title', 'synopsis', 'extracted_text'],
                'km_document_versions_content_fulltext'
            );

            $table->foreign('km_pengajuan_id', 'km_document_versions_document_foreign')
                ->references('id')->on('km_pengajuans')->restrictOnDelete();
            $table->foreign('category_id', 'km_document_versions_category_foreign')
                ->references('id')->on('km_kategoris')->nullOnDelete();
            $table->foreign('created_by', 'km_document_versions_created_by_foreign')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'km_document_versions_approved_by_foreign')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createVersionPivots(): void
    {
        if (! Schema::hasTable('km_document_version_tags')) {
            Schema::create('km_document_version_tags', function (Blueprint $table): void {
                $table->unsignedBigInteger('document_version_id');
                $table->unsignedBigInteger('km_tag_id');
                $table->timestamps();
                $table->primary(['document_version_id', 'km_tag_id'], 'km_version_tags_primary');
                $table->foreign('document_version_id', 'km_version_tags_version_foreign')
                    ->references('id')->on('km_document_versions')->cascadeOnDelete();
                $table->foreign('km_tag_id', 'km_version_tags_tag_foreign')
                    ->references('id')->on('km_tags')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('km_document_version_authors')) {
            Schema::create('km_document_version_authors', function (Blueprint $table): void {
                $table->unsignedBigInteger('document_version_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->primary(['document_version_id', 'user_id'], 'km_version_authors_primary');
                $table->foreign('document_version_id', 'km_version_authors_version_foreign')
                    ->references('id')->on('km_document_versions')->cascadeOnDelete();
                $table->foreign('user_id', 'km_version_authors_user_foreign')
                    ->references('id')->on('users')->restrictOnDelete();
            });
        }
    }

    private function createRecoveryAudit(): void
    {
        if (Schema::hasTable('km_document_recovery_audits')) {
            return;
        }

        Schema::create('km_document_recovery_audits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('document_version_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason');
            $table->char('checksum_sha256', 64);
            $table->string('request_id', 128)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(
                ['document_version_id', 'created_at'],
                'km_recovery_version_created_index'
            );
            $table->foreign('document_version_id', 'km_recovery_version_foreign')
                ->references('id')->on('km_document_versions')->restrictOnDelete();
            $table->foreign('actor_id', 'km_recovery_actor_foreign')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    private function extendLegacyTables(): void
    {
        Schema::table('km_pengajuans', function (Blueprint $table): void {
            if (! Schema::hasColumn('km_pengajuans', 'current_version_id')) {
                $table->unsignedBigInteger('current_version_id')->nullable()->after('id');
                $table->index('current_version_id', 'km_pengajuans_current_version_index');
            }
            if (! Schema::hasColumn('km_pengajuans', 'published_version_id')) {
                $table->unsignedBigInteger('published_version_id')->nullable()->after('current_version_id');
                $table->index('published_version_id', 'km_pengajuans_published_version_index');
            }
        });

        foreach (['km_approval_events', 'km_transaksis', 'km_insights', 'km_point_ledger'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'document_version_id')) {
                continue;
            }
            Schema::table($tableName, static function (Blueprint $table) use ($tableName): void {
                $table->unsignedBigInteger('document_version_id')->nullable();
                $table->index('document_version_id', $tableName.'_version_index');
            });
        }
    }

    private function backfillVersions(): void
    {
        DB::table('km_pengajuans')->orderBy('id')->chunkById(200, function ($documents): void {
            foreach ($documents as $document) {
                $existing = DB::table('km_document_versions')
                    ->where('km_pengajuan_id', $document->id)
                    ->where('version_major', 1)
                    ->where('version_minor', 0)
                    ->value('id');

                $extension = strtolower(pathinfo((string) ($document->file_path ?? ''), PATHINFO_EXTENSION));
                $hasCompleteFile = ($document->file_disk ?? null) === 'km_private'
                    && ! empty($document->file_path)
                    && ! empty($document->file_checksum_sha256);
                $isPdf = $hasCompleteFile && $extension === 'pdf';
                $isOffice = $hasCompleteFile && in_array($extension, ['ppt', 'pptx'], true);
                $processing = $isPdf
                    ? KmProcessingStatus::READY->value
                    : ($isOffice ? KmProcessingStatus::PENDING->value : KmProcessingStatus::FAILED->value);
                $versionStatus = match ((int) $document->status) {
                    KmDocumentStatus::PUBLISHED->value => KmVersionStatus::PUBLISHED->value,
                    KmDocumentStatus::PENDING_APPROVAL->value => KmVersionStatus::PENDING_APPROVAL->value,
                    KmDocumentStatus::DRAFT->value => KmVersionStatus::DRAFT->value,
                    default => KmVersionStatus::WITHDRAWN->value,
                };

                $versionId = $existing !== null ? (int) $existing : (int) DB::table('km_document_versions')->insertGetId([
                    'km_pengajuan_id' => (int) $document->id,
                    'version_major' => 1,
                    'version_minor' => 0,
                    'change_type' => 'major',
                    'change_note' => 'Baseline migrasi dokumen legacy ke versioning KM.',
                    'version_status' => $versionStatus,
                    'title' => (string) ($document->judul ?? ''),
                    'synopsis' => $document->keterangan,
                    'category_id' => $document->id_km_kategori,
                    'audience' => $document->posisi,
                    'reading_minutes' => $document->reading_minutes ?? null,
                    'original_disk' => $document->file_disk ?? null,
                    'original_path' => $document->file_path ?? null,
                    'original_name' => $document->file_original_name ?? $document->file_name ?? null,
                    'original_mime_type' => $document->file_mime_type ?? null,
                    'original_size_bytes' => $document->file_size_bytes ?? null,
                    'original_checksum_sha256' => $document->file_checksum_sha256 ?? null,
                    'normalized_pdf_disk' => $isPdf ? ($document->file_disk ?? null) : null,
                    'normalized_pdf_path' => $isPdf ? ($document->file_path ?? null) : null,
                    'normalized_pdf_size_bytes' => $isPdf ? ($document->file_size_bytes ?? null) : null,
                    'normalized_pdf_checksum_sha256' => $isPdf ? ($document->file_checksum_sha256 ?? null) : null,
                    'processing_status' => $processing,
                    'antivirus_status' => $isPdf ? 'legacy_unscanned' : 'pending',
                    'processing_attempts' => 0,
                    'last_error' => $hasCompleteFile ? null : 'Metadata file legacy tidak lengkap.',
                    'processed_at' => $isPdf ? ($document->file_migrated_at ?? $document->updated_at) : null,
                    'created_by' => $document->id_user,
                    'published_at' => $versionStatus === KmVersionStatus::PUBLISHED->value
                        ? ($document->updated_at ?? $document->created_at)
                        : null,
                    'withdrawn_at' => $versionStatus === KmVersionStatus::WITHDRAWN->value
                        ? ($document->updated_at ?? $document->created_at)
                        : null,
                    'created_at' => $document->created_at ?? now(),
                    'updated_at' => $document->updated_at ?? now(),
                ]);

                DB::table('km_pengajuans')->where('id', $document->id)->update([
                    'current_version_id' => $versionId,
                    'published_version_id' => $versionStatus === KmVersionStatus::PUBLISHED->value
                        ? $versionId
                        : null,
                ]);

                $now = now();
                $tagIds = DB::table('km_document_tag')
                    ->where('km_pengajuan_id', $document->id)->pluck('km_tag_id');
                foreach ($tagIds as $tagId) {
                    DB::table('km_document_version_tags')->insertOrIgnore([
                        'document_version_id' => $versionId,
                        'km_tag_id' => (int) $tagId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                $authorIds = DB::table('km_document_authors')
                    ->where('km_pengajuan_id', $document->id)->pluck('user_id');
                foreach ($authorIds as $userId) {
                    DB::table('km_document_version_authors')->insertOrIgnore([
                        'document_version_id' => $versionId,
                        'user_id' => (int) $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        $relations = [
            'km_approval_events' => 'km_pengajuan_id',
            'km_transaksis' => 'id_km_pengajuan',
            'km_insights' => 'id_km_pengajuan',
            'km_point_ledger' => 'km_pengajuan_id',
        ];
        foreach ($relations as $table => $documentColumn) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            DB::statement(
                "UPDATE `{$table}` AS related "
                ."JOIN `km_pengajuans` AS document ON document.id = related.`{$documentColumn}` "
                .'SET related.document_version_id = COALESCE(document.published_version_id, document.current_version_id) '
                .'WHERE related.document_version_id IS NULL'
            );
        }
    }

    private function replaceProgressIdentity(): void
    {
        if (! Schema::hasTable('km_transaksis')) {
            return;
        }
        $this->dropIndexIfExists('km_transaksis', 'km_transaksis_user_document_unique');
        if (! $this->indexExists('km_transaksis', 'km_transaksis_user_version_unique')) {
            Schema::table('km_transaksis', static function (Blueprint $table): void {
                $table->unique(
                    ['id_user', 'document_version_id'],
                    'km_transaksis_user_version_unique'
                );
            });
        }
    }

    private function addForeignKeys(): void
    {
        $foreignKeys = [
            ['km_pengajuans', 'current_version_id', 'km_pengajuans_current_version_foreign'],
            ['km_pengajuans', 'published_version_id', 'km_pengajuans_published_version_foreign'],
            ['km_approval_events', 'document_version_id', 'km_approval_events_version_foreign'],
            ['km_transaksis', 'document_version_id', 'km_transaksis_version_foreign'],
            ['km_insights', 'document_version_id', 'km_insights_version_foreign'],
            ['km_point_ledger', 'document_version_id', 'km_point_ledger_version_foreign'],
        ];
        foreach ($foreignKeys as [$tableName, $column, $name]) {
            if (! Schema::hasTable($tableName) || $this->foreignExists($tableName, $name)) {
                continue;
            }
            Schema::table($tableName, static function (Blueprint $table) use ($column, $name): void {
                $table->foreign($column, $name)
                    ->references('id')->on('km_document_versions')->nullOnDelete();
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function foreignExists(string $table, string $name): bool
    {
        return DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->exists();
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    private function dropForeignIfExists(string $table, string $foreign): void
    {
        if (Schema::hasTable($table) && $this->foreignExists($table, $foreign)) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$foreign}`");
        }
    }

    private function guardTestingRollback(): void
    {
        if (! app()->environment('testing') || ! str_ends_with(DB::getDatabaseName(), '_testing')) {
            throw new RuntimeException(
                'Rollback migration versioning KM hanya diizinkan pada APP_ENV=testing dan database *_testing.'
            );
        }
    }
};
