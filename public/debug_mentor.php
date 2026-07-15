<?php
// Debug script - delete after use!
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\Competency\CompetencyAssessmentService;

echo "<pre>";

Cache::flush();
echo "✅ Cache flushed\n\n";

$service = app(CompetencyAssessmentService::class);

// Test TC ID=1 (Rekonsiliasi Data, std=4)
echo "=== Testing TC ID=1 (Rekonsiliasi Data & Akurasi Laporan Administratif, std=4) ===\n";
$compName = DB::table('mst_tcs')->where('id', 1)->value('keterangan_tc');
$allIds = DB::table('mst_tcs')->where('keterangan_tc', $compName)->pluck('id');
echo "Competency name: {$compName}\n";
echo "All IDs with same name: " . $allIds->join(', ') . "\n\n";

$mentors = $service->getEmployeesMeetingStandard('tc', 1);
echo "Mentors found: " . count($mentors) . "\n";
foreach ($mentors as $m) {
    echo "  -> {$m['name']} | jabatan: {$m['job_position']} | actual={$m['actual']} | std={$m['standard']}\n";
}

// Also test TC ID=7 (Troubleshooting)
echo "\n=== Testing TC ID=7 (Troubleshooting & Perbaikan Ringan Kerusakan Mesin) ===\n";
$compName7 = DB::table('mst_tcs')->where('id', 7)->value('keterangan_tc');
$allIds7 = DB::table('mst_tcs')->where('keterangan_tc', $compName7)->pluck('id');
echo "Competency name: {$compName7}\n";
echo "All IDs with same name: " . $allIds7->join(', ') . "\n\n";

$mentors7 = $service->getEmployeesMeetingStandard('tc', 7);
echo "Mentors found: " . count($mentors7) . "\n";
foreach ($mentors7 as $m) {
    echo "  -> {$m['name']} | jabatan: {$m['job_position']} | actual={$m['actual']} | std={$m['standard']}\n";
}

echo "</pre>";
