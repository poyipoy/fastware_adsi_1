<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $positions = [
        'Business Development',
        'Sales Dept Head Region 1&2',
        'Sales Dept Head Region 3&4',
        'Finance Accounting & HRGA Dept Head',
        'PDCA Proc Inv IT Dept Head',
        'Key Account Management',
        'Sales Engineer 1',
        'Production Dept Head',
        'Sales Engineer Region 2',
        'Sales Engineer Region 3',
        'Sales Engineer Region 4',
        'Production Heat Treatment Sect. Head',
        'Machining & MC Custom Sec Head',
        'Logistic & Warehouses Dept Head'
    ];

    public function up(): void
    {
        // 1. Reset all to false
        DB::table('mst_job_positions')->update(['is_key_position' => false]);

        // 2. Set the requested ones to true using a robust LIKE match
        foreach ($this->positions as $pos) {
            // Replace special characters with % for a safer LIKE match (e.g., handling spaces around &)
            $safePos = str_replace(['&', '(', ')', '.'], '%', $pos);
            
            // e.g. "Sales Dept Head Region 1%2"
            DB::table('mst_job_positions')
                ->whereRaw('LOWER(position_name) LIKE ?', ['%' . strtolower($safePos) . '%'])
                ->update(['is_key_position' => true]);
        }
    }

    public function down(): void
    {
        DB::table('mst_job_positions')->update(['is_key_position' => false]);
    }
};
