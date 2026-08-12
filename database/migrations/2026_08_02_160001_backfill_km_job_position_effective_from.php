<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_job_positions')
            || ! Schema::hasColumn('user_job_positions', 'effective_from')) {
            return;
        }

        DB::table('user_job_positions')
            ->whereNull('effective_from')
            ->update([
                'effective_from' => DB::raw('DATE(COALESCE(`created_at`, CURRENT_TIMESTAMP))'),
            ]);
    }

    public function down(): void
    {
        // Data repair cannot safely infer which effective dates were previously null.
    }
};
