<?php
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
use App\Models\TcJobPosition;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

$filePath = 'c:\laragon\www\fastware_adsi_1\Employee All Dept.ods';

if (!file_exists($filePath)) {
    die("File not found at: $filePath");
}

try {
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    $updatedCount = 0;
    
    foreach ($rows as $index => $row) {
        if ($index === 0) continue; // Skip header
        if (!array_filter($row)) continue; // Skip empty rows
        
        $name = trim((string)$row[0]);
        $approval1 = trim((string)$row[2]);
        $approval2 = trim((string)$row[3]);
        
        if (empty($name)) continue;
        
        // Find user by name
        $user = User::whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($name)])->first();
        if (!$user) {
            continue; // User not found in DB
        }
        
        // Determine section head and department head
        if (!empty($approval2)) {
            $sectionHead = empty($approval1) ? null : strtoupper($approval1);
            $departmentHead = strtoupper($approval2);
        } else {
            // Approval 2 is empty, meaning they report directly to Kadept/Director
            $sectionHead = null;
            $departmentHead = empty($approval1) ? null : strtoupper($approval1);
        }
        
        // Update all job positions for this user
        $affectedRows = TcJobPosition::where('id_user', $user->id)
            ->update([
                'section_head_name' => $sectionHead,
                'department_head_name' => $departmentHead
            ]);
            
        if ($affectedRows > 0) {
            $updatedCount++;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'users_synced' => $updatedCount
    ]);
    
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
