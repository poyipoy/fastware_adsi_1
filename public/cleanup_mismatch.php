<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

header('Content-Type: text/plain');

echo "=== PENGHAPUSAN DATA PENILAIAN MISMATCH ===\n\n";

// Cari semua penilaian yang user-nya tidak punya department & section (kosong)
// Ini yang disebut mismatch karena data karyawan tidak lengkap
$toDelete = \App\Models\TrsPenilaianTc::with(['user', 'jobPosition'])->get()->filter(function ($p) {
    if (!$p->user) return true; // tidak ada user → hapus
    
    $userDept = trim((string) $p->user->department);
    $userSec = trim((string) $p->user->section);
    
    // Jika departemen dan section keduanya kosong → mismatch
    return $userDept === '' && $userSec === '';
});

echo "Ditemukan " . $toDelete->count() . " data penilaian dengan profil karyawan kosong:\n\n";

foreach ($toDelete as $p) {
    $userName = $p->user ? $p->user->name : 'USER TIDAK ADA';
    $jpName = $p->jobPosition ? $p->jobPosition->position_name : 'N/A';
    $status = $p->status;
    echo "  - ID: {$p->id} | User: $userName | JobPos: $jpName | Status: $status\n";
}

echo "\n--- Apakah ingin dilanjutkan? Tambahkan ?delete=yes ke URL ---\n\n";

if (isset($_GET['delete']) && $_GET['delete'] === 'yes') {
    $ids = $toDelete->pluck('id')->toArray();
    
    if (empty($ids)) {
        echo "Tidak ada data yang perlu dihapus.\n";
    } else {
        // Hapus detail dulu (foreign key)
        $detailsDeleted = \App\Models\DetailTcPenilaian::whereIn('id_trs_penilaian_tc', $ids)->delete();
        echo "Detail dihapus: $detailsDeleted baris\n";
        
        // Hapus penilaian utama
        $mainDeleted = \App\Models\TrsPenilaianTc::whereIn('id', $ids)->delete();
        echo "Penilaian utama dihapus: $mainDeleted baris\n";
        echo "\n✓ Selesai! Data penilaian mismatch telah dihapus.\n";
    }
} else {
    echo "[PREVIEW MODE] Untuk menghapus, akses: /cleanup_mismatch.php?delete=yes\n";
}

echo "\n=== INFORMASI SITI MARIA ULFA ===\n";
$siti = \App\Models\User::where('name', 'like', '%SITI%MARIA%')->orWhere('username', 'ULFA')->first();
if ($siti) {
    echo "ID: {$siti->id} | Name: {$siti->name} | Username: {$siti->username} | role_id: {$siti->role_id}\n";
} else {
    echo "User SITI MARIA ULFA tidak ditemukan dengan username=ULFA.\n";
    // Cari lebih lanjut
    $results = \App\Models\User::where('name', 'like', '%SITI%')->get();
    foreach ($results as $r) {
        echo "  - ID: {$r->id} | Name: {$r->name} | Username: {$r->username}\n";
    }
}

echo "\n=== CEK SIAPA YANG SEKARANG DAPAT FULL ACCESS BERDASARKAN SERVICE BARU ===\n";
$service = new \App\Services\HR\HRRoleAccessService();
// Cek beberapa user penting
$testUsers = \App\Models\User::whereIn('username', ['ULFA', 'IT', 'ADMIN', 'ADMINISTRATOR'])->get();
foreach ($testUsers as $u) {
    $fullAccess = $service->hasFullAccess($u) ? 'YES' : 'NO';
    echo "  - {$u->name} ({$u->username}): hasFullAccess = $fullAccess\n";
}

// Cek user yang dulu punya full access (HRGA dept)
echo "\n=== CEK USER HRGA YANG DULU FULL ACCESS ===\n";
$hrgaUsers = \App\Models\UserJobPosition::with(['user', 'jobPosition.department'])
    ->where('is_active', true)
    ->whereHas('jobPosition', fn($q) => $q->where('department_id', 19))
    ->get();
foreach ($hrgaUsers as $h) {
    $u = $h->user;
    $jp = $h->jobPosition;
    if (!$u || !$jp) continue;
    $fullAccess = $service->hasFullAccess($u) ? 'YES' : 'NO';
    echo "  - {$u->name} ({$u->username}) | {$jp->position_name}: hasFullAccess = $fullAccess\n";
}
