<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add new integer columns (nullable) while keeping old varchar columns
        Schema::table('mst_pd_pengajuans', function (Blueprint $table) {
            $table->unsignedBigInteger('new_job_position_id')->nullable()->after('id_job_position');
            $table->unsignedBigInteger('section_id')->nullable()->after('section');
        });

        // Step 2: Map old VARCHAR text values to IDs using the new master tables
        // Map id_job_position (text like "IT Staff") → mst_job_positions.id
        $positions = DB::table('mst_job_positions')->get(['id', 'position_name']);
        $positionMap = $positions->mapWithKeys(fn($p) => [mb_strtolower(trim($p->position_name)) => $p->id]);

        // Map section (text like "PDCA, Procurement, IT") → mst_sections.id
        $sections = DB::table('mst_sections')->get(['id', 'name']);
        $sectionMap = $sections->mapWithKeys(fn($s) => [mb_strtolower(trim($s->name)) => $s->id]);

        $rows = DB::table('mst_pd_pengajuans')->get(['id', 'id_job_position', 'section']);
        foreach ($rows as $row) {
            $jpKey = mb_strtolower(trim((string) $row->id_job_position));
            $secKey = mb_strtolower(trim((string) $row->section));

            DB::table('mst_pd_pengajuans')->where('id', $row->id)->update([
                'new_job_position_id' => $positionMap[$jpKey] ?? null,
                'section_id' => $sectionMap[$secKey] ?? null,
            ]);
        }

        // Step 3: Drop old varchar columns
        Schema::table('mst_pd_pengajuans', function (Blueprint $table) {
            $table->dropColumn(['id_job_position', 'section']);
        });

        // Step 4: Rename new_job_position_id → id_job_position
        Schema::table('mst_pd_pengajuans', function (Blueprint $table) {
            $table->renameColumn('new_job_position_id', 'id_job_position');
        });
    }

    public function down(): void
    {
        // Reverse: add back varchar columns, populate from IDs, drop id columns
        Schema::table('mst_pd_pengajuans', function (Blueprint $table) {
            $table->string('old_id_job_position')->nullable()->after('id_job_position');
            $table->string('old_section')->nullable()->after('section_id');
        });

        $positions = DB::table('mst_job_positions')->get(['id', 'position_name']);
        $posMap = $positions->mapWithKeys(fn($p) => [$p->id => $p->position_name]);
        $sections = DB::table('mst_sections')->get(['id', 'name']);
        $secMap = $sections->mapWithKeys(fn($s) => [$s->id => $s->name]);

        $rows = DB::table('mst_pd_pengajuans')->get(['id', 'id_job_position', 'section_id']);
        foreach ($rows as $row) {
            DB::table('mst_pd_pengajuans')->where('id', $row->id)->update([
                'old_id_job_position' => $posMap[$row->id_job_position] ?? null,
                'old_section' => $secMap[$row->section_id] ?? null,
            ]);
        }

        Schema::table('mst_pd_pengajuans', function (Blueprint $table) {
            $table->dropColumn(['id_job_position', 'section_id']);
        });

        Schema::table('mst_pd_pengajuans', function (Blueprint $table) {
            $table->renameColumn('old_id_job_position', 'id_job_position');
            $table->renameColumn('old_section', 'section');
        });
    }
};
