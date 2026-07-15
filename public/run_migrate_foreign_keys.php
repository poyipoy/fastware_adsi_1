<?php
/**
 * Script Migrasi Foreign Key Job Position (tc_job_positions.id -> mst_job_positions.id)
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\MstJobPosition;

echo "<pre>";
echo "=== Memulai Migrasi Foreign Key Job Position ===\n\n";

DB::beginTransaction();
try {
    // 1. Buat Mapping Old ID -> New ID
    $oldPositions = DB::table('tc_job_positions')->get();
    
    $mapping = [];
    foreach ($oldPositions as $old) {
        $name = trim($old->job_position);
        if (empty($name)) continue;
        
        $newPos = MstJobPosition::whereRaw('LOWER(position_name) = ?', [strtolower($name)])->first();
        if ($newPos) {
            $mapping[$old->id] = $newPos->id;
        }
    }
    
    echo "Ditemukan " . count($mapping) . " pemetaan job position dari tabel lama ke tabel baru.\n\n";

    // 2. Tentukan batas tanggal "Data Lama" (sebelum 25 Juni 2026)
    $cutoffDate = '2026-06-25 00:00:00';
    
    $tablesToUpdate = [
        'mst_tcs' => 'Master TC',
        'mst_soft_skills' => 'Master Soft Skill',
        'mst_additionals' => 'Master Additional',
        'trs_penilaian_tcs' => 'Transaksi Penilaian',
        'detail_penilaian_tcs' => 'Detail Transaksi'
    ];

    foreach ($tablesToUpdate as $table => $label) {
        echo "Mempersiapkan migrasi {$label} (`{$table}`) ...\n";
        
        $updatedCount = 0;
        
        // Hanya ambil data yang dibuat sebelum tanggal cutoff
        $oldRecords = DB::table($table)->where('created_at', '<', $cutoffDate)->get();
        
        foreach ($oldRecords as $record) {
            $oldIdJobPosition = $record->id_job_position;
            
            // Cek apakah ada mapping ke ID baru
            if (isset($mapping[$oldIdJobPosition])) {
                $newIdJobPosition = $mapping[$oldIdJobPosition];
                
                // Jika ID berbeda (harus di-update)
                if ($oldIdJobPosition != $newIdJobPosition) {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update(['id_job_position' => $newIdJobPosition]);
                    
                    $updatedCount++;
                }
            }
        }
        
        echo "  ✓ Berhasil mengupdate {$updatedCount} record pada tabel `{$table}`.\n\n";
    }

    DB::commit();
    echo "=== SELESAI. Semua foreign key berhasil dimigrasikan! ===\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
echo "</pre>";
