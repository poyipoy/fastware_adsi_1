<?php
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$filePath = 'c:\laragon\www\fastware_adsi_1\Employee All Dept.ods';

if (!file_exists($filePath)) {
    die("File not found at: $filePath");
}

try {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // Output only the first 20 rows to understand the structure
    echo "Total rows: " . count($rows) . "\n\n";
    $count = 0;
    foreach ($rows as $row) {
        // filter out completely empty rows
        if (!array_filter($row)) continue;
        
        echo implode(" | ", array_map(function($cell) {
            return $cell === null ? 'NULL' : (string)$cell;
        }, $row)) . "\n";
        
        $count++;
        if ($count > 25) break;
    }
} catch (\Exception $e) {
    echo "Error reading ODS: " . $e->getMessage();
}
