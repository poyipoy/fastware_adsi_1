<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel rute approval per posisi.
     * Setiap baris menyatakan: posisi X di level Y diapprove oleh posisi Z.
     * approver_position_id merujuk ke mst_job_positions.id
     */
    public function up(): void
    {
        Schema::create('mst_position_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')
                  ->constrained('mst_job_positions')
                  ->onDelete('cascade')
                  ->comment('Posisi yang mengajukan');
            $table->unsignedTinyInteger('approval_level')
                  ->comment('Level approval: 1 = Section Head, 2 = Dept Head, 3 = Div Head');
            $table->foreignId('approver_position_id')
                  ->nullable()
                  ->constrained('mst_job_positions')
                  ->onDelete('set null')
                  ->comment('Posisi approver di level ini');
            $table->timestamps();

            $table->unique(['position_id', 'approval_level'], 'uq_position_approval_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_position_approvals');
    }
};
