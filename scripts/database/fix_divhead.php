<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TrsPenilaianTc;
use App\Models\MstJobPosition;

echo "=== STARTING STATUS CLEANUP (DIV HEAD -> FINAL) ===\n";

$records = TrsPenilaianTc::where('status', 3)->get();
echo "Found {$records->count()} records with status = 3 (Menunggu Konfirmasi Div. Head).\n";

$fixedCount = 0;

foreach ($records as $row) {
    $job = MstJobPosition::with('department')->find($row->id_job_position);
    $isSales = false;

    if ($job) {
        if ($job->department && (stripos($job->department->name, 'sales') !== false || stripos($job->department->name, 'marketing') !== false)) {
            $isSales = true;
        } elseif (stripos($job->position_name, 'sales') !== false || stripos($job->position_name, 'soh') !== false) {
            $isSales = true;
        }
    }

    // Jika tahun 2025 ATAU posisi bukan Sales, maka status 3 HARUS diubah menjadi 4 (Telah Disetujui / Final)
    if ($row->tahun_penilaian == 2025 || !$isSales) {
        $row->status = 4;
        $row->save();
        $fixedCount++;
        echo "Fixed Job ID {$row->id_job_position} (" . ($job ? $job->position_name : 'Unknown') . ") -> Status changed to 4 (Final)\n";
    }
}

echo "\nSuccessfully updated {$fixedCount} non-Sales / 2025 records from status 3 (Div Head) to status 4 (Telah Disetujui / Final)!\n";
echo "=== CLEANUP FINISHED ===\n";
