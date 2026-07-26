<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('km_tags')) {
            throw new RuntimeException(
                'Preflight failed: tabel km_tags tidak ditemukan. '
                . 'Jalankan migration 110003 terlebih dahulu.'
            );
        }

        if (! Schema::hasTable('km_pengajuans')) {
            throw new RuntimeException(
                'Preflight failed: tabel km_pengajuans tidak ditemukan.'
            );
        }

        Schema::create('km_document_tag', function (Blueprint $table): void {
            // km_pengajuans.id = signed int (legacy type dipertahankan)
            $table->integer('km_pengajuan_id');
            $table->foreign('km_pengajuan_id')
                ->references('id')
                ->on('km_pengajuans')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('km_tag_id');
            $table->foreign('km_tag_id')
                ->references('id')
                ->on('km_tags')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['km_pengajuan_id', 'km_tag_id'],
                'km_document_tag_unique'
            );

            // Index balik untuk lookup per tag
            $table->index(
                ['km_tag_id', 'km_pengajuan_id'],
                'km_document_tag_reverse_index'
            );
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('km_document_tag');
        Schema::enableForeignKeyConstraints();
    }
};
