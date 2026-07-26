<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('km_pengajuans')) {
            throw new RuntimeException(
                'Preflight failed: tabel km_pengajuans tidak ditemukan. '
                . 'Jalankan migration Jangka Pendek terlebih dahulu.'
            );
        }

        Schema::table('km_pengajuans', function (Blueprint $table): void {
            $table->string('thumbnail_path', 255)->nullable()->after('image');
            $table->string('thumbnail_status', 20)->default('missing')->after('thumbnail_path');
            $table->char('thumbnail_source_checksum', 64)->nullable()->after('thumbnail_status');
            $table->timestamp('thumbnail_generated_at')->nullable()->after('thumbnail_source_checksum');
            $table->string('thumbnail_failure_reason', 500)->nullable()->after('thumbnail_generated_at');

            // Index untuk worker/backfill
            $table->index(
                ['thumbnail_status', 'updated_at'],
                'km_pengajuans_thumbnail_status_updated_at_index'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('km_pengajuans')) {
            return;
        }

        Schema::table('km_pengajuans', function (Blueprint $table): void {
            // Drop index dulu sebelum kolom
            $table->dropIndex('km_pengajuans_thumbnail_status_updated_at_index');
            $table->dropColumn([
                'thumbnail_path',
                'thumbnail_status',
                'thumbnail_source_checksum',
                'thumbnail_generated_at',
                'thumbnail_failure_reason',
            ]);
        });
    }
};
