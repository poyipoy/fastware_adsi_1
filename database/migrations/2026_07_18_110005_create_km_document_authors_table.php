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

        if (! Schema::hasTable('users')) {
            throw new RuntimeException(
                'Preflight failed: tabel users tidak ditemukan.'
            );
        }

        Schema::create('km_document_authors', function (Blueprint $table): void {
            $table->id();

            // km_pengajuans.id = signed int (legacy)
            $table->integer('km_pengajuan_id');
            $table->foreign('km_pengajuan_id')
                ->references('id')
                ->on('km_pengajuans')
                ->cascadeOnDelete();

            // RESTRICT on delete: atribusi tidak boleh hilang diam-diam bila user dihapus
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                ['km_pengajuan_id', 'user_id'],
                'km_document_authors_unique'
            );

            $table->index(
                ['user_id', 'km_pengajuan_id'],
                'km_document_authors_user_document_index'
            );
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('km_document_authors');
        Schema::enableForeignKeyConstraints();
    }
};
