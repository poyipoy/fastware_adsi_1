<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

header('Content-Type: text/plain');

echo "=== VERIFIKASI AKHIR SEMUA PERUBAHAN ===\n\n";

// 1. Verifikasi HRRoleAccessService
echo "1. CEK hasFullAccess (HRRoleAccessService)\n";
echo "-------------------------------------------\n";
$service = new \App\Services\HR\HRRoleAccessService();

// Check all users to see who has full access
$allUsers = \App\Models\User::all();
$fullAccessUsers = [];
foreach ($allUsers as $u) {
    if ($service->hasFullAccess($u)) {
        $fullAccessUsers[] = "{$u->name} (username:{$u->username}, role_id:{$u->role_id})";
    }
}
echo "Users dengan Full Access:\n";
foreach ($fullAccessUsers as $fa) {
    echo "  ✓ $fa\n";
}
echo "Total: " . count($fullAccessUsers) . " user\n\n";

// 2. Verifikasi SITI MARIA ULFA punya full access
echo "2. CEK SITI MARIA ULFA (ULFA)\n";
echo "-------------------------------\n";
$ulfa = \App\Models\User::where('username', 'ULFA')->orWhere('name', 'like', '%SITI MARIA%')->first();
if ($ulfa) {
    $hasFA = $service->hasFullAccess($ulfa) ? 'YES ✓' : 'NO ✗';
    echo "  User: {$ulfa->name} (username:{$ulfa->username})\n";
    echo "  hasFullAccess: $hasFA\n";
} else {
    echo "  ⚠ User dengan username ULFA tidak ditemukan!\n";
    // cari alternatif
    $sitiResults = \App\Models\User::where('name', 'like', '%SITI%')->get();
    echo "  Users bernama SITI:\n";
    foreach ($sitiResults as $u) {
        $fa = $service->hasFullAccess($u) ? 'YES' : 'NO';
        echo "    - {$u->name} (username:{$u->username}) | hasFullAccess: $fa\n";
    }
}
echo "\n";

// 3. Verifikasi HRGA tidak punya full access
echo "3. CEK HRGA TIDAK PUNYA FULL ACCESS\n";
echo "-------------------------------------\n";
$hrgaUsers = \App\Models\UserJobPosition::with(['user', 'jobPosition'])
    ->where('is_active', true)
    ->whereHas('jobPosition', fn($q) => $q->where('department_id', 19))
    ->get();
foreach ($hrgaUsers as $h) {
    $u = $h->user;
    $jp = $h->jobPosition;
    if (!$u || !$jp) continue;
    $fa = $service->hasFullAccess($u) ? 'YES' : 'NO';
    $icon = $fa === 'NO' ? '✓' : '✗ MASALAH!';
    echo "  $icon {$u->name} ({$jp->position_name}): hasFullAccess = $fa\n";
}
echo "\n";

// 4. Verifikasi data penilaian
echo "4. SISA DATA PENILAIAN\n";
echo "----------------------\n";
$totalPenilaian = \App\Models\TrsPenilaianTc::count();
echo "  Total trs_penilaian_tcs: $totalPenilaian\n";
if ($totalPenilaian === 0) {
    echo "  ⚠ PERINGATAN: Tidak ada data penilaian tersisa!\n";
    echo "  Ini normal jika memang semua data di database belum punya dept karyawan.\n";
} else {
    $data = \App\Models\TrsPenilaianTc::with(['user', 'jobPosition'])->get();
    foreach ($data as $p) {
        $u = $p->user ? $p->user->name : 'N/A';
        $jp = $p->jobPosition ? $p->jobPosition->position_name : 'N/A';
        echo "  - ID:{$p->id} | User:$u | JP:$jp | Status:{$p->status}\n";
    }
}
echo "\n";

// 5. Verifikasi canAccessCompetencyLevel per level
echo "5. CEK canAccessCompetencyLevel PER LEVEL\n";
echo "------------------------------------------\n";
// Sample: cek beberapa user
$testUsernames = ['IT', 'ULFA', 'CAHYO', 'RODJO'];
foreach ($testUsernames as $uname) {
    $u = \App\Models\User::where('username', $uname)->first();
    if (!$u) {
        echo "  - $uname: not found\n";
        continue;
    }
    $kasie = $service->canAccessCompetencyLevel($u, 'kasie') ? 'YES' : 'NO';
    $kadept = $service->canAccessCompetencyLevel($u, 'kadept') ? 'YES' : 'NO';
    $divhead = $service->canAccessCompetencyLevel($u, 'divhead') ? 'YES' : 'NO';
    $hr = $service->canAccessCompetencyLevel($u, 'hr') ? 'YES' : 'NO';
    echo "  - {$u->name} ({$uname}): kasie=$kasie kadept=$kadept divhead=$divhead hr=$hr\n";
}
