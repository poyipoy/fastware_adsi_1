<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\TcPeopleDevelopment;
use App\Models\MstJobPosition;

$pengajuans = TcPeopleDevelopment::with(['user', 'jobPosition.department'])->get();
$emptyDeptHeadRecords = [];
$updateCount = 0;

foreach ($pengajuans as $pengajuan) {
    if (!$pengajuan->jobPosition) {
        continue;
    }
    
    $deptId = $pengajuan->jobPosition->department_id;
    
    // Find Dept Head position for this department
    $deptHeadPosition = MstJobPosition::where('department_id', $deptId)
        ->where(function($q) {
            $q->where('position_name', 'like', '%dept head%')
              ->orWhere('position_name', 'like', '%department head%');
        })
        ->with('users')
        ->first();
        
    $picName = null;
    
    if ($deptHeadPosition && $deptHeadPosition->users->count() > 0) {
        $picName = $deptHeadPosition->users->first()->name;
    }
    
    if ($picName) {
        $pengajuan->modified_at = $picName;
        $pengajuan->save();
        $updateCount++;
    } else {
        $pengajuan->modified_at = null; // Biarkan kosong
        $pengajuan->save();
        
        $emptyDeptHeadRecords[] = [
            'id' => $pengajuan->id,
            'karyawan' => $pengajuan->user ? $pengajuan->user->name : 'Unknown',
            'job_position' => $pengajuan->jobPosition->position_name,
            'department' => $pengajuan->jobPosition->department ? $pengajuan->jobPosition->department->department_name : 'Unknown',
            'program_training' => $pengajuan->program_training
        ];
    }
}

echo json_encode([
    'status' => 'success',
    'total_updated' => $updateCount,
    'total_empty' => count($emptyDeptHeadRecords),
    'empty_records' => $emptyDeptHeadRecords
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
