<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

header('Content-Type: text/plain');

echo "=== TOTAL DATA PENILAIAN ===\n";
$total = \App\Models\TrsPenilaianTc::count();
echo "Total: $total\n\n";

echo "=== DETAIL SEMUA DATA PENILAIAN (ID, User, Dept, Section) ===\n";
$all = \App\Models\TrsPenilaianTc::with(['user', 'jobPosition.department', 'jobPosition.section'])->get();

foreach ($all as $p) {
    $userName = $p->user ? $p->user->name : '(NO USER)';
    $userDept = $p->user ? (string)$p->user->department : '(N/A)';
    $userSec  = $p->user ? (string)$p->user->section  : '(N/A)';
    $jpName   = $p->jobPosition ? $p->jobPosition->position_name : '(NO JP)';
    $jpDept   = $p->jobPosition && $p->jobPosition->department ? $p->jobPosition->department->name : '(N/A)';
    $jpSec    = $p->jobPosition && $p->jobPosition->section    ? $p->jobPosition->section->name    : '(N/A)';
    $status   = $p->status;
    
    $deptMatch = (strcasecmp(trim($userDept), trim($jpDept)) === 0) ? '✓' : '✗';
    $secMatch  = ($jpSec === '(N/A)' || strcasecmp(trim($userSec), trim($jpSec)) === 0) ? '✓' : '✗';
    
    $flag = ($deptMatch === '✗' || $secMatch === '✗') ? '[MISMATCH]' : '';
    $deptEmpty = (trim($userDept) === '') ? '[DEPT KOSONG]' : '';
    $secEmpty  = (trim($userSec) === '') ? '[SEC KOSONG]' : '';
    
    echo "ID:{$p->id} | Status:{$status} | {$flag}{$deptEmpty}{$secEmpty}\n";
    echo "  User: $userName | User.Dept: '$userDept' | User.Sec: '$userSec'\n";
    echo "  JP  : $jpName   | JP.Dept  : '$jpDept' | JP.Sec: '$jpSec'\n";
    echo "  Match: Dept=$deptMatch Sec=$secMatch\n\n";
}

// Also check if the previous IDs (22,23,25,30...) still exist
echo "=== CEK ID 22,23,25,30,32-39 MASIH ADA? ===\n";
$checkIds = [22, 23, 25, 30, 32, 33, 34, 35, 36, 37, 38, 39];
$found = \App\Models\TrsPenilaianTc::whereIn('id', $checkIds)->get();
echo "Dari " . count($checkIds) . " ID, ditemukan: " . $found->count() . "\n";
foreach ($found as $p) {
    $userName = $p->user ? $p->user->name : '(NO USER)';
    echo "  - ID: {$p->id} | User: $userName | Status: {$p->status}\n";
}
