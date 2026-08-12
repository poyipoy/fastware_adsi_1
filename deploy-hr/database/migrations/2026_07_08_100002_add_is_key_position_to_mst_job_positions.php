<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Daftar nama job position yang merupakan Key Position.
     * Matching dilakukan secara case-insensitive dan LIKE.
     */
    private array $keyPositionKeywords = [
        'Business Development',
        'Sales Dept Head Region 1',
        'Sales Dept Head Region 2',
        'Sales Dept Head Region 3',
        'Sales Dept Head Region 4',
        'Finance Accounting',
        'HRGA Dept Head',
        'PDCA Proc Inv IT Dept Head',
        'Key Account Management',
        'Sales Engineer',
        'Production Dept Head',
        'Production Heat Treatment Sect',
        'Machining',
        'MC Custom Sec Head',
        'Logistic',
        'Warehouses Dept Head',
    ];

    public function up(): void
    {
        // 1. Add column
        Schema::table('mst_job_positions', function (Blueprint $table) {
            $table->boolean('is_key_position')->default(false)->after('is_active');
        });

        // 2. Seed initial key positions based on keywords
        foreach ($this->keyPositionKeywords as $keyword) {
            DB::table('mst_job_positions')
                ->whereRaw('LOWER(position_name) LIKE ?', ['%' . strtolower($keyword) . '%'])
                ->update(['is_key_position' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('mst_job_positions', function (Blueprint $table) {
            $table->dropColumn('is_key_position');
        });
    }
};
