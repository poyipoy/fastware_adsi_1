<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Migration access dan targeting KM memerlukan MySQL.');
        }
        foreach (['users', 'roles', 'user_job_positions', 'mst_job_positions', 'mst_departments', 'km_document_versions', 'km_notifications'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Preflight access/targeting KM gagal: tabel {$table} tidak ditemukan.");
            }
        }

        $this->extendAssignments();
        $this->createAccessTables();
        $this->createTargetTables();
        $this->createPublicationTables();
    }

    public function down(): void
    {
        $this->guardTestingRollback();
        Schema::dropIfExists('km_publication_recipients');
        Schema::dropIfExists('km_publication_batches');
        Schema::dropIfExists('km_document_version_job_positions');
        Schema::dropIfExists('km_document_version_departments');
        Schema::dropIfExists('km_organization_assignment_audits');
        Schema::dropIfExists('km_access_audits');
        Schema::dropIfExists('km_access_rules');

        if (Schema::hasTable('user_job_positions')) {
            if (! $this->indexExists('user_job_positions', 'uq_user_position')) {
                Schema::table('user_job_positions', static fn (Blueprint $table) => $table->unique(
                    ['user_id', 'mst_job_position_id'],
                    'uq_user_position',
                ));
            }
            foreach (['km_user_job_positions_effective_index', 'km_user_job_positions_position_effective_index'] as $index) {
                $this->dropIndexIfExists('user_job_positions', $index);
            }
            $this->dropIndexIfExists('user_job_positions', 'km_user_job_positions_user_fk_index');
            $columns = array_values(array_filter(
                ['effective_from', 'effective_until', 'assignment_source'],
                static fn (string $column): bool => Schema::hasColumn('user_job_positions', $column),
            ));
            if ($columns !== []) {
                Schema::table('user_job_positions', static fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
    }

    private function extendAssignments(): void
    {
        if ($this->indexExists('user_job_positions', 'uq_user_position')) {
            if (! $this->indexExists('user_job_positions', 'km_user_job_positions_user_fk_index')
                && ! $this->indexExists('user_job_positions', 'km_user_job_positions_effective_index')) {
                Schema::table('user_job_positions', static fn (Blueprint $table) => $table->index(
                    'user_id',
                    'km_user_job_positions_user_fk_index',
                ));
            }
            DB::statement('ALTER TABLE `user_job_positions` DROP INDEX `uq_user_position`');
        }
        Schema::table('user_job_positions', function (Blueprint $table): void {
            if (! Schema::hasColumn('user_job_positions', 'effective_from')) {
                $table->date('effective_from')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('user_job_positions', 'effective_until')) {
                $table->date('effective_until')->nullable()->after('effective_from');
            }
            if (! Schema::hasColumn('user_job_positions', 'assignment_source')) {
                $table->string('assignment_source', 64)->default('legacy')->after('effective_until');
            }
        });
        DB::statement(
            'UPDATE `user_job_positions` SET `effective_from` = DATE(COALESCE(`created_at`, CURRENT_TIMESTAMP)) '
            .'WHERE `effective_from` IS NULL'
        );
        Schema::table('user_job_positions', function (Blueprint $table): void {
            if (! $this->indexExists('user_job_positions', 'km_user_job_positions_effective_index')) {
                $table->index(
                    ['user_id', 'is_active', 'effective_from', 'effective_until'],
                    'km_user_job_positions_effective_index',
                );
            }
            if (! $this->indexExists('user_job_positions', 'km_user_job_positions_position_effective_index')) {
                $table->index(
                    ['mst_job_position_id', 'is_active', 'effective_from', 'effective_until'],
                    'km_user_job_positions_position_effective_index',
                );
            }
        });
        if ($this->indexExists('user_job_positions', 'km_user_job_positions_user_fk_index')) {
            $this->dropIndexIfExists('user_job_positions', 'km_user_job_positions_user_fk_index');
        }
    }

    private function createAccessTables(): void
    {
        if (! Schema::hasTable('km_access_rules')) {
            Schema::create('km_access_rules', function (Blueprint $table): void {
                $table->id();
                $table->string('subject_type', 24);
                $table->unsignedBigInteger('subject_id');
                $table->string('ability', 80);
                $table->string('effect', 8);
                $table->timestamp('valid_from')->nullable();
                $table->timestamp('valid_until')->nullable();
                $table->text('reason');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(
                    ['subject_type', 'subject_id', 'ability', 'valid_from', 'valid_until'],
                    'km_access_rules_subject_ability_effective_index',
                );
                $table->index(['ability', 'effect'], 'km_access_rules_ability_effect_index');
                $table->foreign('created_by', 'km_access_rules_creator_foreign')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }
        if (! Schema::hasTable('km_access_audits')) {
            Schema::create('km_access_audits', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('access_rule_id')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('action', 32);
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->text('reason');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['access_rule_id', 'created_at'], 'km_access_audits_rule_created_index');
                $table->foreign('access_rule_id', 'km_access_audits_rule_foreign')
                    ->references('id')->on('km_access_rules')->nullOnDelete();
                $table->foreign('actor_id', 'km_access_audits_actor_foreign')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }
        if (! Schema::hasTable('km_organization_assignment_audits')) {
            Schema::create('km_organization_assignment_audits', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_job_position_id')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('action', 32);
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->text('reason');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['user_job_position_id', 'created_at'], 'km_org_assignment_audits_assignment_index');
                $table->foreign('user_job_position_id', 'km_org_assignment_audits_assignment_foreign')
                    ->references('id')->on('user_job_positions')->nullOnDelete();
                $table->foreign('actor_id', 'km_org_assignment_audits_actor_foreign')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    private function createTargetTables(): void
    {
        if (! Schema::hasTable('km_document_version_departments')) {
            Schema::create('km_document_version_departments', function (Blueprint $table): void {
                $table->unsignedBigInteger('document_version_id');
                $table->unsignedBigInteger('department_id');
                $table->timestamps();
                $table->primary(['document_version_id', 'department_id'], 'km_version_departments_primary');
                $table->foreign('document_version_id', 'km_version_departments_version_foreign')
                    ->references('id')->on('km_document_versions')->cascadeOnDelete();
                $table->foreign('department_id', 'km_version_departments_department_foreign')
                    ->references('id')->on('mst_departments')->restrictOnDelete();
            });
        }
        if (! Schema::hasTable('km_document_version_job_positions')) {
            Schema::create('km_document_version_job_positions', function (Blueprint $table): void {
                $table->unsignedBigInteger('document_version_id');
                $table->unsignedBigInteger('job_position_id');
                $table->timestamps();
                $table->primary(['document_version_id', 'job_position_id'], 'km_version_positions_primary');
                $table->foreign('document_version_id', 'km_version_positions_version_foreign')
                    ->references('id')->on('km_document_versions')->cascadeOnDelete();
                $table->foreign('job_position_id', 'km_version_positions_position_foreign')
                    ->references('id')->on('mst_job_positions')->restrictOnDelete();
            });
        }
    }

    private function createPublicationTables(): void
    {
        if (! Schema::hasTable('km_publication_batches')) {
            Schema::create('km_publication_batches', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('document_version_id');
                $table->string('status', 24)->default('pending');
                $table->unsignedInteger('recipient_count')->default(0);
                $table->unsignedInteger('processed_count')->default(0);
                $table->text('last_error')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique('document_version_id', 'km_publication_batches_version_unique');
                $table->index(['status', 'id'], 'km_publication_batches_status_index');
                $table->foreign('document_version_id', 'km_publication_batches_version_foreign')
                    ->references('id')->on('km_document_versions')->restrictOnDelete();
            });
        }
        if (! Schema::hasTable('km_publication_recipients')) {
            Schema::create('km_publication_recipients', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('publication_batch_id');
                $table->unsignedBigInteger('user_id');
                $table->string('department_snapshot')->nullable();
                $table->string('job_position_snapshot')->nullable();
                $table->timestamp('notified_at')->nullable();
                $table->unsignedBigInteger('notification_id')->nullable();
                $table->timestamp('inaccessible_at')->nullable();
                $table->timestamps();
                $table->unique(
                    ['publication_batch_id', 'user_id'],
                    'km_publication_recipients_batch_user_unique',
                );
                $table->index(
                    ['publication_batch_id', 'notified_at', 'id'],
                    'km_publication_recipients_pending_index',
                );
                $table->foreign('publication_batch_id', 'km_publication_recipients_batch_foreign')
                    ->references('id')->on('km_publication_batches')->cascadeOnDelete();
                $table->foreign('user_id', 'km_publication_recipients_user_foreign')
                    ->references('id')->on('users')->restrictOnDelete();
                $table->foreign('notification_id', 'km_publication_recipients_notification_foreign')
                    ->references('id')->on('km_notifications')->nullOnDelete();
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

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    private function guardTestingRollback(): void
    {
        if (! app()->environment('testing') || ! str_ends_with(DB::getDatabaseName(), '_testing')) {
            throw new RuntimeException('Rollback migration access/targeting KM hanya untuk database *_testing.');
        }
    }
};
