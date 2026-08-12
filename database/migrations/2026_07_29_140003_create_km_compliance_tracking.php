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
            throw new RuntimeException('Migration compliance KM memerlukan MySQL.');
        }
        foreach (['users', 'km_pengajuans', 'km_document_versions', 'km_transaksis', 'km_notifications'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Preflight compliance KM gagal: tabel {$table} tidak ditemukan.");
            }
        }

        if (! Schema::hasTable('km_reading_sessions')) {
            Schema::create('km_reading_sessions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->integer('document_id');
                $table->unsignedBigInteger('document_version_id');
                $table->char('session_hash', 64);
                $table->char('device_hash', 64)->nullable();
                $table->unsignedBigInteger('client_active_seconds')->default(0);
                $table->unsignedBigInteger('credited_active_seconds')->default(0);
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'document_version_id', 'session_hash'], 'km_reading_sessions_user_version_session_unique');
                $table->index(['user_id', 'document_version_id', 'last_seen_at'], 'km_reading_sessions_active_index');
                $table->foreign('user_id', 'km_reading_sessions_user_foreign')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('document_id', 'km_reading_sessions_document_foreign')->references('id')->on('km_pengajuans')->cascadeOnDelete();
                $table->foreign('document_version_id', 'km_reading_sessions_version_foreign')->references('id')->on('km_document_versions')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('km_assignments')) {
            Schema::create('km_assignments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('document_version_id');
                $table->string('title');
                $table->string('status', 24)->default('active');
                $table->timestamp('due_at');
                $table->json('target_snapshot');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->text('reason');
                $table->timestamps();
                $table->index(['status', 'due_at'], 'km_assignments_status_due_index');
                $table->foreign('document_version_id', 'km_assignments_version_foreign')->references('id')->on('km_document_versions')->restrictOnDelete();
                $table->foreign('created_by', 'km_assignments_creator_foreign')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('km_assignment_users')) {
            Schema::create('km_assignment_users', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('assignment_id');
                $table->unsignedBigInteger('user_id');
                $table->string('department_snapshot')->nullable();
                $table->string('job_position_snapshot')->nullable();
                $table->timestamp('due_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('exempted_at')->nullable();
                $table->unsignedBigInteger('exempted_by')->nullable();
                $table->text('exemption_reason')->nullable();
                $table->unsignedBigInteger('completion_event_id')->nullable();
                $table->timestamp('reminded_h3_at')->nullable();
                $table->timestamp('reminded_h1_at')->nullable();
                $table->timestamp('overdue_notified_at')->nullable();
                $table->timestamps();
                $table->unique(['assignment_id', 'user_id'], 'km_assignment_users_assignment_user_unique');
                $table->index(['user_id', 'completed_at', 'due_at'], 'km_assignment_users_user_completion_index');
                $table->index(['completed_at', 'exempted_at', 'due_at'], 'km_assignment_users_reminder_index');
                $table->foreign('assignment_id', 'km_assignment_users_assignment_foreign')->references('id')->on('km_assignments')->cascadeOnDelete();
                $table->foreign('user_id', 'km_assignment_users_user_foreign')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('exempted_by', 'km_assignment_users_exempted_by_foreign')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('km_completion_events')) {
            Schema::create('km_completion_events', function (Blueprint $table): void {
                $table->id();
                $table->string('event_key', 191)->unique('km_completion_events_event_unique');
                $table->unsignedBigInteger('user_id');
                $table->integer('document_id');
                $table->unsignedBigInteger('document_version_id');
                $table->integer('transaction_id')->nullable();
                $table->unsignedBigInteger('assignment_user_id')->nullable();
                $table->string('completion_type', 24);
                $table->timestamp('acknowledged_at')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->text('reason')->nullable();
                $table->json('evidence_snapshot');
                $table->timestamp('completed_at');
                $table->timestamps();
                $table->index(['user_id', 'document_version_id'], 'km_completion_events_user_version_index');
                $table->foreign('user_id', 'km_completion_events_user_foreign')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('document_id', 'km_completion_events_document_foreign')->references('id')->on('km_pengajuans')->restrictOnDelete();
                $table->foreign('document_version_id', 'km_completion_events_version_foreign')->references('id')->on('km_document_versions')->restrictOnDelete();
                $table->foreign('transaction_id', 'km_completion_events_transaction_foreign')->references('id')->on('km_transaksis')->nullOnDelete();
                $table->foreign('assignment_user_id', 'km_completion_events_assignment_user_foreign')->references('id')->on('km_assignment_users')->nullOnDelete();
                $table->foreign('actor_id', 'km_completion_events_actor_foreign')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! app()->environment('testing') || ! str_ends_with(DB::getDatabaseName(), '_testing')) {
            throw new RuntimeException('Rollback migration compliance KM hanya untuk database *_testing.');
        }
        Schema::dropIfExists('km_completion_events');
        Schema::dropIfExists('km_assignment_users');
        Schema::dropIfExists('km_assignments');
        Schema::dropIfExists('km_reading_sessions');
    }
};
