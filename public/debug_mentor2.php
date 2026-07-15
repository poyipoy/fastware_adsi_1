<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\Competency\CompetencyAssessmentService;

echo "<pre>";
$service = app(CompetencyAssessmentService::class);

echo "=== Testing Pengarsipan Berkas & Dokumen Perusahaan (TC) ===\n";
$compName = DB::table('mst_tcs')->where('id', 2)->value('keterangan_tc');
if ($compName) {
    $mentors = $service->getEmployeesMeetingStandard('tc', 2);
    echo "Mentors found: " . count($mentors) . "\n";
    foreach ($mentors as $m) {
        echo "  -> {$m['name']} | {$m['job_position']} | actual={$m['actual']} | std={$m['standard']}\n";
    }
} else {
    echo "Competency not found\n";
}

echo "\n=== Testing Efisiensi Kerja & Otomasi Laporan Sederhana (AD) ===\n";
// Find the ID for this AD first
$adComp = DB::table('mst_additionals')->where('keterangan_ad', 'LIKE', '%Efisiensi Kerja%')->first();
if ($adComp) {
    echo "Found AD ID: {$adComp->id} - {$adComp->keterangan_ad}\n";
    $mentors = $service->getEmployeesMeetingStandard('ad', $adComp->id);
    echo "Mentors found: " . count($mentors) . "\n";
    foreach ($mentors as $m) {
        echo "  -> {$m['name']} | {$m['job_position']} | actual={$m['actual']} | std={$m['standard']}\n";
    }
} else {
    echo "Competency not found\n";
}

echo "</pre>";
