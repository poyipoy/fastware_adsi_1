<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mst_tc', function (Blueprint $table) {
            $table->dropColumn(['deskripsi_level_1', 'deskripsi_level_2', 'deskripsi_level_3', 'deskripsi_level_4']);
        });

        Schema::table('mst_soft_skills', function (Blueprint $table) {
            $table->dropColumn(['deskripsi_level_1', 'deskripsi_level_2', 'deskripsi_level_3', 'deskripsi_level_4']);
        });

        Schema::table('mst_additionals', function (Blueprint $table) {
            $table->dropColumn(['deskripsi_level_1', 'deskripsi_level_2', 'deskripsi_level_3', 'deskripsi_level_4']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_tc', function (Blueprint $table) {
            $table->text('deskripsi_level_1')->nullable();
            $table->text('deskripsi_level_2')->nullable();
            $table->text('deskripsi_level_3')->nullable();
            $table->text('deskripsi_level_4')->nullable();
        });

        Schema::table('mst_soft_skills', function (Blueprint $table) {
            $table->text('deskripsi_level_1')->nullable();
            $table->text('deskripsi_level_2')->nullable();
            $table->text('deskripsi_level_3')->nullable();
            $table->text('deskripsi_level_4')->nullable();
        });

        Schema::table('mst_additionals', function (Blueprint $table) {
            $table->text('deskripsi_level_1')->nullable();
            $table->text('deskripsi_level_2')->nullable();
            $table->text('deskripsi_level_3')->nullable();
            $table->text('deskripsi_level_4')->nullable();
        });
    }
};
