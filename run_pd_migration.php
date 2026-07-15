<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    // Check if columns already migrated
    $cols = Schema::getColumnListing('mst_pd_pengajuans');
    echo "Current columns: " . implode(', ', $cols) . "\n\n";
    
    $hasOldJobPos = in_array('id_job_position', $cols);
    $hasSection = in_array('section', $cols);
    $hasSectionId = in_array('section_id', $cols);
    
    echo "Has id_job_position (varchar): " . ($hasOldJobPos ? 'YES' : 'NO') . "\n";
    echo "Has section (varchar): " . ($hasSection ? 'YES' : 'NO') . "\n";
    echo "Has section_id (int): " . ($hasSectionId ? 'YES' : 'NO') . "\n\n";
    
    if (!$hasOldJobPos && !$hasSection) {
        echo "Already migrated!\n";
        exit;
    }
    
    // Step 1: Add new integer columns if not exist
    if (!in_array('new_job_position_id', $cols)) {
        DB::statement('ALTER TABLE mst_pd_pengajuans ADD COLUMN new_job_position_id BIGINT UNSIGNED NULL AFTER id_job_position');
        echo "Added new_job_position_id column\n";
    }
    if (!$hasSectionId) {
        DB::statement('ALTER TABLE mst_pd_pengajuans ADD COLUMN section_id BIGINT UNSIGNED NULL AFTER section');
        echo "Added section_id column\n";
    }
    
    // Step 2: Build maps
    $positions = DB::table('mst_job_positions')->get(['id', 'position_name']);
    $positionMap = $positions->mapWithKeys(fn($p) => [mb_strtolower(trim($p->position_name)) => $p->id]);
    $sections = DB::table('mst_sections')->get(['id', 'name']);
    $sectionMap = $sections->mapWithKeys(fn($s) => [mb_strtolower(trim($s->name)) => $s->id]);
    
    echo "Loaded " . count($positionMap) . " job positions\n";
    echo "Loaded " . count($sectionMap) . " sections\n";
    
    // Step 3: Populate new columns
    $rows = DB::table('mst_pd_pengajuans')->get(['id', 'id_job_position', 'section']);
    $matched = 0; $unmatched = [];
    
    foreach ($rows as $row) {
        $jpKey = mb_strtolower(trim((string) $row->id_job_position));
        $secKey = mb_strtolower(trim((string) $row->section));
        
        $jpId = $positionMap[$jpKey] ?? null;
        $secId = $sectionMap[$secKey] ?? null;
        
        if (!$jpId && $jpKey) {
            $unmatched[] = "jp: [{$row->id_job_position}]";
        }
        
        DB::table('mst_pd_pengajuans')->where('id', $row->id)->update([
            'new_job_position_id' => $jpId,
            'section_id' => $secId,
        ]);
        if ($jpId) $matched++;
    }
    
    echo "Total rows: " . count($rows) . "\n";
    echo "Matched job positions: {$matched}\n";
    
    if (!empty($unmatched)) {
        echo "Unmatched: " . implode(', ', array_unique($unmatched)) . "\n";
    }
    
    // Step 4: Drop old varchar columns
    DB::statement('ALTER TABLE mst_pd_pengajuans DROP COLUMN id_job_position');
    echo "Dropped id_job_position\n";
    DB::statement('ALTER TABLE mst_pd_pengajuans DROP COLUMN section');
    echo "Dropped section\n";
    
    // Step 5: Rename new_job_position_id → id_job_position
    DB::statement('ALTER TABLE mst_pd_pengajuans CHANGE new_job_position_id id_job_position BIGINT UNSIGNED NULL');
    echo "Renamed new_job_position_id → id_job_position\n";
    
    echo "\nMigration complete!\n";
    echo "Final columns: " . implode(', ', Schema::getColumnListing('mst_pd_pengajuans')) . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
