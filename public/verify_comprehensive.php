<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

header('Content-Type: text/plain');

echo "=== VERIFIKASI MENYELURUH STATUS DATABASE PENILAIAN ===\n\n";

// Check total penilaian
$total = \DB::table('trs_penilaian_tcs')->count();
echo "Total data trs_penilaian_tcs: $total\n\n";

// Check all existing users and their dept info
echo "=== SEMUA USER DAN INFORMASI DEPT/SECTION ===\n";
$users = \App\Models\User::whereNotNull('name')->get();
$usersWithDept = 0;
$usersWithoutDept = 0;
foreach ($users as $u) {
    $dept = trim((string) $u->department);
    $sec = trim((string) $u->section);
    if ($dept === '') {
        $usersWithoutDept++;
        // Show only users without dept who have job positions
        $hasJob = \App\Models\UserJobPosition::where('user_id', $u->id)->where('is_active', true)->exists();
        if ($hasJob) {
            echo "  ⚠ {$u->name} ({$u->username}) | dept='' | sec='$sec' | HAS JOB POSITION\n";
        }
    } else {
        $usersWithDept++;
    }
}
echo "\nUser dengan dept terisi: $usersWithDept\n";
echo "User tanpa dept: $usersWithoutDept\n\n";

// Check if there's any penilaian data at all (maybe in another table?)
echo "=== CEK TABEL TERKAIT PENILAIAN ===\n";
$tables = ['trs_penilaian_tcs', 'detail_penilaian_tcs'];
foreach ($tables as $t) {
    try {
        $count = \DB::table($t)->count();
        echo "  $t: $count baris\n";
    } catch (\Exception $e) {
        echo "  $t: ERROR - " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Check mst_position_approvals to see what positions are configured
echo "=== KONFIGURASI MST_POSITION_APPROVALS ===\n";
$approvals = \DB::table('mst_position_approvals')
    ->join('mst_job_positions', 'mst_position_approvals.position_id', '=', 'mst_job_positions.id')
    ->select('mst_position_approvals.*', 'mst_job_positions.position_name')
    ->orderBy('position_id')
    ->orderBy('approval_level')
    ->get();
echo "Total konfigurasi approval: " . $approvals->count() . "\n";
foreach ($approvals as $a) {
    echo "  Pos:{$a->position_name} | Level:{$a->approval_level} | ApproverPosId:{$a->approver_position_id}\n";
}

// Ensure controller-level access guard is present
echo "\n=== VERIFIKASI CONTROLLER ACCESS GUARD ===\n";
$controllerContent = file_get_contents('C:/laragon/www/fastware_adsi_1/app/Http/Controllers/PenilaianTCController.php');
$hasGuard = strpos($controllerContent, 'abortUnlessCompetencyLevel') !== false;
echo "abortUnlessCompetencyLevel exists in controller: " . ($hasGuard ? 'YES ✓' : 'NO ✗') . "\n";

// Count occurrences
$count = substr_count($controllerContent, 'abortUnlessCompetencyLevel');
echo "Total penggunaan abortUnlessCompetencyLevel: $count kali\n";

// Check private method definition
$hasMethod = strpos($controllerContent, 'private function abortUnlessCompetencyLevel') !== false;
echo "Method definition exists: " . ($hasMethod ? 'YES ✓' : 'NO ✗') . "\n";

// Check indexTrs has guard
$indexTrsHasGuard = (strpos($controllerContent, "\$this->abortUnlessCompetencyLevel(\$level)") !== false);
echo "indexTrs has guard: " . ($indexTrsHasGuard ? 'YES ✓' : 'NO ✗') . "\n";

// Check HRRoleAccessService has correct logic
echo "\n=== VERIFIKASI HRRoleAccessService ===\n";
$serviceContent = file_get_contents('C:/laragon/www/fastware_adsi_1/app/Services/HR/HRRoleAccessService.php');
$hasFullAccessConst = strpos($serviceContent, 'FULL_ACCESS_USERNAMES') !== false;
$noHrgaBlock = strpos($serviceContent, 'Dynamic check: Has HRGA or PDCA') === false;
$hasUlfaCheck = strpos($serviceContent, "'ULFA'") !== false;
echo "FULL_ACCESS_USERNAMES constant: " . ($hasFullAccessConst ? 'YES ✓' : 'NO ✗') . "\n";
echo "HRGA/PDCA block removed: " . ($noHrgaBlock ? 'YES ✓' : 'NO ✗') . "\n";
echo "ULFA username check: " . ($hasUlfaCheck ? 'YES ✓' : 'NO ✗') . "\n";
