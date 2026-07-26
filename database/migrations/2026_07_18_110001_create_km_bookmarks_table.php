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

        if (! Schema::hasTable('users')) {
            throw new RuntimeException(
                'Preflight failed: tabel users tidak ditemukan.'
            );
        }

        Schema::create('km_bookmarks', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // km_pengajuans.id menggunakan tipe int (signed) — ikuti tipe legacy
            $table->integer('km_pengajuan_id');
            $table->foreign('km_pengajuan_id')
                ->references('id')
                ->on('km_pengajuans')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['user_id', 'km_pengajuan_id'],
                'km_bookmarks_user_document_unique'
            );

            // Index balik untuk lookup per dokumen
            $table->index(
                ['km_pengajuan_id', 'user_id'],
                'km_bookmarks_document_user_index'
            );
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('km_bookmarks');
        Schema::enableForeignKeyConstraints();
    }
};
