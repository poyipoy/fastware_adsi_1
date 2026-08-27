<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;
use App\Models\TcPeopleDevelopment;

// Verify migration results
echo "<h2>Verifikasi Hasil Migrasi Training Development</h2>\n";

$cols = \Illuminate\Support\Facades\Schema::getColumnListing('mst_pd_pengajuans');
echo "<p><b>Kolom tabel:</b> " . implode(', ', $cols) . "</p>\n";

$hasSection = in_array('section', $cols);
$hasSectionId = in_array('section_id', $cols);
$hasJobPosId = in_array('id_job_position', $cols);

echo "<p>section (varchar): " . ($hasSection ? '❌ MASIH ADA' : '✅ SUDAH DIHAPUS') . "</p>\n";
echo "<p>section_id (int): " . ($hasSectionId ? '✅ ADA' : '❌ TIDAK ADA') . "</p>\n";
echo "<p>id_job_position: " . ($hasJobPosId ? '✅ ADA' : '❌ TIDAK ADA') . "</p>\n";

// Check actual data
echo "<h3>Data di mst_pd_pengajuans:</h3>\n";
$rows = TcPeopleDevelopment::with('section', 'jobPosition', 'user')->take(5)->get();
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>ID</th><th>section_id</th><th>Section Name</th><th>id_job_position</th><th>Job Position</th><th>User</th></tr>\n";
foreach ($rows as $r) {
    echo "<tr>";
    echo "<td>{$r->id}</td>";
    echo "<td>" . ($r->section_id ?? 'NULL') . "</td>";
    echo "<td>" . ($r->section?->name ?? '-') . "</td>";
    echo "<td>" . ($r->id_job_position ?? 'NULL') . "</td>";
    echo "<td>" . ($r->jobPosition?->position_name ?? '-') . "</td>";
    echo "<td>" . ($r->user?->name ?? '-') . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

// Check sections count
$totalSections = \App\Models\MstSection::count();
echo "<p>Total MstSection di master: {$totalSections}</p>\n";

// Matched vs unmatched
$matched = DB::table('mst_pd_pengajuans')->whereNotNull('section_id')->count();
$unmatched = DB::table('mst_pd_pengajuans')->whereNull('section_id')->count();
echo "<p>Baris dengan section_id cocok: {$matched}</p>\n";
echo "<p>Baris dengan section_id NULL: {$unmatched}</p>\n";
