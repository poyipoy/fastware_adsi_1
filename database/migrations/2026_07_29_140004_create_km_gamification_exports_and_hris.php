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
            throw new RuntimeException('Migration gamifikasi dan integrasi KM memerlukan MySQL.');
        }
        foreach (['users', 'km_completion_events', 'km_assignments'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Preflight gamifikasi KM gagal: tabel {$table} tidak ditemukan.");
            }
        }

        if (! Schema::hasTable('km_badges')) {
            Schema::create('km_badges', function (Blueprint $table): void {
                $table->id();
                $table->string('slug', 80)->unique('km_badges_slug_unique');
                $table->string('name');
                $table->text('description');
                $table->string('event_type', 48);
                $table->unsignedInteger('threshold');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('km_user_badges')) {
            Schema::create('km_user_badges', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('badge_id');
                $table->string('event_key', 191);
                $table->json('evidence');
                $table->timestamp('awarded_at');
                $table->timestamps();
                $table->unique(['user_id', 'badge_id'], 'km_user_badges_user_badge_unique');
                $table->unique('event_key', 'km_user_badges_event_unique');
                $table->foreign('user_id', 'km_user_badges_user_foreign')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('badge_id', 'km_user_badges_badge_foreign')->references('id')->on('km_badges')->restrictOnDelete();
            });
        }
        if (! Schema::hasTable('km_export_audits')) {
            Schema::create('km_export_audits', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('export_type', 64);
                $table->string('format', 8);
                $table->json('filters');
                $table->unsignedInteger('record_count');
                $table->string('file_name');
                $table->char('checksum_sha256', 64);
                $table->timestamp('created_at')->useCurrent();
                $table->index(['actor_id', 'created_at'], 'km_export_audits_actor_created_index');
                $table->foreign('actor_id', 'km_export_audits_actor_foreign')->references('id')->on('users')->nullOnDelete();
            });
        }
        if (! Schema::hasTable('km_hris_outbound_events')) {
            Schema::create('km_hris_outbound_events', function (Blueprint $table): void {
                $table->id();
                $table->string('event_key', 191)->unique('km_hris_outbound_event_unique');
                $table->unsignedBigInteger('completion_event_id');
                $table->string('employee_hris_id', 120);
                $table->json('payload');
                $table->string('status', 24)->default('pending');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('next_attempt_at')->nullable();
                $table->text('last_error')->nullable();
                $table->char('response_checksum_sha256', 64)->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'next_attempt_at', 'id'], 'km_hris_outbound_pending_index');
                $table->foreign('completion_event_id', 'km_hris_outbound_completion_foreign')->references('id')->on('km_completion_events')->restrictOnDelete();
            });
        }

        $now = now();
        DB::table('km_badges')->upsert([
            ['slug' => 'first-reading', 'name' => 'Bacaan Pertama', 'description' => 'Menyelesaikan bacaan pertama.', 'event_type' => 'completion', 'threshold' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'ten-readings', 'name' => '10 Bacaan Selesai', 'description' => 'Menyelesaikan sepuluh materi.', 'event_type' => 'completion', 'threshold' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'first-publication', 'name' => 'Publikasi Pertama', 'description' => 'Menerbitkan materi pertama.', 'event_type' => 'publication', 'threshold' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'five-publications', 'name' => '5 Publikasi', 'description' => 'Menerbitkan lima materi.', 'event_type' => 'publication', 'threshold' => 5, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'first-featured-insight', 'name' => 'Insight Pilihan Pertama', 'description' => 'Mendapatkan Insight Pilihan pertama.', 'event_type' => 'featured_insight', 'threshold' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ], ['slug'], ['name', 'description', 'event_type', 'threshold', 'is_active', 'updated_at']);
    }

    public function down(): void
    {
        if (! app()->environment('testing') || ! str_ends_with(DB::getDatabaseName(), '_testing')) {
            throw new RuntimeException('Rollback migration gamifikasi KM hanya untuk database *_testing.');
        }
        Schema::dropIfExists('km_hris_outbound_events');
        Schema::dropIfExists('km_export_audits');
        Schema::dropIfExists('km_user_badges');
        Schema::dropIfExists('km_badges');
    }
};
