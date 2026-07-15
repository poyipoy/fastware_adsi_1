<?php
require __DIR__.'/../vendor/autoload.php';

$filePath = 'c:\laragon\www\fastware_adsi_1\Employee All Dept.ods';

try {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // Output only the first 10 rows
    echo "Total rows: " . count($rows) . "\n\n";
    $count = 0;
    foreach ($rows as $row) {
        // filter out completely empty rows
        if (!array_filter($row)) continue;
        
        $nonNullCells = [];
        foreach ($row as $cell) {
            if ($cell !== null && trim($cell) !== '') {
                $nonNullCells[] = (string)$cell;
            }
        }
        if (count($nonNullCells) > 0) {
            echo implode(" | ", $nonNullCells) . "\n";
            $count++;
            if ($count > 10) break;
        }
    }
} catch (\Exception $e) {
    echo "Error reading ODS: " . $e->getMessage();
}
