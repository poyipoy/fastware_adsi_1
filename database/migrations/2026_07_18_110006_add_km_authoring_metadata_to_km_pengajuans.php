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
                'Preflight failed: tabel km_pengajuans tidak ditemukan.'
            );
        }

        Schema::table('km_pengajuans', function (Blueprint $table): void {
            $table->unsignedSmallInteger('reading_minutes')->nullable()->after('keterangan');
            $table->unsignedBigInteger('draft_revision')->default(0)->after('reading_minutes');
            $table->timestamp('autosaved_at')->nullable()->after('draft_revision');

            // Composite index untuk daftar draft per owner — gunakan nama kolom dari Jangka Pendek
            // km_pengajuans memiliki: id_user, status, updated_at
            $table->index(
                ['id_user', 'status', 'updated_at'],
                'km_pengajuans_draft_owner_index'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('km_pengajuans')) {
            return;
        }

        Schema::table('km_pengajuans', function (Blueprint $table): void {
            $table->dropIndex('km_pengajuans_draft_owner_index');
            $table->dropColumn(['reading_minutes', 'draft_revision', 'autosaved_at']);
        });
    }
};
