<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

header('Content-Type: text/plain');

// Cari semua penilaian yang profil user-nya tidak punya departemen (kosong)
$allPenilaian = \App\Models\TrsPenilaianTc::with(['user', 'jobPosition.department'])->get();

$toDelete = $allPenilaian->filter(function ($p) {
    if (!$p->user) return true; // no user → delete
    $userDept = trim((string) $p->user->department);
    return $userDept === ''; // hapus jika departemen user kosong
});

$ids = $toDelete->pluck('id')->toArray();

echo "=== DATA YANG AKAN DIHAPUS (" . count($ids) . " baris) ===\n\n";
foreach ($toDelete as $p) {
    $userName = $p->user ? $p->user->name : 'NO_USER';
    $jpName   = $p->jobPosition ? $p->jobPosition->position_name : 'NO_JP';
    $userDept = $p->user ? (string)$p->user->department : '(null)';
    echo "  ID:{$p->id} | User:{$userName} | Dept:'{$userDept}' | JP:{$jpName} | Status:{$p->status}\n";
}

if (isset($_GET['delete']) && $_GET['delete'] === 'yes') {
    if (empty($ids)) {
        echo "\nTidak ada data yang perlu dihapus.\n";
    } else {
        echo "\n--- MENGHAPUS DATA PENILAIAN UTAMA ---\n";

        // Cukup hapus baris di trs_penilaian_tcs saja
        // Detail (detail_penilaian_tcs) berelasi via id_job_position (bukan id penilaian)
        // jadi tidak perlu dihapus untuk menghindari orphan data
        $deleted = \DB::table('trs_penilaian_tcs')->whereIn('id', $ids)->delete();
        echo "Baris dihapus dari trs_penilaian_tcs: $deleted\n";

        echo "\n✓ SELESAI! Total $deleted data penilaian mismatch berhasil dihapus.\n";
        
        // Verifikasi
        $remaining = \App\Models\TrsPenilaianTc::count();
        echo "\nSisa data di trs_penilaian_tcs: $remaining\n";
    }
} else {
    echo "\n[PREVIEW MODE] Tambahkan ?delete=yes untuk menghapus.\n";
}
