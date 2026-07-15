<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [1] ADD JOB POSITION — Approval berbasis posisi
     * Tambah kolom approval_status dan div_head_name ke tc_job_positions.
     * Data lama default 'fully_approved' agar tidak terganggu.
     */
    public function up(): void
    {
        Schema::table('tc_job_positions', function (Blueprint $table) {
            $table->string('approval_status', 30)->default('fully_approved')
                ->after('status')
                ->comment('pending|approved_sh|approved_dh|approved_dvh|fully_approved');

            $table->string('div_head_name', 255)->nullable()
                ->after('department_head_name')
                ->comment('Nama Div Head (opsional)');

            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('tc_job_positions', function (Blueprint $table) {
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['approval_status', 'div_head_name']);
        });
    }
};
