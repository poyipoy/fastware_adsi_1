<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('km_kategoris')) {
            Schema::create('km_kategoris', function (Blueprint $table): void {
                $table->bigInteger('id', true);
                $table->string('nama_kategori');
                $table->integer('poin_kategori');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('km_pengajuans')) {
            Schema::create('km_pengajuans', function (Blueprint $table): void {
                // Keep the signed INT legacy document key for compatibility.
                $table->integer('id', true);
                $table->unsignedBigInteger('id_user')->nullable();
                $table->bigInteger('id_km_kategori')->nullable();
                $table->string('judul')->nullable();
                $table->string('keterangan', 3000)->nullable();
                $table->string('posisi')->nullable();
                $table->string('image')->nullable();
                $table->string('file')->nullable();
                $table->string('file_name')->nullable();
                $table->string('persetujuan')->nullable();
                $table->integer('status');
                $table->timestamps();
                $table->string('modified_by')->nullable();
            });
        }

        if (! Schema::hasTable('km_transaksis')) {
            Schema::create('km_transaksis', function (Blueprint $table): void {
                $table->integer('id', true);
                $table->integer('id_km_pengajuan')->nullable();
                $table->unsignedBigInteger('id_user')->nullable();
                $table->integer('poin')->nullable();
                $table->integer('level')->nullable();
                $table->integer('status');
                $table->timestamps();
                $table->unsignedBigInteger('modified_by')->nullable();
            });
        }

        if (! Schema::hasTable('km_lihat_bukus')) {
            Schema::create('km_lihat_bukus', function (Blueprint $table): void {
                $table->bigInteger('id', true);
                $table->integer('id_km_transaksi')->nullable();
                $table->integer('id_km_pengajuan')->nullable();
                $table->unsignedBigInteger('jumlah_lihat')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('km_sukas')) {
            Schema::create('km_sukas', function (Blueprint $table): void {
                $table->bigInteger('id', true);
                $table->unsignedBigInteger('id_user')->nullable();
                $table->integer('id_km_pengajuan')->nullable();
                $table->bigInteger('jumlah_like')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('km_insights')) {
            Schema::create('km_insights', function (Blueprint $table): void {
                $table->integer('id', true);
                $table->unsignedBigInteger('id_user')->nullable();
                $table->integer('id_km_pengajuan')->nullable();
                $table->string('content', 1200)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $database = (string) DB::connection()->getDatabaseName();

        if (! app()->environment('testing') || ! str_ends_with($database, '_testing')) {
            throw new RuntimeException(
                'KM baseline rollback is only allowed when APP_ENV=testing and DB_DATABASE ends with _testing.'
            );
        }

        Schema::dropIfExists('km_insights');
        Schema::dropIfExists('km_sukas');
        Schema::dropIfExists('km_lihat_bukus');
        Schema::dropIfExists('km_transaksis');
        Schema::dropIfExists('km_pengajuans');
        Schema::dropIfExists('km_kategoris');
    }
};
